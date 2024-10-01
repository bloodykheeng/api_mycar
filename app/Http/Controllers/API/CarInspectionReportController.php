<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\InspectionField;
use Illuminate\Support\Facades\DB;
use App\Models\CarInspectionReport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CarInspectionReportField;
use App\Models\CarInspectionFieldCategory;
use App\Models\CarInspectionReportCategory;

class CarInspectionReportController extends Controller
{
    // Display a listing of all reports
    public function index(Request $request)
    {
        // Start the query with eager loading
        $reportsQuery = CarInspectionReport::with([
            'CarInspectionReportCategory' => function ($query) {
                $query->with([
                    'inspectionFieldCategory',
                    'fields' => function ($query) {
                        $query->with([
                            'inspectionField',
                            'creator'
                        ]);
                    }
                ]);
            },
            'car',
            'creator',
            'updater'
        ]);

        if ($request->has('car_id')) {
            $reportsQuery->where('car_id', $request->input('car_id'));
        }

        // Filter by status using the request input for 'reason'
        if ($request->has('reason')) {
            $statusReason = $request->input('reason');
            if ($statusReason === 'pending-approval-by-the-admin') {
                $reportsQuery->currentStatus('pending approval'); // Assuming the status name is exactly 'pending approval'
            } elseif ($statusReason === 'approved') {
                $reportsQuery->currentStatus('approved');
            }
        }

        // Execute the query
        $reports = $reportsQuery->get();

        // Return the result as JSON
        return response()->json($reports);
    }


    public function updateCarInspectionReportStatus(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer', // Renamed to 'report_id' for clarity
            'comment' => 'required|string',
            'status' => 'required|in:approved,rejected',
        ]);

        try {
            DB::beginTransaction();

            // Find the report by id and update its status
            $report = CarInspectionReport::with([
                'CarInspectionReportCategory' => function ($query) {
                    $query->with([
                        'inspectionFieldCategory',
                        'fields' => function ($query) {
                            $query->with([
                                'inspectionField',
                                'creator'
                            ]);
                        }
                    ]);
                },
                'car',
                'creator',
                'updater'
            ])->findOrFail($request->report_id);

            $report->setStatus(
                $request->status,
                $request->comment
            ); // Set status using Spatie's package


            DB::commit();
            return response()->json(['message' => 'Status updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating status', 'error' => $e->getMessage()], 500);
        }
    }



    // Display a specific report by ID
    public function show($id)
    {
        $report = CarInspectionReport::with(['fields.inspectionField', 'creator', 'updater'])->find($id);
        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }
        return response()->json($report);
    }


    // Store a newly created report and its fields
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string',
            'car_id' => 'required|integer|exists:cars,id',
            'details' => 'nullable|string',
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:car_inspection_field_categories,id',
            'categories.*.inspection_fields' => 'required|array',
            'categories.*.inspection_fields.*.id' => 'required|exists:inspection_fields,id',
            'categories.*.inspection_fields.*.value' => 'nullable|string',
        ]);

        // Check if a report for the specified car already exists
        if (CarInspectionReport::where('car_id', $validatedData['car_id'])->exists()) {
            return response()->json([
                'message' => 'A report for this car already exists.'
            ], 409); // HTTP 409 Conflict could be appropriate here
        }

        DB::beginTransaction();
        try {
            // Create the main report
            $report = CarInspectionReport::create([
                'name' => $validatedData['name'],
                'car_id' => $validatedData['car_id'],
                'details' => $validatedData['details'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Loop through each category and validate and create associated fields
            foreach ($validatedData['categories'] as $category) {
                $fieldCategoryId = $category['id'];
                if (!CarInspectionFieldCategory::where('id', $fieldCategoryId)->exists()) {
                    throw new \Exception("Invalid field category ID: " . $fieldCategoryId);
                }

                // Create category entries linked to the report
                $reportCategory = CarInspectionReportCategory::create([
                    'car_inspection_reports_id' => $report->id,
                    'car_inspection_field_categories_id' => $fieldCategoryId,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                foreach ($category['inspection_fields'] as $field) {
                    $fieldId = $field['id'];
                    if (!InspectionField::where('id', $fieldId)->exists()) {
                        throw new \Exception("Invalid inspection field ID: " . $fieldId);
                    }

                    // Create fields associated with the category
                    CarInspectionReportField::create([
                        'car_inspection_reports_id' => $report->id,
                        'car_inspection_report_categories_id' => $reportCategory->id,
                        'inspection_fields_id' => $fieldId,
                        'value' => $field['value'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
            $report->setStatus('pending approval', 'Awaiting approval by Admin');
            DB::commit();
            return response()->json([
                'message' => 'Report and its fields created successfully',
                'data' => $report
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create the report due to an error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Update a report and its fields
    public function update(Request $request, $id)
    {
        // Retrieve the report and ensure it exists
        $report = CarInspectionReport::findOrFail($id);

        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string',
            'details' => 'nullable|string',
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:car_inspection_field_categories,id',
            'categories.*.inspection_fields' => 'required|array',
            'categories.*.inspection_fields.*.id' => 'required|exists:inspection_fields,id',
            'categories.*.inspection_fields.*.value' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update the main report details
            $report->update([
                'name' => $validatedData['name'],
                'details' => $validatedData['details'],
                'updated_by' => Auth::id(),
            ]);

            // Loop through each category
            foreach ($validatedData['categories'] as $category) {
                // Ensure the category ID is valid
                $reportCategory = CarInspectionReportCategory::firstOrCreate([
                    'car_inspection_reports_id' => $report->id,
                    'car_inspection_field_categories_id' => $category['id'],
                ], [
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // Loop through each field within the category
                foreach ($category['inspection_fields'] as $field) {
                    $reportField = CarInspectionReportField::updateOrCreate([
                        'car_inspection_report_categories_id' => $reportCategory->id,
                        'inspection_fields_id' => $field['id'],
                    ], [
                        'value' => $field['value'],
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(
                [
                    'message' => 'Report and its fields updated successfully',
                    'data' => $report
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update the report due to an error',
                'error' => $e->getMessage()
            ], 400);
        }
    }


    // Remove a specific report and its related fields
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $report = CarInspectionReport::findOrFail($id);
            $report->delete();           // Then delete the report

            DB::commit();
            return response()->json(['message' => 'Report deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete the report', 'error' => $e->getMessage()], 400);
        }
    }
}