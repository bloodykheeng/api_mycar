<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventNotification;
use App\Models\EventNotificationEventSubscriber;
use App\Models\EventSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EventNotificationController extends Controller
{
    public function index()
    {
        $notifications = EventNotification::with(['createdBy', 'updatedBy'])->get();

        return response()->json(['data' => $notifications]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $record = EventNotification::with(['createdBy', 'updatedBy'])->findOrFail($id);
        return $record;
    }

    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'message' => 'required|string',
            'total_subscribers' => 'nullable|integer|min:1',
        ]);

        $message = $validated['message'];
        $totalNoUsers = $validated['total_subscribers'] ?? null;

        DB::beginTransaction();

        try {
            // Create the EventNotification
            $eventNotification = EventNotification::create([
                'message' => $message,
                'total_subscribers' => $totalNoUsers,
                'created_by' => auth()->id(),
            ]);

            // Get subscribers for the event notification
            $subscribersQuery = EventSubscriber::query();
            if ($totalNoUsers) {
                $subscribersQuery->limit($totalNoUsers);
            }
            $subscribers = $subscribersQuery->get();

            if ($subscribers->isEmpty()) {
                return response()->json(['message' => 'No subscribers found'], 404);
            }

            // Create EventNotificationEventSubscriber records
            foreach ($subscribers as $subscriber) {
                EventNotificationEventSubscriber::create([
                    'event_notification_id' => $eventNotification->id,
                    'event_subscriber_id' => $subscriber->id,
                ]);

                // Send email to subscriber using the blade file
                Mail::send('emails.eventNotification', ['subscriber' => $subscriber, 'message' => $message], function ($message) use ($subscriber) {
                    $message->to($subscriber->email)->subject('Event Notification');
                });
            }

            DB::commit();

            return response()->json(['message' => 'Event notification created and emails sent successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create event notification and send emails: ' . $e->getMessage());

            return response()->json(['message' => 'Failed to create event notification and send emails'], 500);
        }
    }

    public function update(Request $request, EventNotification $notification)
    {
        // Validate the request
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = $validated['message'];

        DB::beginTransaction();

        try {
            // Update the EventNotification
            $notification->update(['message' => $message, 'updated_by' => auth()->id()]);

            // Get subscribers for the event notification
            $subscribersQuery = EventSubscriber::query();
            if ($notification->total_subscribers) {
                $subscribersQuery->limit($notification->total_subscribers);
            }
            $subscribers = $subscribersQuery->get();

            if ($subscribers->isEmpty()) {
                return response()->json(['message' => 'No subscribers found'], 404);
            }

            // Update EventNotificationEventSubscriber records
            foreach ($subscribers as $subscriber) {
                EventNotificationEventSubscriber::updateOrCreate(
                    ['event_notification_id' => $notification->id, 'event_subscriber_id' => $subscriber->id],
                    ['event_notification_id' => $notification->id, 'event_subscriber_id' => $subscriber->id]
                );

                // Send email to subscriber using the blade file
                Mail::send('emails.eventNotification', ['subscriber' => $subscriber, 'message' => $message], function ($message) use ($subscriber) {
                    $message->to($subscriber->email)->subject('Event Notification');
                });
            }

            DB::commit();

            return response()->json(['message' => 'Event notification updated and emails sent successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update event notification and send emails: ' . $e->getMessage());

            return response()->json(['message' => 'Failed to update event notification and send emails'], 500);
        }
    }

    public function destroy(EventNotification $notification)
    {
        DB::beginTransaction();

        try {
            // Delete the EventNotification
            $notification->delete();

            // Also delete associated EventNotificationEventSubscriber records
            EventNotificationEventSubscriber::where('event_notification_id', $notification->id)->delete();

            DB::commit();

            return response()->json(['message' => 'Event notification deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to delete event notification'], 500);
        }
    }
}