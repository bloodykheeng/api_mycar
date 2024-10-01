<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarInspectionReportCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_inspection_reports_id',
        'car_inspection_field_categories_id',
        'created_by',
        'updated_by'
    ];

    public function carInspectionReport()
    {
        return $this->belongsTo(CarInspectionReport::class, 'car_inspection_reports_id');
    }

    public function inspectionFieldCategory()
    {
        return $this->belongsTo(CarInspectionFieldCategory::class, 'car_inspection_field_categories_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fields()
    {
        return $this->hasMany(CarInspectionReportField::class, 'car_inspection_report_categories_id');
    }
}