<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CarWashOrder;

class CarWashOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Start building the query
        $query = CarWashOrder::with(['car', 'carWashFee', 'vendor', 'createdBy', 'updatedBy']);

        // Get the currently authenticated user
        /** @var \App\Models\User */
        $user = Auth::user();

        // Check if the user has the 'Vendor' role and apply the filter
        if ($user->hasRole('Vendor')) {
            // Assuming the UserVendor model defines the relationship to get the vendor id
            $vendorId = $user->vendors->vendor_id ?? null;
            if ($vendorId) {
                $query->whereHas('vendor', function ($q) use ($vendorId) {
                    $q->where('id', $vendorId);
                });
            }
        }

        // Execute the query and get the results
        $carWashOrders = $query->get();

        return response()->json($carWashOrders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_wash_fee_id' => 'required|exists:car_wash_fees,id',
            'car_id' => 'required|exists:cars,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'details' => 'nullable|string',

        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $carWashOrder = CarWashOrder::create($validated);
        return response()->json($carWashOrder, 201);
    }

    public function show(CarWashOrder $carWashOrder)
    {
        $carWashOrder->load(['car', 'carWashFee', 'vendor', 'createdBy', 'updatedBy']);
        return response()->json($carWashOrder);
    }

    public function update(Request $request, $id)
    {
        $carWashOrder = CarWashOrder::find($id);
        if (!$carWashOrder) {
            return response()->json(['message' => 'Car wash order not found'], 404);
        }

        $validated = $request->validate([
            'car_wash_fee_id' => 'sometimes|exists:car_wash_fees,id',
            'car_id' => 'sometimes|exists:cars,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'details' => 'nullable|string',
        ]);

        $validated['updated_by'] = Auth::id();

        $carWashOrder->update($validated);
        return response()->json(['message' => 'Car wash order updated successfully', 'data' => $carWashOrder]);
    }


    public function destroy($id)
    {
        $carWashOrder = CarWashOrder::find($id);

        if (!$carWashOrder) {
            return response()->json(['message' => 'Car Wash Order not found'], 404);
        }

        $carWashOrder->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
    }
}
