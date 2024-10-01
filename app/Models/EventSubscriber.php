<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'name', 'phone_number'];

    public function notifications()
    {
        return $this->belongsToMany(EventNotification::class, 'event_notification_event_subscriber', 'event_subscriber_id', 'event_notification_id');
    }

    public function setEmailAttribute($value)
    {
        if (empty($value)) { // will check for empty string
            $this->attributes['email'] = null;
        } else {
            $this->attributes['email'] = $value;
        }
    }

    public function setPhoneNumberAttribute($value)
    {
        if (empty($value)) { // will check for empty string
            $this->attributes['phone_number'] = null;
        } else {
            $this->attributes['phone_number'] = $value;
        }
    }
}