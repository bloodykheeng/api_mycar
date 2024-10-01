<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class GarageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start building the query
        $query = Garage::with(['reviews', 'createdByUser', 'updatedByUser', 'reviews']);

        // Order the results by the created_at column in descending order (latest first)
        // $query->latest();

        // Apply filters from request
        if (!empty($request->search)) { // Check if search is not null and not an empty string
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Get the result of the query
        // $garages = $query->get();

        // Paginate the results
        $perPage = $request->input('per_page', 10); // Number of results per page, default is 10
        $garages = $query->paginate($perPage);

        return response()->json($garages);
    }

    // New method to get garage by slug
    public function getBySlug($slug)
    {
        // Retrieve the garage along with related details
        $garage = Garage::with(['reviews', 'createdByUser', 'updatedByUser', 'reviews'])->where('slug', $slug)->first();

        if (!$garage) {
            return response()->json(['message' => 'Garage not found'], 404);
        }

        return response()->json($garage);
    }
    // Store a newly created Garage
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'address' => 'required',
            'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'availability' => 'required',
            'opening_hours' => 'required',
            'special_features' => 'nullable',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'garage_photos');
            $validatedData['photo_url'] = $photoUrl;
        }
        $validatedData['availability'] = $validatedData['availability'] ? 1 : 0;
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $garage = Garage::create($validatedData);

        return response()->json(['message' => 'Garage created successfully', 'data' => $garage]);
    }

    // Display the specified Garage
    public function show($id)
    {
        $garage = Garage::with(['reviews', 'createdByUser', 'updatedByUser', 'reviews'])->find($id);

        if (!$garage) {
            return response()->json(['message' => 'Garage not found'], 404);
        }

        return response()->json($garage);
    }

    // Update the specified Garage
    public function update(Request $request, $id)
    {
        $garage = Garage::find($id);
        if (!$garage) {
            return response()->json(['message' => 'Garage not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required',
            'address' => 'required',
            'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'availability' => 'required',
            'opening_hours' => 'required',
            'special_features' => 'nullable',
        ]);

        $photoUrl = $garage->photo_url;
        if ($request->hasFile('photo')) {
            // Delete old photo if it exists
            if ($photoUrl) {
                $this->deletePhoto($photoUrl);
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'garage_photos');
            $validatedData['photo_url'] = $photoUrl;
        }
        $validatedData['updated_by'] = Auth::id();
        $validatedData['availability'] = $validatedData['availability'] ? 1 : 0;

        $garage->update($validatedData);

        return response()->json(['message' => 'Garage updated successfully', 'data' => $garage]);
    }

    // Remove the specified Garage
    public function destroy($id)
    {
        $garage = Garage::find($id);

        if (!$garage) {
            return response()->json(['message' => 'Garage not found'], 404);
        }

        // Delete associated photo file
        if ($garage->photo_url) {
            $this->deletePhoto($garage->photo_url);
        }

        $garage->delete();

        return response()->json(['message' => 'Garage deleted successfully']);
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