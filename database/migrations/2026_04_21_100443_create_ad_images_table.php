<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ad_id')
                  ->constrained('ads')
                  ->cascadeOnDelete();

            $table->string('path');          // relative path inside the disk
            $table->string('disk')->default('public');
            $table->unsignedTinyInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['ad_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_images');
    }
};
