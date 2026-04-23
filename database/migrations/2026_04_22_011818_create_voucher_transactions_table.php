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
    Schema::create('voucher_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
        $table->enum('type', ['credit', 'debit']);
        $table->decimal('amount', 15, 2);
        $table->decimal('balance_after', 15, 2);
        $table->string('description');
        $table->foreignId('ad_id')->nullable()->constrained('ads')->onDelete('set null');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_transactions');
    }
};
