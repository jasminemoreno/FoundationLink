<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FoundationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'type',
        'document_type',
        'file_path',
        'file_name',
        'file_type',
        'status',
        'remarks'
    ];
    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }
}
