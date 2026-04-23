<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'ad_id',
        'vendor_id',
        'buyer_id',
        'last_message_at',
        'vendor_read',
        'buyer_read',
        'image_path',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'vendor_read'     => 'boolean',
        'buyer_read'      => 'boolean',
    ];

    protected $appends = ['unread_count', 'image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->latest();
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    /**
     * Unread messages in this thread — excludes messages sent by the
     * given user so each participant sees their own unread count.
     */
    public function unreadMessages()
    {
        return $this->hasMany(ChatMessage::class)
            ->whereNull('read_at');
    }

    // ── Computed Attributes ────────────────────────────────────────────────────

    /**
     * Number of unread messages for the authenticated user.
     * Returns 0 when called outside an auth context (e.g. seeders).
     */
    public function getUnreadCountAttribute(): int
    {
        $userId = auth()->id();

        if (! $userId) return 0;

        return $this->hasMany(ChatMessage::class)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * All threads the given user participates in as either buyer or vendor.
     * Usage: Chat::forUser($user->id)->get()
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('vendor_id', $userId)
              ->orWhere('buyer_id', $userId);
        });
    }

    /**
     * Threads that have at least one unread message for the given user.
     * Usage: Chat::forUser($id)->unread($id)->count()
     */
    public function scopeUnread($query, int $userId)
    {
        return $query->whereHas('messages', function ($q) use ($userId) {
            $q->whereNull('read_at')
              ->where('sender_id', '!=', $userId);
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Returns the other participant's User model relative to $userId.
     * Useful in Flutter response to know who you are chatting with.
     */
    public function otherParticipant(int $userId): ?User
    {
        if ($this->vendor_id === $userId) {
            return $this->buyer;
        }

        if ($this->buyer_id === $userId) {
            return $this->vendor;
        }

        return null;
    }

    /**
     * Check whether a given user ID is a participant in this thread.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->vendor_id === $userId
            || $this->buyer_id  === $userId;
    }
}
