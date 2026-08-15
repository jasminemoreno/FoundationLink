<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FoundationPaymentAccount;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function foundationAccounts()
    {
        return $this->hasMany(FoundationPaymentAccount::class);
    }
}