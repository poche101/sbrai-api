<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add notification preferences and privacy settings columns to the users table.
     *
     * All columns are nullable so existing rows are untouched; the controller
     * falls back to sensible defaults when a value is NULL.
     *
     * Notification columns  → notif_*
     * Privacy / Security    → privacy_*
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // ── Notifications ──────────────────────────────────────────────────
            // New Listings  – "Get notified about new items in your area"
            $table->boolean('notif_new_listings')
                  ->nullable()
                  ->default(null)
                  ->after('fcm_token');

            // Price Drops   – "Alert me when prices drop on favorited items"
            $table->boolean('notif_price_drops')
                  ->nullable()
                  ->default(null)
                  ->after('notif_new_listings');

            // Messages      – "Receive notifications for new messages"
            $table->boolean('notif_messages')
                  ->nullable()
                  ->default(null)
                  ->after('notif_price_drops');

            // Promotions    – "Receive promotional offers and deals"
            $table->boolean('notif_promotions')
                  ->nullable()
                  ->default(null)
                  ->after('notif_messages');

            // ── Privacy & Security ─────────────────────────────────────────────
            // Show Online Status – "Let others see when you're online"
            $table->boolean('privacy_show_online')
                  ->nullable()
                  ->default(null)
                  ->after('notif_promotions');

            // Show Phone Number  – "Display phone number on listings"
            $table->boolean('privacy_show_phone')
                  ->nullable()
                  ->default(null)
                  ->after('privacy_show_online');

            // Allow Messages     – "Allow users to send you messages"
            $table->boolean('privacy_allow_msgs')
                  ->nullable()
                  ->default(null)
                  ->after('privacy_show_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notif_new_listings',
                'notif_price_drops',
                'notif_messages',
                'notif_promotions',
                'privacy_show_online',
                'privacy_show_phone',
                'privacy_allow_msgs',
            ]);
        });
    }
};
