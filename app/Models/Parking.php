<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Parking extends Model
{
    protected $fillable = [
        'car_id',
        'parking_fee_id',
        'vendor_id',
        'start_date',
        'end_date',
        'details',
        'created_by',
        'updated_by'
    ];

    // Define relationships
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function parkingFee()
    {
        return $this->belongsTo(ParkingFee::class, 'parking_fee_id');
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

    // Other model methods...

    protected $appends = ['parking_charge', 'status']; // Add the custom attribute to the model's array form

    // Add a custom accessor for status
    public function getStatusAttribute()
    {
        $now = Carbon::now();
        $startDate = Carbon::parse($this->start_date);

        if ($startDate->isFuture()) {
            return 'future';
        } elseif ($startDate->isPast() && (!$this->end_date || Carbon::parse($this->end_date)->isFuture())) {
            return 'active';
        }

        return 'deactive';
    }
    public function getParkingChargeAttribute()
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : Carbon::now();

        // Access the fee_amount from the related ParkingFee model
        $feePerDay = $this->parkingFee->fee_amount ?? 0;

        if (!$this->end_date) {
            $endDate = Carbon::now();
        }

        $daysParked = (int) $startDate->diffInDays($endDate);

        // If the start date is in the future or there is no fee set, return 0 to prevent negative charges
        if ($startDate->isFuture() || $feePerDay == 0) {
            return 0;
        }

        return $daysParked * $feePerDay;
    }
}
