<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarInspectionFieldCategory extends Model
{
    use HasFactory;

    protected $table = 'car_inspection_field_categories';

    protected $fillable = [
        'name',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function inspectionFields()
    {
        return $this->hasMany(InspectionField::class, 'car_inspection_field_categories_id');
    }
}