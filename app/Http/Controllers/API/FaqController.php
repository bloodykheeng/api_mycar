<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Faq::query();

        // Search by question or answer
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                    ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }

        // Filter by guard
        if ($request->has('guard')) {
            $guard = $request->input('guard');
            $query->where('guard', $guard);
        }

        // Filter by created_by
        if ($request->has('created_by')) {
            $createdBy = $request->input('created_by');
            $query->where('created_by', $createdBy);
        }

        // Filter by updated_by
        if ($request->has('updated_by')) {
            $updatedBy = $request->input('updated_by');
            $query->where('updated_by', $updatedBy);
        }

        // Pagination (optional)
        // $faqs = $query->paginate(10);

        $faqs = $query->get();

        return response()->json(["data" => $faqs]);
    }

    public function show($id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }
        return response()->json($faq);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guard' => 'required|string',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::create([
            'guard' => $validated['guard'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'created_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'FAQ created successfully', 'data' => $faq], 201);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $validated = $request->validate([
            'guard' => 'sometimes|string',
            'question' => 'sometimes|string',
            'answer' => 'sometimes|string',
        ]);

        $faq->update([
            'guard' => $validated['guard'] ?? $faq->guard,
            'question' => $validated['question'] ?? $faq->question,
            'answer' => $validated['answer'] ?? $faq->answer,
            'updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'FAQ updated successfully', 'data' => $faq]);
    }

    public function destroy($id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $faq->delete();

        return response()->json(['message' => 'FAQ deleted successfully']);
    }
}