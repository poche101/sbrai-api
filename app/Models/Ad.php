<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',             // 'product' | 'service' | 'property'
        'title',
        'description',
        'price',
        'price_unit',
        'location',
        'status',           // 'active' | 'inactive'
        'property_status',  // 'for_rent' | 'for_sale' — property only
        'bedrooms',         // property only
        'sqft',             // property only
        'views_count',      // Track engagement
        'likes_count',      // Track engagement
    ];

    protected $casts = [
        'price'       => 'float',
        'bedrooms'    => 'integer',
        'sqft'        => 'float',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(AdImage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(AdView::class);
    }

    public function favorites()
    {
        return $this->hasMany(AdFavorite::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeWithEngagementCounts($query)
    {
        return $query->withCount(['views', 'favorites', 'chats']);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeProperties($query)
    {
        return $query->where('type', 'property');
    }

    // ── Engagement Helpers ─────────────────────────────────────────────────────

    /**
     * Increment the view count safely.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment the likes count safely.
     */
    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }
}
