<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SparePartType;
use Illuminate\Support\Facades\File;

class SparePartTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sparePartTypes = SparePartType::with(['createdBy', 'updatedBy'])->get();
        return response()->json($sparePartTypes);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'spare_part_type_photos'); // Save the photo in a specific folder
            $validated['photo_url'] = $photoUrl;
        }

        $sparePartType = SparePartType::create($validated);
        return response()->json($sparePartType, 201);
    }

    public function show($id)
    {

        $sparePartType = SparePartType::with(['createdBy', 'updatedBy'])->find($id);

        if (!$sparePartType) {
            return response()->json(['message' => 'Spare Part Type not found'], 404);
        }

        return response()->json($sparePartType);
    }

    public function update(Request $request, $id)
    {
        $sparePartType = SparePartType::find($id);
        if (!$sparePartType) {
            return response()->json(['message' => 'Spare Part Type not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
        ]);

        $validated['updated_by'] = Auth::id();

        $photoUrl = $sparePartType->photo_url;
        if ($request->hasFile('photo')) {
            // Delete old photo if it exists
            if ($photoUrl) {
                $this->deletePhoto($photoUrl);
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'spare_part_type_photos');
            $validated['photo_url'] = $photoUrl;
        }

        $sparePartType->update($validated);
        return response()->json($sparePartType);
    }

    public function destroy($id)
    {
        $sparePartType = SparePartType::find($id);

        if (!$sparePartType) {
            return response()->json(['message' => 'Spare Part Type not found'], 404);
        }

        $sparePartType->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
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
