<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignPromotion extends Model
{
    /** How long a foundation can choose to promote a campaign for. */
    public const DURATIONS = [3, 7, 14];

    /** Kinds of promotion, with the label shown to people. */
    public const TYPES = [
        'urgent' => 'Urgent need',
        'deadline' => 'Deadline',
        'milestone' => 'Milestone',
    ];

    protected $fillable = [
        'campaign_id',
        'created_by',
        'type',
        'message',
        'duration_days',
        'starts_at',
        'ends_at',
        'notified_count',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'duration_days' => 'integer',
        'notified_count' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /** Promotions running right now. */
    public function scopeRunning($query)
    {
        return $query->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->starts_at <= now() && $this->ends_at > now();
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? self::TYPES['urgent'];
    }
}
