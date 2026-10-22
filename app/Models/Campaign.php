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

    public function promotions()
    {
        return $this->hasMany(CampaignPromotion::class)->latest('starts_at');
    }

    /**
     * Whole days until the end date, counted in the users' local time; null if
     * there is no end date or it has passed. 0 means today is the last day.
     */
    public function daysLeft(): ?int
    {
        if (!$this->end_date) {
            return null;
        }
        $tz = config('app.display_timezone');
        $today = now($tz)->startOfDay();
        $end = \Carbon\Carbon::parse($this->end_date->format('Y-m-d'), $tz)->startOfDay();

        return $end->lt($today) ? null : (int) round($today->diffInDays($end));
    }

    /** People who donated (or pledged) in the last 7 days; cancelled ones don't count. */
    public function donorsThisWeek(): int
    {
        return $this->donations()
            ->where('status', '!=', 'cancelled')
            ->where(fn($q) => $q->where('donated_at', '>=', now()->subDays(7))
                ->orWhere(fn($q) => $q->whereNull('donated_at')->where('created_at', '>=', now()->subDays(7))))
            ->distinct()
            ->count('donor_id');
    }

    /** The promotion running right now, if any (at most one per campaign). */
    public function activePromotion()
    {
        return $this->hasOne(CampaignPromotion::class)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }
}