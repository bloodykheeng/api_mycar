<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Garage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'photo_url',
        'availability',
        'opening_hours',
        'special_features',
        'created_by',
        'updated_by',
    ];

    // Define the relationships
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reviews()
    {
        return $this->hasMany(GarageReview::class);
    }
    // Add 'rating' to the array or JSON form of the model
    protected $appends = ['rating'];
    // Accessor for the average rating
    public function getRatingAttribute()
    {
        return $this->reviews()->average('rating');
    }

    protected static function booted()
    {
        static::creating(function ($item) {
            $item->slug = static::uniqueSlug($item->name);
        });
    }

    public static function uniqueSlug($string)
    {
        $baseSlug = Str::slug($string, '-');
        if (static::where('slug', $baseSlug)->doesntExist()) {
            return $baseSlug;
        }

        $counter = 1;
        // Limiting the counter to prevent infinite loops
        while ($counter < 1000) {
            $slug = "{$baseSlug}-{$counter}";
            if (static::where('slug', $slug)->doesntExist()) {
                return $slug;
            }
            $counter++;
        }

        // Fallback if reached 1000 iterations (should ideally never happen)
        return "{$baseSlug}-" . uniqid();
    }
}