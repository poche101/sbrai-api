<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorActivity extends Model
{
    protected $fillable = [
        'vendor_id', 'type', 'title',
        'icon_name', 'color_hex',
        'subject_type', 'subject_id',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // Polymorphic: could point to an Ad, Chat, etc.
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Map activity type → Flutter icon name and colour.
     * The Flutter app resolves these to actual Icon widgets.
     */
    public static function iconFor(string $type): array
    {
        return match ($type) {
            'view'     => ['icon' => 'visibility',      'color' => '#2196F3'],
            'message'  => ['icon' => 'chat_bubble',     'color' => '#4CAF50'],
            'favorite' => ['icon' => 'favorite',        'color' => '#F44336'],
            'sale'     => ['icon' => 'attach_money',    'color' => '#FF9800'],
            default    => ['icon' => 'notifications',   'color' => '#9C27B0'],
        };
    }

    /**
     * Factory: create an activity entry and return it.
     */
    public static function log(
        int $vendorId,
        string $type,
        string $title,
        ?Model $subject = null
    ): self {
        $meta = self::iconFor($type);

        return self::create([
            'vendor_id'    => $vendorId,
            'type'         => $type,
            'title'        => $title,
            'icon_name'    => $meta['icon'],
            'color_hex'    => $meta['color'],
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
        ]);
    }
}
