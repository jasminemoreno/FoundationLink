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