<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CarReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start building the query for CarReviews
        $query = CarReview::with(['car', 'createdBy', 'updatedBy']);

        // Check if 'car_id' is provided in the request to filter reviews
        if ($request->has('car_id')) {
            $carId = $request->input('car_id');
            $query->where('car_id', $carId);
        }

        // Execute the query to get reviews
        $reviews = $query->get();

        // if ($reviews->isEmpty()) {
        //     return response()->json(['message' => 'Review not found'], 404);
        // }

        return response()->json(['data' => $reviews]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Check if the user has already reviewed this car
        $existingReview = CarReview::where('car_id', $validatedData['car_id'])
            ->where('created_by', Auth::id())
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this car.'], 409);
        }

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $review = CarReview::create($validatedData);
        return response()->json($review, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $review = CarReview::with(['car', 'createdBy', 'updatedBy'])->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $review = CarReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Car review not found'], 404);
        }

        $validatedData = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $review->update($validatedData);
        return response()->json($review, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $carReview = CarReview::find($id);

        if (!$carReview) {
            return response()->json(['message' => 'Car review not found'], 404);
        }

        $carReview->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
    }

    public function get_car_reviews(string $id)
    {
        $review = CarReview::with(['car'])->where('car_id', $id)->get();

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }
}