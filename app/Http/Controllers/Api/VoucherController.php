<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\VendorVoucher;
use App\Models\VoucherTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    // ── GET /api/vendor/voucher ────────────────────────────────────────────────
    /**
     * Current balance + paginated transaction history.
     */
    public function show(Request $request): JsonResponse
    {
        $voucher = $this->getOrCreateWallet($request->user()->id);

        $transactions = VoucherTransaction::where('vendor_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->through(fn($t) => [
                'id'          => $t->id,
                'type'        => $t->type,             // 'credit' | 'debit'
                'amount'      => (float) $t->amount,
                'balance_after' => (float) $t->balance_after,
                'description' => $t->description,
                'ad_id'       => $t->ad_id,
                'created_at'  => $t->created_at->toDateTimeString(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'balance'      => (float) $voucher->balance,
                'transactions' => $transactions,
            ],
        ]);
    }

    // ── POST /api/vendor/voucher/topup ─────────────────────────────────────────
    /**
     * Credit the wallet. In production this endpoint would be called by your
     * payment gateway webhook (Paystack, Flutterwave, etc.) after a confirmed
     * payment — NOT called directly by the Flutter app.
     *
     * For now it accepts a manual top-up so you can test the flow.
     * Protect this with a webhook secret in production.
     */
    public function topUp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference'   => ['nullable', 'string', 'max:100'], // payment ref
        ]);

        DB::beginTransaction();
        try {
            $voucher = $this->getOrCreateWallet($request->user()->id);
            $voucher->credit(
                (float) $data['amount'],
                $data['description'] ?? "Top-up via payment gateway",
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Top-up failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Wallet credited successfully.',
            'data'        => ['balance' => (float) $voucher->fresh()->balance],
        ]);
    }

    // ── POST /api/vendor/voucher/spend ─────────────────────────────────────────
    /**
     * Debit the wallet to promote an ad.
     * The Flutter app calls this when a vendor taps "Boost Ad".
     *
     * Promotion tiers (configurable in config/voucher.php):
     *   standard  → ₦500  / 7 days
     *   featured  → ₦1500 / 7 days
     *   premium   → ₦3000 / 7 days
     */
    public function spend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ad_id' => ['required', 'integer', 'exists:ads,id'],
            'tier'  => ['required', 'string', 'in:standard,featured,premium'],
        ]);

        $ad = Ad::findOrFail($data['ad_id']);

        if ($ad->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only boost your own ads.',
            ], 403);
        }

        $cost = match ($data['tier']) {
            'standard' => 500.00,
            'featured' => 1500.00,
            'premium'  => 3000.00,
        };

        DB::beginTransaction();
        try {
            $voucher = $this->getOrCreateWallet($request->user()->id);
            $voucher->debit(
                $cost,
                "Ad boost ({$data['tier']}) – {$ad->title}",
                $ad->id
            );
            DB::commit();
        } catch (\DomainException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // "Insufficient voucher balance."
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Spend failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Ad boosted on the {$data['tier']} plan.",
            'data'    => [
                'balance'      => (float) $voucher->fresh()->balance,
                'amount_spent' => $cost,
                'tier'         => $data['tier'],
            ],
        ]);
    }

    // ── GET /api/vendor/voucher/transactions ───────────────────────────────────
    /**
     * Full paginated transaction ledger (credits and debits).
     * Supports ?type=credit|debit filter.
     */
    public function transactions(Request $request): JsonResponse
    {
        $query = VoucherTransaction::where('vendor_id', $request->user()->id)
            ->latest();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate(20)->through(fn($t) => [
            'id'            => $t->id,
            'type'          => $t->type,
            'amount'        => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'description'   => $t->description,
            'ad_id'         => $t->ad_id,
            'created_at'    => $t->created_at->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    // ── Private helper ─────────────────────────────────────────────────────────

    private function getOrCreateWallet(int $vendorId): VendorVoucher
    {
        return VendorVoucher::firstOrCreate(
            ['vendor_id' => $vendorId],
            ['balance'   => 0.00]
        );
    }
}
