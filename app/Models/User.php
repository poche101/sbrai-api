<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ── Mass-assignable columns ────────────────────────────────────────────────

    protected $fillable = [
        // ── Shared (buyer + vendor) ──────────────────────────────────────────
        'name',
        'email',
        'phone',
        'password',
        'role',                  // 'buyer' | 'vendor'
        'profile_photo',         // Updated to match migration column
        'address',               // NEW: Personal/Delivery address for buyers
        'fcm_token',
        'phone_verified_at',
        'google_id',
        'avatar',
        'auth_provider',



        // ── Vendor business profile ──────────────────────────────────────────
        'business_name',
        'business_category',     // e.g. "Building Materials"
        'business_address',
        'state',
        'city',
        'business_description',
        'logo_path',             // Business branding image

        // ── Vendor account status ────────────────────────────────────────────
        'vendor_status',         // 'pending' | 'active' | 'suspended'
        'is_verified',           // verified badge shown in app

        // ── KYC ─────────────────────────────────────────────────────────────
        'nin',                   // National Identification Number (11 chars)
        'nin_verified_at',       // set by admin after NIN check

        // ── Rating ──────────────────────────────────────────────────────────
        'rating',                // average star rating 0.0–5.0 from buyer reviews
        'email_verified_at',   // ← add this
         'phone_verified_at',
        ];

    // ── Hidden from serialisation ──────────────────────────────────────────────

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ── Type casts ─────────────────────────────────────────────────────────────

    protected $casts = [
        'email_verified_at' => 'datetime',
        'nin_verified_at'   => 'datetime',
        'password'          => 'hashed',
        'is_verified'       => 'boolean',
        'rating'            => 'float',
        'phone_verified_at' => 'datetime',

    ];

    // ── Appended virtual attributes ────────────────────────────────────────────

    protected $appends = ['logo_url', 'profile_photo_url'];

    // ══════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Ads posted by this vendor.
     */
    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * Ads the user (buyer) has saved / favorited.
     */
    public function favoriteAds()
    {
        return $this->belongsToMany(
            Ad::class,
            'ad_favorites',
            'user_id',
            'ad_id'
        )->withTimestamps();
    }

    /**
     * Vendor ad-credit wallet.
     */
    public function voucher()
    {
        return $this->hasOne(VendorVoucher::class, 'vendor_id');
    }

    /**
     * Complete credit/debit ledger for the vendor's wallet.
     */
    public function voucherTransactions()
    {
        return $this->hasMany(VoucherTransaction::class, 'vendor_id')
                    ->latest();
    }

    /**
     * Dashboard activity feed.
     */
    public function activities()
    {
        return $this->hasMany(VendorActivity::class, 'vendor_id')
                    ->latest();
    }

    /**
     * Chat threads where this user is the vendor (seller) side.
     */
    public function vendorChats()
    {
        return $this->hasMany(Chat::class, 'vendor_id');
    }

    /**
     * Chat threads where this user is the buyer side.
     */
    public function buyerChats()
    {
        return $this->hasMany(Chat::class, 'buyer_id');
    }

    /**
     * Vendor notification, privacy, and language/region settings.
     */
    public function settings()
    {
        return $this->hasOne(VendorSettings::class, 'vendor_id');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // COMPUTED ATTRIBUTES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Full public URL for the vendor's logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }

    /**
     * Full public URL for the user's personal profile photo.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? Storage::disk('public')->url($this->profile_photo)
            : null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ROLE HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function isActiveVendor(): bool
    {
        return $this->role === 'vendor'
            && $this->vendor_status === 'active';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // QUERY SCOPES
    // ══════════════════════════════════════════════════════════════════════════

    public function scopeBuyers($query)
    {
        return $query->where('role', 'buyer');
    }

    public function scopeVendors($query)
    {
        return $query->where('role', 'vendor');
    }

    public function scopeActiveVendors($query)
    {
        return $query->where('role', 'vendor')
                     ->where('vendor_status', 'active');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SERIALISATION HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Safe subset of vendor fields shown to buyers on the public listing page.
     */
    public function publicVendorProfile(): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'profile_photo_url'    => $this->profile_photo_url,
            'business_name'        => $this->business_name,
            'business_category'    => $this->business_category,
            'business_description' => $this->business_description,
            'state'                => $this->state,
            'city'                 => $this->city,
            'logo_url'             => $this->logo_url,
            'is_verified'          => $this->is_verified,
            'rating'               => $this->rating,
            'phone'                => $this->phone,
        ];
    }
}
