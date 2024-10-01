<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'photo_url',
        'description',
        'price',
        'condition',
        'identifier',
        'quantity',
        'approval_status',
        'spare_part_type_id',
        'vendor_id',
        'created_by',
        'updated_by',
    ];

    // Define the relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviews()
    {
        return $this->hasMany(SparePartReview::class);
    }
    // Add 'rating' to the array or JSON form of the model
    protected $appends = ['rating'];
    // Accessor for the average rating
    public function getRatingAttribute()
    {
        return $this->reviews()->average('rating');
    }

    public function sparePartType()
    {
        return $this->belongsTo(SparePartType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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