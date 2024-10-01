<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CarWashFee;

class CarWashFeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $careWashFees = CarWashFee::with(['createdBy', 'updatedBy', 'carType'])->get();
        return response()->json($careWashFees);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fee_amount' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'billing_cycle' => 'required|string',
            'status' => 'required|string',
            'car_type_id' => 'required|exists:car_types,id',
            'details' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $careWashFee = CarWashFee::create($validated);
        return response()->json($careWashFee, 201);
    }

    public function show(CarWashFee $careWashFee)
    {
        $careWashFee->load(['createdBy', 'updatedBy', 'productType']);
        return response()->json($careWashFee);
    }

    public function update(Request $request, $id)
    {
        $carWashFee = CarWashFee::find($id);
        if (!$carWashFee) {
            return response()->json(['message' => 'Car wash fee not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'fee_amount' => 'sometimes|numeric',
            'currency' => 'sometimes|string|max:3',
            'billing_cycle' => 'sometimes|string',
            'status' => 'sometimes|string',
            'car_type_id' => 'sometimes|exists:car_types,id',
        ]);

        $validated['updated_by'] = Auth::id();

        $carWashFee->update($validated);
        return response()->json(['message' => 'Car wash fee updated successfully', 'data' => $carWashFee]);
    }

    public function destroy($id)
    {
        $careWashFee = CarWashFee::find($id);

        if (!$careWashFee) {
            return response()->json(['message' => 'Car Wash Fee not found'], 404);
        }

        $careWashFee->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
    }
}
