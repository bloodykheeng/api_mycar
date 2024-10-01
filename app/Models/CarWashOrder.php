<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarWashOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_wash_fee_id',
        'car_id',
        'start_date',
        'end_date',
        'vendor_id',
        'details',
        'created_by',
        'updated_by',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function carWashFee()
    {
        return $this->belongsTo(CarWashFee::class, 'car_wash_fee_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
