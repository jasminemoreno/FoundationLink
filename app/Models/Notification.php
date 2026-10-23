<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'notifiable_id',
        'notifiable_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        // Also push it to the user's phone(s) once the request is done
        static::created(fn(Notification $n) => app(\App\Support\ExpoPush::class)->queue($n));
    }

    /**
     * The donor page this notification opens (web path; the mobile app maps it
     * to its own screens). Campaign updates live on the foundation's page:
     * pass $updateFoundationIds (update id => foundation id) when building a
     * list, so it is one query instead of one per notification.
     */
    public function donorLink($updateFoundationIds = null): ?string
    {
        if ($this->type === 'campaign_update' && $updateFoundationIds === null) {
            $updateFoundationIds = CampaignUpdate::where('campaign_updates.id', $this->notifiable_id)
                ->join('campaigns', 'campaigns.id', '=', 'campaign_updates.campaign_id')
                ->pluck('campaigns.foundation_id', 'campaign_updates.id');
        }

        return match ($this->type) {
            'new_campaign', 'campaign_promotion' => '/donor/campaigns?tab=new&highlight=' . $this->notifiable_id,
            'campaign_update' => isset($updateFoundationIds[$this->notifiable_id])
                ? '/donor/foundations/' . $updateFoundationIds[$this->notifiable_id]
                : null,
            'donation_received', 'donation_cancelled' => '/donor/donations?highlight=' . $this->notifiable_id,
            default => null,
        };
    }
    public static function notifySuperadmins(string $type, string $title, ?string $message = null, $notifiable = null)
    {
        $superadminIds = User::where('role', 'superadmin')->pluck('id');

        foreach ($superadminIds as $id) {
            self::create([
                'user_id' => $id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'notifiable_type' => $notifiable ? get_class($notifiable) : self::class,
                'notifiable_id' => $notifiable?->id ?? 0,
            ]);
        }
    }
}