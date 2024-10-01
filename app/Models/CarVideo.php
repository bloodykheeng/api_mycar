<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'video_url',
        'caption',
        'created_by',
        'updated_by'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    // If you have User model relationships for created_by and updated_by
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
