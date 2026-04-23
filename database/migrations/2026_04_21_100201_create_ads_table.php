<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();

            // Ownership — nullable until auth is implemented
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->cascadeOnDelete();

            // Listing type
            $table->string('type')->index(); // product | service | property

            // Core fields
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 15, 2)->nullable();
            $table->string('price_unit', 50)->nullable();  // per bag, per job, per year …
            $table->string('location');
            $table->string('status')->default('active');   // active | inactive
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);

            // Property-specific fields (null for products / services)
            $table->string('property_status')->nullable(); // for_rent | for_sale
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->decimal('sqft', 10, 2)->nullable();

            $table->timestamps();

            // Common query patterns
            $table->index(['type', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
