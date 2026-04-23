<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AdImage extends Model
{
    use HasFactory;

    protected $fillable = ['ad_id', 'path', 'disk', 'sort_order'];

    protected $appends = ['url'];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Full public URL for the watermarked image.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }
}
