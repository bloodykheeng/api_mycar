<?php

namespace App\Http\Controllers\API;

use App\Models\Office;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OfficeController extends Controller
{
    public function index()
    {
        // Assuming offices are used by multiple departments and managed by different users
        $offices = Office::with(['creator', 'updater'])->get();
        return response()->json($offices);
    }

    public function show($id)
    {
        $office = Office::with(['creator', 'updater'])->find($id);
        if (!$office) {
            return response()->json(['message' => 'Office not found'], 404);
        }
        return response()->json($office);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'currency' => 'required|string|max:255',
            'billing_cycle' => 'required|string',
            'size' => 'required|integer',
            'payment_terms' => 'nullable|string',
            'room_capacity' => 'nullable|integer',
            'fee_amount' => 'required|numeric'
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $office = Office::create($validated);
        return response()->json(['message' => 'Office created successfully', 'data' => $office]);
    }

    public function update(Request $request, $id)
    {
        $office = Office::find($id);
        if (!$office) {
            return response()->json(['message' => 'Office not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:255',
            'currency' => 'required|string|max:255',
            'billing_cycle' => 'sometimes|string',
            'size' => 'sometimes|integer',
            'payment_terms' => 'nullable|string',
            'room_capacity' => 'nullable|integer',
            'fee_amount' => 'sometimes|numeric'
        ]);

        $validated['updated_by'] = Auth::id();

        $office->update($validated);
        return response()->json(['message' => 'Office updated successfully', 'data' => $office]);
    }

    public function destroy($id)
    {
        $office = Office::find($id);
        if (!$office) {
            return response()->json(['message' => 'Office not found'], 404);
        }

        $office->delete();
        return response()->json(null, 204);
    }
}