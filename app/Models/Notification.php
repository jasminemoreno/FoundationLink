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