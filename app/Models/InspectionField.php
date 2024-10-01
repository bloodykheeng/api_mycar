<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionField extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'field_type',
        'status',
        'description',
        'car_inspection_field_categories_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the user who created the inspection field.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the inspection field.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function category()
    {
        return $this->belongsTo(CarInspectionFieldCategory::class, 'car_inspection_field_categories_id');
    }
}