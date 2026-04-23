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
        Schema::create('vendor_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            // Maps to Flutter's ActivityItem fields
            $table->string('type');       // 'view' | 'message' | 'favorite' | 'sale'
            $table->string('title');      // "New view on Cement Mixer"
            $table->string('icon_name')->nullable();  // icon key → resolved client-side
            $table->string('color_hex')->nullable();  // e.g. '#2196F3'

            // Manual Polymorphic reference to avoid duplicate index names
            // Replacing nullableMorphs('subject') to prevent internal index collision
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();

            $table->timestamps();

            // Explicitly named indexes
            $table->index(['vendor_id', 'created_at']);
            $table->index(['subject_id', 'subject_type'], 'vendor_activities_subject_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_activities');
    }
};
