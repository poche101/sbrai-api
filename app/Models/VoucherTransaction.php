<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTransaction extends Model
{
    protected $fillable = [
        'vendor_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'ad_id'
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
