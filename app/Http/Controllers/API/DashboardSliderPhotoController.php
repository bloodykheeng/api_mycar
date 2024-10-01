<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DashboardSliderPhoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class DashboardSliderPhotoController extends Controller
{
    /**
     * Display a listing of the dashboard slider photos.
     */
    public function index()
    {
        $photos = DashboardSliderPhoto::with(['creator', 'updater'])->get();
        return response()->json($photos);
    }

    /**
     * Store a newly created dashboard slider photo.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'status' => 'required|string',
            'caption' => 'nullable|string',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'dashboard_slider_photos');
        }

        $photo = DashboardSliderPhoto::create([
            'photo_url' => $photoUrl,
            'title' => $validatedData['title'],
            'status' => $validatedData['status'],
            'caption' => $validatedData['caption'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Photo created successfully', 'data' => $photo]);
    }

    /**
     * Display the specified dashboard slider photo.
     */
    public function show($id)
    {
        $photo = DashboardSliderPhoto::with(['creator', 'updater'])->find($id);
        if (!$photo) {
            return response()->json(['message' => 'Photo not found'], 404);
        }
        return response()->json($photo);
    }

    /**
     * Update the specified dashboard slider photo.
     */
    public function update(Request $request, $id)
    {
        $photo = DashboardSliderPhoto::find($id);
        if (!$photo) {
            return response()->json(['message' => 'Photo not found'], 404);
        }

        $validatedData = $request->validate([
            'title' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'status' => 'required|string',
            'caption' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if it exists and upload new one
            if ($photo->photo_url) {
                $this->deletePhoto($photo->photo_url);
            }
            $photo->photo_url = $this->uploadPhoto($request->file('photo'), 'dashboard_slider_photos');
        }

        $photo->caption = $validatedData['caption'];
        $photo->updated_by = Auth::id();
        $photo->save();

        return response()->json(['message' => 'Photo updated successfully', 'data' => $photo]);
    }

    /**
     * Remove the specified dashboard slider photo.
     */
    public function destroy($id)
    {
        $photo = DashboardSliderPhoto::find($id);
        if (!$photo) {
            return response()->json(['message' => 'Photo not found'], 404);
        }

        if ($photo->photo_url) {
            $this->deletePhoto($photo->photo_url);
        }

        $photo->delete();
        return response()->json(['message' => 'Photo deleted successfully']);
    }

    /**
     * Helper method to upload a photo.
     */
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

    /**
     * Helper method to delete a photo.
     */
    private function deletePhoto($photoUrl)
    {
        $photoPath = parse_url($photoUrl, PHP_URL_PATH);
        $photoPath = public_path($photoPath);
        if (File::exists($photoPath)) {
            File::delete($photoPath);
        }
    }
}
