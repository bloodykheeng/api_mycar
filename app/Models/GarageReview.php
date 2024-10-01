<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'comment',
        'rating',
        'created_by',
        'updated_by',
    ];

    // Define the relationships
    public function garage()
    {
        return $this->belongsTo(Garage::class, 'garage_id');
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