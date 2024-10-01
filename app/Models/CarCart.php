<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarCart extends Model
{
    use HasFactory;

    protected $table = 'car_carts';

    // Specify the fields that are mass assignable
    protected $fillable = ['car_id', 'selected_quantity', 'price', 'created_by', 'updated_by'];

    /**
     * Get the car associated with the cart.
     */
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    /**
     * Get the user who created the cart.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the cart.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
