<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorVoucher extends Model
{
    protected $fillable = ['vendor_id', 'balance'];

    protected $casts = [
        'balance' => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function transactions()
    {
        return $this->hasMany(VoucherTransaction::class, 'vendor_id', 'vendor_id')
            ->latest();
    }

    // ── Balance helpers ────────────────────────────────────────────────────────

    /**
     * Credit the wallet and log the transaction.
     */
    public function credit(float $amount, string $description = '', ?int $adId = null): void
    {
        $this->increment('balance', $amount);
        $this->logTransaction('credit', $amount, $description, $adId);
    }

    /**
     * Debit the wallet. Throws if insufficient funds.
     */
    public function debit(float $amount, string $description = '', ?int $adId = null): void
    {
        if ($this->balance < $amount) {
            throw new \DomainException('Insufficient voucher balance.');
        }
        $this->decrement('balance', $amount);
        $this->logTransaction('debit', $amount, $description, $adId);
    }

    private function logTransaction(
        string $type,
        float $amount,
        string $description,
        ?int $adId
    ): void {
        VoucherTransaction::create([
            'vendor_id'     => $this->vendor_id,
            'type'          => $type,
            'amount'        => $amount,
            'balance_after' => $this->fresh()->balance,
            'description'   => $description,
            'ad_id'         => $adId,
        ]);
    }
}
