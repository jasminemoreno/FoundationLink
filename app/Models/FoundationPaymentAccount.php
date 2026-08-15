<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoundationPaymentAccount extends Model
{
    protected $fillable = [
        'foundation_id',
        'payment_method_id',
        'account_name',
        'account_number',
    ];

    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}