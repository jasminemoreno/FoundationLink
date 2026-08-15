<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'foundation_id',
        'category_id',
        'created_by',
        'title',
        'description',
        'cover_photo',
        'type',
        'goal_amount',
        'current_amount',
        'start_date',
        'end_date',
        'status',
        'pause_reason',
        'accepted_delivery_methods',
        'accepted_payment_methods',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'goal_amount' => 'float',
        'current_amount' => 'float',
        'accepted_delivery_methods' => 'array',
        'accepted_payment_methods' => 'array',
    ];

    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos()
    {
        return $this->hasMany(CampaignPhoto::class)->orderBy('order');
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function monetaryDonations()
    {
        return $this->hasMany(Donation::class)->where('type', 'monetary');
    }

    public function itemDonations()
    {
        return $this->hasMany(Donation::class)->where('type', 'item');
    }

    public function updates()
    {
        return $this->hasMany(CampaignUpdate::class)->latest();
    }
}