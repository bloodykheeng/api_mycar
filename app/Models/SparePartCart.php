<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePartCart extends Model
{
    use HasFactory;

    protected $table = 'spare_part_carts'; // Explicitly defining the table name is optional if it follows Laravel's naming convention

    protected $fillable = ['spare_part_id', 'selected_quantity', 'price', 'created_by', 'updated_by'];

    /**
     * Get the spare part associated with the cart.
     */
    public function sparePart()
    {
        return $this->belongsTo(SparePart::class, 'spare_part_id');
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
