<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CarInspectionFieldCategory;

class CarInspectionFieldCategoryController extends Controller
{
    // Display a listing of the car inspection field categories
    public function index()
    {
        $categories = CarInspectionFieldCategory::with(['createdByUser', 'updatedByUser'])->get();
        return response()->json($categories);
    }


    // public function getCategoryWithFields()
    // {
    //     // Get all categories with their related inspection fields
    //     $categories = CarInspectionFieldCategory::with(['inspectionFields'])->get();
    //     return response()->json($categories);
    // }
    public function getCategoryWithFields()
    {
        // Get categories with inspection fields using eager loading
        $categories = CarInspectionFieldCategory::with('inspectionFields')->has('inspectionFields')->get();

        // Return the filtered categories
        return response()->json($categories);
    }
    // Store a newly created car inspection field category
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = CarInspectionFieldCategory::create([
            'name' => $validatedData['name'],
            'status' => $validatedData['status'],
            'description' => $validatedData['description'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Category created successfully', 'data' => $category]);
    }

    // Display the specified car inspection field category
    public function show($id)
    {
        $category = CarInspectionFieldCategory::with(['createdByUser', 'updatedByUser'])->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    // Update the specified car inspection field category
    public function update(Request $request, $id)
    {
        $category = CarInspectionFieldCategory::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $validatedData['name'],
            'status' => $validatedData['status'],
            'description' => $validatedData['description'],
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Category updated successfully', 'data' => $category]);
    }

    // Remove the specified car inspection field category
    public function destroy($id)
    {
        $category = CarInspectionFieldCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}