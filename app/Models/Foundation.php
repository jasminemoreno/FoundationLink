<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $street
 * @property string|null $barangay
 * @property string|null $city_municipality
 * @property string|null $province
 * @property string|null $logo
 * @property string|null $cover_photo
 * @property string $status
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\FoundationDocument> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Campaign> $campaigns
 */
class Foundation extends Model
{
    use SoftDeletes;

    protected $table = 'foundations';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'street',
        'barangay',
        'city_municipality',
        'province',
        'logo',
        'cover_photo',
        'status',
        'rejection_reason',
        'verified_at',
    ];

    /** Statuses of a foundation whose admin hasn't uploaded verification documents yet. */
    public const UNFINISHED_SIGN_UP_STATUSES = ['pending_verification', 'incomplete'];

    /** Sign-up is finished once the documents were sent (under review or later). */
    public function isSignUpFinished(): bool
    {
        return !in_array($this->status, self::UNFINISHED_SIGN_UP_STATUSES, true);
    }

    /**
     * Why Settings are locked for this foundation, or null if they can be edited.
     * Settings open once the super admin has approved the foundation:
     *   'finish_signup' - details or documents not sent yet
     *   'under_review'  - waiting for the super admin's approval
     *   'rejected'      - fix details with "Edit & Resubmit" on the dashboard instead
     */
    public function settingsLockReason(): ?string
    {
        return match (true) {
            !$this->isSignUpFinished() => 'finish_signup',
            $this->status === 'under_review' => 'under_review',
            $this->status === 'rejected' => 'rejected',
            default => null, // verified (and suspended, as before)
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(
            \App\Models\FoundationDocument::class,
            'foundation_id'
        );
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function campaigns()
    {
        return $this->hasMany(\App\Models\Campaign::class);
    }

    public function likers()
    {
        return $this->belongsToMany(User::class, 'foundation_followers', 'foundation_id', 'donor_id')
            ->wherePivot('type', '=', 'like');
    }

    // also scope the existing followers() to only type=follow
    public function followers()
    {
        return $this->belongsToMany(User::class, 'foundation_followers', 'foundation_id', 'donor_id')
            ->wherePivot('type', '=', 'follow');
    }
    public function donations()
    {
        return $this->hasManyThrough(
            \App\Models\Donation::class,
            \App\Models\Campaign::class,
            'foundation_id', // FK on campaigns
            'campaign_id',   // FK on donations
            'id',            // local key on foundations
            'id'             // local key on campaigns
        );
    }
}