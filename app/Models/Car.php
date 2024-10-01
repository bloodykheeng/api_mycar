<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'make',
        'model',
        'transmission',
        'year',
        'mileage',
        'number_plate',
        'price',
        'color',
        'quantity',
        'visibility',
        'condition',
        'status',
        'identifier',
        'fuel_type',
        'drive_type',
        'engine_type',
        'vin',
        'car_brand_id',
        'car_type_id',
        'vendor_id',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['inspection_status', 'rating'];

    // Inspection status accessor
    public function getInspectionStatusAttribute()
    {
        if ($this->inspectionReport) {
            $latestStatus = $this->inspectionReport->latestStatus();
            if ($latestStatus) {
                switch ($latestStatus->name) {
                    case 'approved':
                        return 'approved';
                    case 'rejected':
                        return 'rejected';
                    default:
                        return 'inspected'; // Any status other than approved/rejected is considered as inspected
                }
            }
            return 'inspected'; // Assume inspected if there is a report but no status
        }
        return 'not inspected'; // Default status if no report is linked
    }

    public function reviews()
    {
        return $this->hasMany(CarReview::class);
    }

    // Accessor for the average rating
    public function getRatingAttribute()
    {
        return $this->reviews()->average('rating');
    }

    public function photos()
    {
        return $this->hasMany(CarPhoto::class, 'car_id');
    }

    public function inspectionReport()
    {
        return $this->hasOne(CarInspectionReport::class, 'car_id');
    }

    public function carInspector()
    {
        return $this->hasOne(CarInspector::class, 'car_id');
    }

    public function inspector()
    {
        return $this->hasOneThrough(
            User::class,
            CarInspector::class,
            'car_id', // Foreign key on CarInspector table
            'id', // Foreign key on User table
            'id', // Local key on Car table
            'inspector_id' // Local key on CarInspector table
        );
    }
    public function videos()
    {
        return $this->hasMany(CarVideo::class);
    }

    public function brand()
    {
        return $this->belongsTo(CarBrand::class, 'car_brand_id');
    }

    public function type()
    {
        return $this->belongsTo(CarType::class, 'car_type_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id'); // Define the relationship
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