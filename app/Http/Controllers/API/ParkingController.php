<?php

namespace App\Http\Controllers\API;

use App\Models\Parking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ParkingController extends Controller
{
    public function index()
    {
        // Start building the query
        $query = Parking::with(['car', 'parkingFee', 'vendor', 'createdBy', 'updatedBy']);

        // Order the results by the created_at column in descending order (latest first)
        // $query->latest();

        // Get the currently authenticated user
        /** @var \App\Models\User */
        $user = Auth::user();

        // Check if the user has the 'Vendor' role and apply the filter
        if ($user && $user->hasRole('Vendor')) {
            // Assuming the UserVendor model defines the relationship to get the vendor id
            $vendorId = $user->vendors->vendor_id ?? null;
            if ($vendorId) {
                $query->where('vendor_id', $vendorId);
            }
        }

        // Execute the query and get the results
        $parking = $query->get();

        return response()->json($parking);
    }
    public function show($id)
    {
        // Include 'parkingFee' in the with() method to fetch its data
        $parking = Parking::with(['car', 'parkingFee', 'vendor', 'createdBy', 'updatedBy'])->find($id);
        if (!$parking) {
            return response()->json(['message' => 'Parking not found'], 404);
        }
        return response()->json($parking);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'parking_fee_id' => 'required|exists:parking_fees,id', // Add parking_fee_id validation
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'details' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id', // Assuming vendor is optional
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $parking = Parking::create($validated);
        return response()->json(['message' => 'Parking created successfully', 'data' => $parking], 201);
    }

    public function update(Request $request, $id)
    {
        $parking = Parking::find($id);
        if (!$parking) {
            return response()->json(['message' => 'Parking not found'], 404);
        }

        $validated = $request->validate([
            'car_id' => 'sometimes|exists:cars,id',
            'parking_fee_id' => 'sometimes|exists:parking_fees,id', // Add parking_fee_id validation
            'vendor_id' => 'sometimes|exists:vendors,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'details' => 'nullable',
        ]);

        $validated['updated_by'] = Auth::id();

        $parking->update($validated);
        return response()->json(['message' => 'Parking updated successfully', 'data' => $parking]);
    }

    public function destroy($id)
    {
        $parking = Parking::find($id);
        if (!$parking) {
            return response()->json(['message' => 'Parking not found'], 404);
        }

        $parking->delete();
        return response()->json(null, 204);
    }
}