<?php

namespace App\Http\Controllers\API;

use App\Models\OfficeRent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OfficeRentController extends Controller
{
    public function index()
    {
        $officeRents = OfficeRent::with(['office', 'vendor', 'creator', 'updater'])->get();
        return response()->json($officeRents);
    }

    public function show($id)
    {
        $officeRent = OfficeRent::with(['office', 'vendor', 'creator', 'updater'])->find($id);
        if (!$officeRent) {
            return response()->json(['message' => 'Office rent not found'], 404);
        }
        return response()->json($officeRent);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'details' => 'nullable|string'
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $officeRent = OfficeRent::create($validated);
        return response()->json(['message' => 'Office rent created successfully', 'data' => $officeRent]);
    }

    public function update(Request $request, $id)
    {
        $officeRent = OfficeRent::find($id);
        if (!$officeRent) {
            return response()->json(['message' => 'Office rent not found'], 404);
        }

        $validated = $request->validate([
            'office_id' => 'sometimes|exists:offices,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'vendor_id' => 'sometimes|exists:vendors,id',
            'details' => 'nullable|string'
        ]);

        $validated['updated_by'] = Auth::id();

        $officeRent->update($validated);
        return response()->json(['message' => 'Office rent updated successfully', 'data' => $officeRent]);
    }

    public function destroy($id)
    {
        $officeRent = OfficeRent::find($id);
        if (!$officeRent) {
            return response()->json(['message' => 'Office rent not found'], 404);
        }

        $officeRent->delete();
        return response()->json(null, 204);
    }
}