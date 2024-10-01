<?php

namespace App\Http\Controllers\API;


use App\Models\CarBrand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CarBrandController extends Controller
{
    // Display a listing of product brands
    public function index()
    {
        $carBrands = CarBrand::with(['createdBy', 'updatedBy'])->get();
        return response()->json($carBrands);
    }

    // Display the specified product brand
    public function show($id)
    {
        $carBrand = CarBrand::with(['createdBy', 'updatedBy'])->find($id);

        if (!$carBrand) {
            return response()->json(['message' => 'car brand not found'], 404);
        }

        return response()->json($carBrand);
    }

    // Store a newly created product brand
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'country_of_origin' => 'required|string|max:255',
        ]);

        $logoUrl = null;
        if ($request->hasFile('photo')) {
            $logoUrl = $this->uploadPhoto($request->file('photo'), 'car_brand_logos');
        }

        $carBrand = CarBrand::create([
            'name' => $request->name,
            'description' => $request->description,
            'logo_url' => $logoUrl,
            'country_of_origin' => $validatedData['country_of_origin'],
            'status' => $validatedData['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'car brand created successfully', 'data' => $carBrand]);
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

    // Update the specified product brand
    public function update(Request $request, $carBrandId)
    {
        $validatedData =  $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'country_of_origin' => 'required|string|max:255',
        ]);

        $carBrand = CarBrand::find($carBrandId);
        if (!$carBrand) {
            return response()->json(['message' => 'car brand not found'], 404);
        }

        $logoUrl = $carBrand->logo_url;
        if ($request->hasFile('photo')) {
            if ($logoUrl) {
                $photoPath = parse_url($logoUrl, PHP_URL_PATH);
                $photoPath = ltrim($photoPath, '/');
                if (file_exists(public_path($photoPath))) {
                    unlink(public_path($photoPath));
                }
            }
            $logoUrl = $this->uploadPhoto($request->file('photo'), 'car_brand_logos');
        }

        $carBrand->update([
            'name' => $request->name,
            'description' => $request->description,
            'logo_url' => $logoUrl,
            'country_of_origin' => $validatedData['country_of_origin'],
            'status' => $validatedData['status'],
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'car brand updated successfully', 'data' => $carBrand]);
    }

    // Remove the specified product brand
    public function destroy($id)
    {
        $carBrand = CarBrand::find($id);

        if (!$carBrand) {
            return response()->json(['message' => 'car brand not found'], 404);
        }

        if ($carBrand->logo_url) {
            $photoPath = parse_url($carBrand->logo_url, PHP_URL_PATH);
            $photoPath = ltrim($photoPath, '/');
            if (file_exists(public_path($photoPath))) {
                unlink(public_path($photoPath));
            }
        }

        $carBrand->delete();

        return response()->json(null, 204);
    }
}
