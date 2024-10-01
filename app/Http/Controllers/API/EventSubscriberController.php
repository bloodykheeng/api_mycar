<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventSubscriber;
use Illuminate\Http\Request;

class EventSubscriberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EventSubscriber::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:event_subscribers',
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
        ]);

        $eventSubscriber = EventSubscriber::create($validated);

        return response()->json($eventSubscriber, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $eventSubscriber = EventSubscriber::findOrFail($id);
        return $eventSubscriber;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $eventSubscriber = EventSubscriber::findOrFail($id);

        $validated = $request->validate([
            'email' => 'required|email|unique:event_subscribers,email,' . $eventSubscriber->id,
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
        ]);

        $eventSubscriber->update($validated);

        return response()->json($eventSubscriber, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $eventSubscriber = EventSubscriber::findOrFail($id);
        $eventSubscriber->delete();

        return response()->json(null, 204);
    }
}