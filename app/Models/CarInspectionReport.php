<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStatus\HasStatuses;

class CarInspectionReport extends Model
{
    use HasFactory, HasStatuses;

    protected $fillable = [
        'name',
        'details',
        'car_id',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['spatie_current_status'];

    /**
     * Get the current status as an array with name, reason, and formatted creation date.
     *
     * @return array|null
     */
    public function getSpatieCurrentStatusAttribute()
    {
        $latestStatus = $this->latestStatus();
        return $latestStatus ? [
            'name' => $latestStatus->name,
            'reason' => $latestStatus->reason,
            'created_at' => $latestStatus->created_at->format('Y-m-d H:i:s') // Ensure the date format is readable
        ] : null;
    }


    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    /**
     * Get the user who created the report.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the report.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }



    public function carInspectionReportCategory()
    {
        return $this->hasMany(CarInspectionReportCategory::class, 'car_inspection_reports_id');
    }
}