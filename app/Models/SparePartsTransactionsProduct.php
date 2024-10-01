<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePartsTransactionsProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'spare_parts_id',
        'spare_parts_transactions_id',
        'quantity',
        'created_by',
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class, 'spare_parts_id');
    }

    public function transaction()
    {
        return $this->belongsTo(SparePartsTransaction::class, 'spare_parts_transactions_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
