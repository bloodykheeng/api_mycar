<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GarageReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GarageReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start building the query for GarageReviews
        $query = GarageReview::with(['garage', 'createdBy', 'updatedBy']);

        // Check if 'garage_id' is provided in the request to filter reviews
        if ($request->has('garage_id')) {
            $garageId = $request->input('garage_id');
            $query->where('garage_id', $garageId);
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
            'garage_id' => 'required|exists:garages,id',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Check if the user has already reviewed this garage
        $existingReview = GarageReview::where('garage_id', $validatedData['garage_id'])
            ->where('created_by', Auth::id())
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this garage.'], 409);
        }

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $review = GarageReview::create($validatedData);
        return response()->json($review, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $review = GarageReview::with(['garage', 'createdBy', 'updatedBy'])->find($id);

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
        $review = GarageReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Garage review not found'], 404);
        }

        $validatedData = $request->validate([
            'garage_id' => 'required|exists:garages,id',
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
        $garageReview = GarageReview::find($id);

        if (!$garageReview) {
            return response()->json(['message' => 'Garage review not found'], 404);
        }

        $garageReview->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
    }

    public function get_garage_reviews(string $id)
    {
        $review = GarageReview::with(['garage'])->where('garage_id', $id)->get();

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }
}