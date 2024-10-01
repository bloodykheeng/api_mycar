<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MotorThirdParty;
use Illuminate\Support\Facades\File;

class MotorThirdPartyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // Display a listing of Motor Third Partys
    public function index(Request $request)
    {
        // Start building the query
        $query = MotorThirdParty::with(['createdBy', 'updatedBy']);

        // Order the results by the created_at column in descending order (latest first)
        // $query->latest();

        // Apply filters from request
        if (!empty($request->search)) { // Check if search is not null and not an empty string
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Execute the query after conditions are applied
        $motorThirdParties = $query->get();

        return response()->json($motorThirdParties);
    }


    // Store a newly created Motor Third Party
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoUrl = null;
        if ($request->hasFile('logo')) {
            $photoUrl = $this->uploadPhoto($request->file('logo'), 'motor_third_party_photos');
        }

        $motorThirdParty = MotorThirdParty::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'logo_url' => $photoUrl,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Motor Third Party created successfully', 'data' => $motorThirdParty]);
    }

    // Display the specified Motor Third Party
    public function show($id)
    {
        $motorThirdParty = MotorThirdParty::with(['createdBy', 'updatedBy'])->find($id);

        if (!$motorThirdParty) {
            return response()->json(['message' => 'Motor Third Party not found'], 404);
        }

        return response()->json($motorThirdParty);
    }

    // Update the specified Motor Third Party
    public function update(Request $request, $id)
    {
        $motorThirdParty = MotorThirdParty::find($id);
        if (!$motorThirdParty) {
            return response()->json(['message' => 'Motor Third Party not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoUrl = $motorThirdParty->logo_url;
        if ($request->hasFile('logo')) {
            // Delete old photo if it exists
            if ($photoUrl) {
                $this->deletePhoto($photoUrl);
            }
            $photoUrl = $this->uploadPhoto($request->file('logo'), 'motor_third_party_photos');
        }

        $motorThirdParty->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'logo_url' => $photoUrl,
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Motor Third Party updated successfully', 'data' => $motorThirdParty]);
    }

    // Remove the specified Motor Third Party
    public function destroy($id)
    {
        $motorThirdParty = MotorThirdParty::find($id);

        if (!$motorThirdParty) {
            return response()->json(['message' => 'Motor Third Party not found'], 404);
        }

        // Delete associated photo file
        if ($motorThirdParty->logo_url) {
            $this->deletePhoto($motorThirdParty->logo_url);
        }

        $motorThirdParty->delete();

        return response()->json(['message' => 'Motor Third Party deleted successfully']);
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