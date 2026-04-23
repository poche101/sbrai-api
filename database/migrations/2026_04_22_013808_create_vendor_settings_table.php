<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')
                  ->unique()                         // one row per vendor
                  ->constrained('users')
                  ->cascadeOnDelete();

            // ── Notifications ────────────────────────────────────────────────
            $table->boolean('new_listings')->default(true);
            $table->boolean('price_drops')->default(true);
            $table->boolean('messages')->default(true);
            $table->boolean('promotions')->default(false);

            // ── Privacy & Security ────────────────────────────────────────────
            $table->boolean('show_online_status')->default(true);
            $table->boolean('show_phone_number')->default(false);
            $table->boolean('allow_messages')->default(true);

            // ── Language & Region ─────────────────────────────────────────────
            $table->string('language', 30)->default('English');
            $table->string('currency', 10)->default('NGN');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_settings');
    }
};
