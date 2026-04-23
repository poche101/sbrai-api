<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // ── Identity & Shared Fields ──────────────────────────────────────
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // ── Role ──────────────────────────────────────────────────────────
            // 'buyer'  → Default user
            // 'vendor' → Business user
            $table->enum('role', ['buyer', 'vendor'])->default('buyer')->index();

            // ── Profile Fields ────────────────────────────────────────────────
            // Shared profile photo for both buyers and vendors
            $table->string('profile_photo')->nullable();

            // Home / delivery address — primary for buyers
            $table->string('address')->nullable();

            // ── Vendor business profile (null for buyers) ─────────────────────
            $table->string('business_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('business_address')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->text('business_description')->nullable();
            $table->string('logo_path')->nullable();

            // ── Vendor Status & Verification ──────────────────────────────────
            $table->enum('vendor_status', ['pending', 'active', 'suspended'])
                  ->nullable()
                  ->default(null);

            $table->boolean('is_verified')->default(false);

            // ── KYC (National Identity) ───────────────────────────────────────
            $table->string('nin', 11)->nullable()->unique();
            $table->timestamp('nin_verified_at')->nullable();

            // ── Rating (0.0 to 5.0) ───────────────────────────────────────────
            $table->decimal('rating', 3, 2)->default(0.00);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
