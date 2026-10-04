<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'address',
        'gender',
        'birthdate',
        'profile_photo',

        // email-change flow (donor + foundation admin)
        'pending_email',
        'email_change_token',
        'email_change_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_change_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_change_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    public function isFoundationAdmin()
    {
        return $this->role === 'foundation_admin';
    }

    public function isDonor()
    {
        return $this->role === 'donor';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // If a foundation admin owns a foundation
    public function foundation()
    {
        return $this->hasOne(Foundation::class);
    }

    // Donor donations
    public function donations()
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }
    public function followedFoundations()
    {
        return $this->belongsToMany(Foundation::class, 'foundation_followers', 'donor_id', 'foundation_id');
    }
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class)->latest();
    }

}