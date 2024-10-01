<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\CarVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CarController extends Controller
{
    public function index(Request $request)
    {
        // Start building the query
        $query = Car::with([
            'brand', 'photos', 'videos', 'type', 'reviews', 'vendor', 'createdBy', 'updatedBy', 'inspector', 'carInspector.inspector',
            'inspectionReport' => function ($query) {
                $query->with([
                    'CarInspectionReportCategory' => function ($query) {
                        $query->with([
                            'inspectionFieldCategory',
                            'fields' => function ($query) {
                                $query->with([
                                    'inspectionField',
                                    'creator',
                                ]);
                            },
                        ]);
                    },
                    'car',
                    'creator',
                    'updater',
                ]);
            },
        ]);

        // Order the results by the created_at column in descending order (latest first)
        // $query->orderBy('created_at', 'desc');

        // Get the currently authenticated user
        /** @var \App\Models\User */
        // $user = Auth::user();

        // Attempt to get the authenticated user if one is available
        $user = Auth::guard('sanctum')->user();

        // Check if the user has the 'Vendor' role and apply the filter this user carInspector relation
        if (isset($user) && $user->hasRole('Vendor')) {
            // Assuming the UserVendor model defines the relationship to get the vendor id
            $vendorId = $user->vendors->vendor_id ?? null;
            if ($vendorId) {
                $query->where('vendor_id', $vendorId);
            }
        }

        // Check if the user has the 'Inspector' role and filter cars he is attached to

        if (isset($user) && $user->hasRole('Inspector')) {
            // Filter to get the cars associated with this inspector
            $query->whereHas('carInspector', function ($q) use ($user) {
                $q->where('inspector_id', $user->id);
            });
        }

        // Check if the user has the 'Inspector' role and filter cars he is attached to theis isere inspector relation
        // if ($user && $user->hasRole('Inspector')) {
        //     // Get the cars associated with this inspector
        //     $query->whereHas('inspector', function ($q) use ($user) {
        //         $q->where('id', $user->id);
        //     });
        // }

        // Apply filters from request
        if (!empty($request->search)) { // Check if search is not null and not an empty string
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // if (!empty($request->inspection_status)) {
        //     if ($request->inspection_status == 'inspected') {
        //         $query->whereHas('inspectionReport');
        //     } elseif ($request->inspection_status == 'not_inspected') {
        //         $query->whereDoesntHave('inspectionReport');
        //     }
        // }

        if (!empty($request->user_id)) { // Check if search is not null and not an empty string
            $query->where('created_by', $request->user_id);
        }

        if (!empty($request->visibility)) { // Check if search is not null and not an empty string
            $query->where('visibility', $request->visibility);
        }

        if (!empty($request->condition)) { // Check if condition is not null and not an empty string
            $query->where('condition', $request->condition);
        }

        if (!empty($request->maxPrice)) { // Check if maxPrice is not null and not an empty string
            $query->where('price', '<=', $request->maxPrice);
        }

        if (!empty($request->car_type)) { // Check if car_type is not null and not an empty string
            // Assuming `car_type` is the slug of the type
            $query->whereHas('type', function ($q) use ($request) {
                $q->where('slug', $request->car_type);
            });
        }

        // New filters
        if (!empty($request->fuel_type)) {
            $query->where('fuel_type', $request->fuel_type);
        }

        if (!empty($request->drive_type)) {
            $query->where('drive_type', $request->drive_type);
        }

        if (!empty($request->engine_type)) {
            $query->where('engine_type', $request->engine_type);
        }

        if (!empty($request->vin)) {
            $query->where('vin', $request->vin);
        }

        if (!empty($request->identifier)) {
            $query->where('identifier', $request->identifier);
        }

        //get by latest
        $query->latest();

        // Execute the query and get the results
        $cars = $query->get();

        // Filter cars by inspection status based on the value from the request
        if (!empty($request->inspection_status)) {
            $cars = $cars->filter(function ($car) use ($request) {
                return $car->inspection_status === $request->inspection_status;
            });
        }

        return response()->json($cars);
    }

    // New function to get car by slug
    public function getBySlug($slug)
    {
        // Retrieve the car along with related details
        $car = Car::with([
            'brand', 'photos', 'videos', 'type', 'reviews', 'vendor', 'createdBy', 'updatedBy', 'inspector', 'carInspector.inspector',
            'inspectionReport' => function ($query) {
                $query->with([
                    'CarInspectionReportCategory' => function ($query) {
                        $query->with([
                            'inspectionFieldCategory',
                            'fields' => function ($query) {
                                $query->with([
                                    'inspectionField',
                                    'creator',
                                ]);
                            },
                        ]);
                    },
                    'car',
                    'creator',
                    'updater',
                ]);
            },
        ])

            ->where('slug', $slug)
            ->first();

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        return response()->json($car);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'make' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'mileage' => 'nullable|integer',
            'number_plate' => 'nullable|string|max:255',
            'price' => 'required|numeric',
            'color' => 'nullable|string|max:255',
            // 'quantity' => 'required|integer',
            'visibility' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'car_brand_id' => 'required|exists:car_brands,id',
            'car_type_id' => 'required|exists:car_types,id',
            'vendor_id' => 'required|exists:vendors,id',
            'fuel_type' => 'nullable|string|max:255', // Adding fuel type validation
            'drive_type' => 'nullable|string|max:255', // Adding drive type validation
            'engine_type' => 'nullable|string|max:255', // Adding engine type validation
            'vin' => 'nullable|string|max:255|unique:cars,vin', // Adding vin validation
            'identifier' => 'nullable|string|max:255|unique:cars,identifier', // Adding identifier validation
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        DB::beginTransaction();
        try {
            $car = Car::create($validated);

            // Handle Photos
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $imageCaptions = $request->input('imageCaptions', []);

                foreach ($images as $index => $image) {
                    $photoUrl = $this->uploadPhoto($image, 'car_photos');
                    $caption = $imageCaptions[$index] ?? '';

                    CarPhoto::create([
                        'car_id' => $car->id,
                        'photo_url' => $photoUrl,
                        'caption' => $caption,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            // Handle Videos
            if ($request->hasFile('videos')) {
                $videos = $request->file('videos');
                $videoCaptions = $request->input('videoCaptions', []);

                foreach ($videos as $index => $video) {
                    $videoUrl = $this->uploadPhoto($video, 'car_videos'); // Adjust if necessary for video upload
                    $caption = $videoCaptions[$index] ?? '';

                    CarVideo::create([
                        'car_id' => $car->id,
                        'video_url' => $videoUrl,
                        'caption' => $caption,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Car created successfully', 'data' => $car]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create car: ' . $e->getMessage()], 500);
        }
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

    public function show($id)
    {
        $car = Car::with([
            'brand', 'type', 'photos', 'reviews', 'videos', 'vendor', 'createdBy', 'updatedBy', 'inspector', 'carInspector.inspector',
            'inspectionReport' => function ($query) {
                $query->with([
                    'CarInspectionReportCategory' => function ($query) {
                        $query->with([
                            'inspectionFieldCategory',
                            'fields' => function ($query) {
                                $query->with([
                                    'inspectionField',
                                    'creator',
                                ]);
                            },
                        ]);
                    },
                    'car',
                    'creator',
                    'updater',
                ]);
            },
        ])->find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        return response()->json($car);
    }

    public function update(Request $request, $id)
    {
        $car = Car::with('photos', 'videos')->find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'make' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'mileage' => 'nullable|integer',
            'number_plate' => 'nullable|string|max:255',
            'price' => 'required|numeric',
            'color' => 'nullable|string|max:255',
            // 'quantity' => 'required|integer',
            'visibility' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'car_brand_id' => 'required|exists:car_brands,id',
            'car_type_id' => 'required|exists:car_types,id',
            'vendor_id' => 'required|exists:vendors,id',
            'fuel_type' => 'nullable|string|max:255', // Adding fuel type validation
            'drive_type' => 'nullable|string|max:255', // Adding drive type validation
            'engine_type' => 'nullable|string|max:255', // Adding engine type validation
            'vin' => 'nullable|string|max:255|unique:cars,vin,' . $car->id, // Adding vin validation, allowing for the same VIN if it belongs to the current car
            'identifier' => 'nullable|string|max:255|unique:cars,identifier,' . $car->id, // Adding identifier validation, allowing for the same identifier if it belongs to the current car
        ]);

        $validated['updated_by'] = Auth::id();

        DB::beginTransaction();
        try {
            $car->update($validated);

            // Delete existing photos and videos, handle uploads within transaction
            $this->deleteAndUploadMedia($car, $request);

            DB::commit();
            return response()->json(['message' => 'Car updated successfully', 'data' => $car]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update car: ' . $e->getMessage()], 500);
        }
    }

    private function deleteAndUploadMedia($car, $request)
    {

        // Handle new photo uploads
        if ($request->hasFile('images')) {
            // Delete existing photos
            foreach ($car->photos as $photo) {
                $this->deleteFile($photo->photo_url);
                $photo->delete();
            }

            $images = $request->file('images');
            $imageCaptions = $request->input('imageCaptions', []);

            foreach ($images as $index => $image) {
                $photoUrl = $this->uploadPhoto($image, 'car_photos');
                $caption = $imageCaptions[$index] ?? '';

                CarPhoto::create([
                    'car_id' => $car->id,
                    'photo_url' => $photoUrl,
                    'caption' => $caption,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        }

        // Handle new video uploads
        if ($request->hasFile('videos')) {

            // Delete existing videos
            foreach ($car->videos as $video) {
                $this->deleteFile($video->video_url);
                $video->delete();
            }

            $videos = $request->file('videos');
            $videoCaptions = $request->input('videoCaptions', []);

            foreach ($videos as $index => $video) {
                $videoUrl = $this->uploadPhoto($video, 'car_videos'); // Adjust if necessary for video upload
                $caption = $videoCaptions[$index] ?? '';

                CarVideo::create([
                    'car_id' => $car->id,
                    'video_url' => $videoUrl,
                    'caption' => $caption,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }

    private function deleteFile($filePath)
    {
        $path = parse_url($filePath, PHP_URL_PATH);
        $absolutePath = public_path($path);

        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    }

    public function destroy($id)
    {
        $car = Car::with('photos', 'videos')->find($id);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        // Delete associated photos
        foreach ($car->photos as $photo) {
            $this->deleteFile($photo->photo_url);
            $photo->delete();
        }

        // Delete associated videos
        foreach ($car->videos as $video) {
            $this->deleteFile($video->video_url);
            $video->delete();
        }

        $car->delete();

        return response()->json(null, 204);
    }
}