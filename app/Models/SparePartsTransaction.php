<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePartsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone_number',
        'transaction_id',
        'tx_ref',
        'flw_ref',
        'currency',
        'amount',
        'charged_amount',
        'charge_response_code',
        'charge_response_message',
        'gateway_created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(SparePartsTransactionsProduct::class, 'spare_parts_transactions_id');
    }
}
