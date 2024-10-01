<?php

namespace App\Http\Controllers\API;

use App\Models\CarInspector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CarInspectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carInspectors = CarInspector::with(['car', 'inspector', 'createdBy', 'updatedBy'])->get();
        return response()->json($carInspectors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated =  $request->validate([
            'car_id' => 'required|integer|exists:cars,id',
            'inspector_id' => 'required|integer|exists:users,id'
        ]);

        DB::transaction(function () use ($request, $validated) {

            // Check if a similar record already exists
            // $existingCarInspector = CarInspector::where('car_id', $validated['car_id'])
            //     ->where('inspector_id', $validated['inspector_id'])
            //     ->first();

            // if ($existingCarInspector) {
            //     // Optionally handle the logic if the association already exists
            //     // For example, you can return an informative message or update the existing record
            //     return response()->json(['message' => 'Inspector already assigned to this car'], 409);
            // }
            // Check and delete existing record
            $existingCarInspector = CarInspector::where('car_id', $request->car_id)
                ->where('inspector_id', $request->inspector_id)
                ->first();
            if ($existingCarInspector) {
                $existingCarInspector->delete();
            }

            // Create a new record
            $carInspector = CarInspector::create([
                'car_id' => $request->car_id,
                'inspector_id' => $request->inspector_id,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return response()->json($carInspector, 201);
        });
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $carInspector = CarInspector::with(['car', 'inspector', 'createdBy', 'updatedBy'])->findOrFail($id);
        return response()->json($carInspector);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $carInspector = CarInspector::findOrFail($id);

        $request->validate([
            'car_id' => 'required|integer|exists:cars,id',
            'inspector_id' => 'required|integer|exists:users,id'
        ]);

        $carInspector->update([
            'car_id' => $request->car_id,
            'inspector_id' => $request->inspector_id,
            'updated_by' => Auth::id()
        ]);

        return response()->json($carInspector);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $carInspector = CarInspector::findOrFail($id);
        $carInspector->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}