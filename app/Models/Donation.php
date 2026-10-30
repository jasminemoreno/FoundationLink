<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'pickup_latitude',   // optional exact pick-up spot (GPS), Pick Up only
        'pickup_longitude',
        'pickup_accuracy',   // metres
        'pickup_date',       // pick-up schedule set by the foundation (see pickupSchedule())
        'pickup_window',
        'pickup_time',
        'pickup_schedule_note',
        'pickup_confirmed_at',
        'pickup_request_date', // another day the donor asked for
        'pickup_request_window',
        'pickup_request_note',
        'status',
        'notes',
        'thank_you_note',    // optional message from the foundation when received
        'donated_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'donated_at' => 'datetime',
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'pickup_accuracy' => 'integer',
        'pickup_date' => 'date',
        'pickup_confirmed_at' => 'datetime',
        'pickup_request_date' => 'date',
    ];

    public const PICKUP_WINDOWS = [
        'morning' => 'Morning (8 AM–12 NN)',
        'afternoon' => 'Afternoon (1–5 PM)',
    ];

    /** "Sat, Oct 31 · Morning (8 AM–12 NN)" or "Sat, Oct 31 · 2:30 PM" */
    public static function pickupLabel($date, ?string $window, ?string $time = null): ?string
    {
        if (!$date) {
            return null;
        }
        $when = $window === 'exact' && $time
            ? Carbon::parse($time)->format('g:i A')
            : (self::PICKUP_WINDOWS[$window] ?? '');

        return trim(Carbon::parse($date)->format('D, M j') . ' · ' . $when, ' ·');
    }

    /** The pick-up schedule as both apps show it, or null when none is set */
    public function pickupSchedule(): ?array
    {
        if (!$this->pickup_date) {
            return null;
        }

        return [
            'date' => $this->pickup_date->toDateString(),
            'window' => $this->pickup_window,
            'time' => $this->pickup_time ? substr($this->pickup_time, 0, 5) : null,
            'label' => self::pickupLabel($this->pickup_date, $this->pickup_window, $this->pickup_time),
            'note' => $this->pickup_schedule_note,
            'confirmed' => (bool) $this->pickup_confirmed_at,
            'confirmed_at' => $this->pickup_confirmed_at?->toIso8601String(),
            'request' => $this->pickup_request_date ? [
                'date' => $this->pickup_request_date->toDateString(),
                'window' => $this->pickup_request_window,
                'label' => self::pickupLabel($this->pickup_request_date, $this->pickup_request_window),
                'note' => $this->pickup_request_note,
            ] : null,
        ];
    }

    // withTrashed: a donation is history, so it still shows its campaign / donor
    // after either is soft-deleted (otherwise donation lists crash on null).
    public function campaign()
    {
        return $this->belongsTo(Campaign::class)->withTrashed();
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id')->withTrashed();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}