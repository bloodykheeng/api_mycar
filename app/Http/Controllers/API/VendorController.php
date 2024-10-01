<?php

namespace App\Http\Controllers\API;

use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class VendorController extends Controller
{
    // Display a listing of vendors
    public function index()
    {
        // Start building the query
        $query = Vendor::with(['createdBy', 'updatedBy']);
    
        // Order the results by the created_at column in descending order (latest first)
        // $query->latest();
    
        // Execute the query and get the results
        $vendors = $query->get();
    
        return response()->json($vendors);
    }

    // Display the specified vendor
    public function show($id)
    {
        $vendor = Vendor::with(['createdBy', 'updatedBy'])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        return response()->json($vendor);
    }

    // Store a newly created vendor
    public function store(Request $request)
    {
        $validatedData =  $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'code' => 'required|string|unique:vendors',
            'status' => 'required|string|max:255',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'vendor_photos');
        }

        $vendor = Vendor::create([
            'name' => $request->name,
            'description' => $request->description,
            'code' => $validatedData['code'],
            'status' => $validatedData['status'],
            'photo_url' => $photoUrl,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Vendor created successfully', 'data' => $vendor]);
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

    // Update the specified vendor
    public function update(Request $request, $vendorId)
    {

        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'code' => 'required|string|unique:vendors,code,' . $vendor->id, // Exclude the current vendor from the unique check
            'status' => 'required|string|max:255',
        ]);



        $photoUrl = $vendor->photo_url;
        if ($request->hasFile('photo')) {
            if ($photoUrl) {
                $photoPath = parse_url($photoUrl, PHP_URL_PATH);
                $photoPath = ltrim($photoPath, '/');
                if (file_exists(public_path($photoPath))) {
                    unlink(public_path($photoPath));
                }
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'vendor_photos');
        }

        $vendor->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'photo_url' => $photoUrl,
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Vendor updated successfully', 'data' => $vendor]);
    }

    // Remove the specified vendor
    public function destroy($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        if ($vendor->photo_url) {
            $photoPath = parse_url($vendor->photo_url, PHP_URL_PATH);
            $photoPath = ltrim($photoPath, '/');
            if (file_exists(public_path($photoPath))) {
                unlink(public_path($photoPath));
            }
        }

        $vendor->delete();

        return response()->json(null, 204);
    }
}