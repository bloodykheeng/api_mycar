<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SparePartReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SparePartReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start building the query for SparePartReviews
        $query = SparePartReview::with(['sparePart', 'createdBy', 'updatedBy']);

        // Check if 'spare_part_id' is provided in the request to filter reviews
        if ($request->has('spare_part_id')) {
            $sparePartId = $request->input('spare_part_id');
            $query->where('spare_part_id', $sparePartId);
        }

        // Execute the query to get reviews
        $reviews = $query->get();

        // if ($reviews->isEmpty()) {
        //     return response()->json(['message' => 'Review not found'], 404);
        // }

        return response()->json(['data' => $reviews]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Check if the user has already reviewed this spare part
        $existingReview = SparePartReview::where('spare_part_id', $validatedData['spare_part_id'])
            ->where('created_by', Auth::id())
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this spare part.'], 409);
        }

        // return response()->json(['message' => 'testing', 'data' => Auth::id()], 404);

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $review = SparePartReview::create($validatedData);
        return response()->json($review, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $review = SparePartReview::with(['sparePart', 'createdBy', 'updatedBy'])->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $review = SparePartReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Spare part not found'], 404);
        }

        $validatedData = $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
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
        $sparePartReview = SparePartReview::find($id);

        if (!$sparePartReview) {
            return response()->json(['message' => 'Spare Part Review not found'], 404);
        }

        $sparePartReview->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
    }

    public function get_spare_part_reviews(string $id)
    {
        $review = SparePartReview::with(['user'])->where('spare_part_id', $id)->get();

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }
}