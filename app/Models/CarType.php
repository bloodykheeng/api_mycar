<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CarType extends Model
{
    use HasFactory;

    // Define the table if it's not the standard naming convention
    protected $table = 'car_types';

    // Specify the fields that are mass assignable
    protected $fillable = [
        'name',
        'description',
        'status',
        'photo_url',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the user that created the product type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that last updated the product type.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Add other relationships here as needed
    protected static function booted()
    {
        static::creating(function ($car) {
            $car->slug = static::uniqueSlug($car->name);
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
