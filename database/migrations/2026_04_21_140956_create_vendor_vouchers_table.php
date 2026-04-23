<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One voucher wallet per vendor
        Schema::create('vendor_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // Ledger of every credit/debit — append-only audit trail
        Schema::create('voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');               // 'credit' | 'debit'
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description')->nullable(); // "Ad promotion – Cement Mixer"
            $table->foreignId('ad_id')->nullable()->constrained('ads')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_transactions');
        Schema::dropIfExists('vendor_vouchers');
    }
};
