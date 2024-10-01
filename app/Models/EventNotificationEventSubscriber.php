<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventNotificationEventSubscriber extends Model
{
    use HasFactory;

    protected $table = 'event_notification_event_subscriber';
    protected $fillable = ['event_notification_id', 'event_subscriber_id'];
}