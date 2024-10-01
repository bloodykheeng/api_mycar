<?php

namespace App\Http\Controllers\API;

use App\Models\ParkingFee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ParkingFeeController extends Controller
{
    public function index()
    {
        $parkingFees = ParkingFee::with(['carType', 'createdBy', 'updatedBy'])->get();
        return response()->json($parkingFees);
    }

    public function show($id)
    {
        $parkingFee = ParkingFee::with(['carType', 'createdBy', 'updatedBy'])->find($id);
        if (!$parkingFee) {
            return response()->json(['message' => 'Parking fee not found'], 404);
        }
        return response()->json($parkingFee);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'currency' => 'required|string|max:3',
            'billing_cycle' => 'required|string',
            'status' => 'required|string',
            'fee_amount' => 'required|numeric',
            'car_type_id' => 'required|exists:car_types,id',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $parkingFee = ParkingFee::create($validated);
        return response()->json(['message' => 'Parking fee created successfully', 'data' => $parkingFee], 201);
    }

    public function update(Request $request, $id)
    {
        $parkingFee = ParkingFee::find($id);
        if (!$parkingFee) {
            return response()->json(['message' => 'Parking fee not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string',
            'currency' => 'sometimes|string|max:3',
            'billing_cycle' => 'sometimes|string',
            'status' => 'sometimes|string',
            'fee_amount' => 'sometimes|numeric',
            'car_type_id' => 'sometimes|exists:car_types,id',
        ]);

        $validated['updated_by'] = Auth::id();

        $parkingFee->update($validated);
        return response()->json(['message' => 'Parking fee updated successfully', 'data' => $parkingFee]);
    }

    public function destroy($id)
    {
        $parkingFee = ParkingFee::find($id);
        if (!$parkingFee) {
            return response()->json(['message' => 'Parking fee not found'], 404);
        }

        $parkingFee->delete();
        return response()->json(null, 204);
    }
}
