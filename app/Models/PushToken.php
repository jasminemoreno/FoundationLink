<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An Expo push address of one phone where the user is logged in to the
 * mobile app, e.g. "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]".
 */
class PushToken extends Model
{
    /** What getExpoPushTokenAsync() returns. */
    public const FORMAT = '/^Expo(nent)?PushToken\[[^\]\s]+\]$/';

    protected $fillable = ['user_id', 'token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
