<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'comment',
        'rating',
        'created_by',
        'updated_by',
    ];

    // Define the relationships
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}