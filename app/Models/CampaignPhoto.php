<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignPhoto extends Model
{
    protected $fillable = [
        'campaign_id',
        'photo_path',
        'caption',
        'order',
    ];
}