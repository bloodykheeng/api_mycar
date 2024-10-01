<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarInspectionReportField extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_inspection_reports_id',
        'car_inspection_report_categories_id',
        'inspection_fields_id',
        'value',
        'created_by',
        'updated_by',
    ];

    public function report()
    {
        return $this->belongsTo(CarInspectionReport::class, 'car_inspection_reports_id');
    }
    public function category()
    {
        return $this->belongsTo(CarInspectionReportCategory::class, 'car_inspection_report_categories_id');
    }


    public function inspectionField()
    {
        return $this->belongsTo(InspectionField::class, 'inspection_fields_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}