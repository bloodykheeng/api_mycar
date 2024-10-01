<?php

namespace App\Http\Controllers\API;

use App\Models\CarType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CarTypeController extends Controller
{
    // Display a listing of product types
    public function index()
    {
        $carTypes = CarType::with(['creator', 'updater'])->get();
        return response()->json($carTypes);
    }

    // Store a newly created car type
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'car_type_photos');
        }

        $carType = CarType::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'status' => $validatedData['status'],
            'photo_url' => $photoUrl,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'car type created successfully', 'data' => $carType]);
    }

    // Display the specified car type
    public function show($id)
    {
        $carType = CarType::with(['creator', 'updater'])->find($id);

        if (!$carType) {
            return response()->json(['message' => 'car type not found'], 404);
        }

        return response()->json($carType);
    }

    // Update the specified car type
    public function update(Request $request, $id)
    {
        $carType = CarType::find($id);
        if (!$carType) {
            return response()->json(['message' => 'car type not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoUrl = $carType->photo_url;
        if ($request->hasFile('photo')) {
            // Delete old photo if it exists
            if ($photoUrl) {
                $this->deletePhoto($photoUrl);
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'car_type_photos');
        }

        $carType->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'status' => $request->status,
            'photo_url' => $photoUrl,
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'car type updated successfully', 'data' => $carType]);
    }

    // Remove the specified car type
    public function destroy($id)
    {
        $carType = CarType::find($id);

        if (!$carType) {
            return response()->json(['message' => 'car type not found'], 404);
        }

        // Delete associated photo file
        if ($carType->photo_url) {
            $this->deletePhoto($carType->photo_url);
        }

        $carType->delete();

        return response()->json(['message' => 'car type deleted successfully']);
    }

    private function uploadPhoto($photo, $folderPath)
    {
        $publicPath = public_path($folderPath);
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0777, true, true);
        }

        $fileName = time() . '_' . $photo->getClientOriginalName();
        $photo->move($publicPath, $fileName);

        return '/' . $folderPath . '/' . $fileName;
    }

    private function deletePhoto($photoUrl)
    {
        $photoPath = parse_url($photoUrl, PHP_URL_PATH);
        $photoPath = public_path($photoPath);
        if (File::exists($photoPath)) {
            File::delete($photoPath);
        }
    }
}
