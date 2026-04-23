<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorSettings extends Model
{
    protected $table = 'vendor_settings';

    protected $fillable = [
        'vendor_id',
        // Notifications
        'new_listings',
        'price_drops',
        'messages',
        'promotions',
        // Privacy & Security
        'show_online_status',
        'show_phone_number',
        'allow_messages',
        // Language & Region
        'language',
        'currency',
    ];

    protected $casts = [
        'new_listings'       => 'boolean',
        'price_drops'        => 'boolean',
        'messages'           => 'boolean',
        'promotions'         => 'boolean',
        'show_online_status' => 'boolean',
        'show_phone_number'  => 'boolean',
        'allow_messages'     => 'boolean',
    ];

    // ── Relationship ──────────────────────────────────────────────────────────

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // ── Defaults that mirror Flutter's SettingsModel() constructor ────────────

    public static function defaults(int $vendorId): array
    {
        return [
            'vendor_id'          => $vendorId,
            'new_listings'       => true,
            'price_drops'        => true,
            'messages'           => true,
            'promotions'         => false,
            'show_online_status' => true,
            'show_phone_number'  => false,
            'allow_messages'     => true,
            'language'           => 'English',
            'currency'           => 'NGN',
        ];
    }

    // ── Available options ─────────────────────────────────────────────────────

    public static function availableLanguages(): array
    {
        return ['English', 'French', 'Igbo', 'Hausa', 'Yoruba'];
    }

    public static function availableCurrencies(): array
    {
        return ['NGN', 'USD', 'GBP', 'EUR'];
    }

    // ── Serialise to the shape Flutter's SettingsModel expects ───────────────

    public function toApiArray(): array
    {
        return [
            // Notifications
            'new_listings'       => $this->new_listings,
            'price_drops'        => $this->price_drops,
            'messages'           => $this->messages,
            'promotions'         => $this->promotions,
            // Privacy & Security
            'show_online_status' => $this->show_online_status,
            'show_phone_number'  => $this->show_phone_number,
            'allow_messages'     => $this->allow_messages,
            // Language & Region
            'language'           => $this->language,
            'currency'           => $this->currency,
            // Meta
            'available_languages' => self::availableLanguages(),
            'available_currencies' => self::availableCurrencies(),
        ];
    }
}
