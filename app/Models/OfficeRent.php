<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OfficeRent extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'start_date',
        'end_date',
        'vendor_id',
        'details',
        'created_by',
        'updated_by',
    ];

    // Relationship with Office
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    // Relationship with Vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Relationship with User (Creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with User (Updater)
    public function updater()
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
