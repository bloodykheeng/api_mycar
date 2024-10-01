<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVendor extends Model
{
    protected $table = 'user_vendor';

    protected $fillable = ['user_id', 'vendor_id', 'created_by', 'updated_by'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
