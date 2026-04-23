<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            // viewer_id is nullable — guests can also view listings
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6
            $table->timestamps();

            // Prevent a single user from inflating view counts (one per day)
            $table->unique(['ad_id', 'viewer_id', 'ip_address'], 'unique_view');

            $table->index(['ad_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_views');
    }
};
