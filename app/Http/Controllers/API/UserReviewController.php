<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start building the query for UserReviews
        $query = UserReview::with(['user', 'createdBy', 'updatedBy']);

        // Check if 'user_id' is provided in the request to filter reviews
        if ($request->has('user_id')) {
            $userId = $request->input('user_id');
            $query->where('user_id', $userId);
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
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Check if the user has already reviewed this user
        $existingReview = UserReview::where('user_id', $validatedData['user_id'])
            ->where('created_by', Auth::id())
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this user.'], 409);
        }

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $review = UserReview::create($validatedData);
        return response()->json($review, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $review = UserReview::with(['user', 'createdBy', 'updatedBy'])->find($id);

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
        $review = UserReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
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
        $userReview = UserReview::find($id);

        if (!$userReview) {
            return response()->json(['message' => 'User Review not found'], 404);
        }

        $userReview->delete();

        return response()->json(null, 204); // No content to indicate successful deletion
    }

    public function get_user_reviews(string $id)
    {
        $review = UserReview::with(['user'])->where('user_id', $id)->get();

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }
}