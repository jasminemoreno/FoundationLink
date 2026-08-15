<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignUpdate extends Model
{
    protected $fillable = ['campaign_id', 'title', 'content', 'photos'];
    protected $casts = ['photos' => 'array'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    public function reactions()
    {
        return $this->hasMany(UpdateReaction::class);
    }
}