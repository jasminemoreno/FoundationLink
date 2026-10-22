<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateReaction extends Model
{
    protected $fillable = ['donor_id', 'campaign_update_id'];

    public function donor()
    {
        // withTrashed: reactions from soft-deleted donors still list without crashing
        return $this->belongsTo(User::class, 'donor_id')->withTrashed();
    }

    public function campaignUpdate()
    {
        return $this->belongsTo(CampaignUpdate::class, 'campaign_update_id');
    }
}