<?php

namespace App\Models;

use App\Support\PrivateFile;
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

    protected $appends = ['file_url'];

    /** Signed link to the file, which is private (App\Support\PrivateFile). */
    public function getFileUrlAttribute(): ?string
    {
        return PrivateFile::url($this->file_path);
    }

    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }
}
