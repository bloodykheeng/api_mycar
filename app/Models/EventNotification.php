<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventNotification extends Model
{
    use HasFactory;
    protected $fillable = ['message', 'total_subscribers', 'created_by', 'updated_by'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function subscribers()
    {
        return $this->belongsToMany(EventSubscriber::class, 'event_notification_event_subscriber', 'event_notification_id', 'event_subscriber_id');
    }
}