<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'donor_id',
        'type',
        'amount',
        'payment_method_id',
        'proof_photo',
        'item_name',
        'item_quantity',
        'item_description',
        'item_photo',        // ← add this
        'delivery_method',
        'delivery_address',
        'status',
        'notes',
        'donated_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'donated_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}