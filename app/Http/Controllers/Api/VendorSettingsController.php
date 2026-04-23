<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Ad;
use App\Models\VendorSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class VendorSettingsController extends Controller
{
    // ── GET /api/v1/vendor/settings ────────────────────────────────────────────
    /**
     * Returns the vendor's current settings.
     * Auto-creates a settings row with sensible defaults if none exists yet
     * (matches Flutter's SettingsModel() default constructor values).
     */
    public function show(Request $request): JsonResponse
    {
        $settings = $this->getOrCreateSettings($request->user()->id);

        return response()->json([
            'success' => true,
            'data'    => $settings->toApiArray(),
        ]);
    }

    // ── PATCH /api/v1/vendor/settings ──────────────────────────────────────────
    /**
     * Partial update — Flutter only sends the field that changed (one toggle flip
     * or one language selection) so every field is optional via 'sometimes'.
     *
     * Example body for a single toggle:
     *   { "new_listings": false }
     *
     * Example body for language change:
     *   { "language": "Yoruba" }
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = $this->getOrCreateSettings($request->user()->id);
        $settings->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Settings saved.',
            'data'    => $settings->fresh()->toApiArray(),
        ]);
    }

    // ── POST /api/v1/vendor/settings/change-password ───────────────────────────
    /**
     * Validates the current password before updating to the new one.
     * All tokens are revoked after a successful change so the vendor
     * must log in again on all devices.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors'  => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        // Prevent re-using the same password
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from your current password.',
                'errors'  => ['password' => ['Choose a different password.']],
            ], 422);
        }

        DB::transaction(function () use ($user, $request) {
            $user->update(['password' => Hash::make($request->password)]);

            // Revoke all tokens — vendor must log in again
            $user->tokens()->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please log in again.',
        ]);
    }

    // ── DELETE /api/v1/vendor/settings/account ─────────────────────────────────
    /**
     * Permanently deletes the vendor account after verifying their password.
     * This mirrors the "Are you absolutely sure?" dialog in Flutter.
     *
     * Cascade deletes (via foreign key constraints):
     *   ads → ad_images, ad_views, ad_favorites, chats, vendor_activities
     *   vendor_vouchers, voucher_transactions, vendor_settings
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Require password confirmation before destructive action
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect. Account not deleted.',
                'errors'  => ['password' => ['Incorrect password.']],
            ], 422);
        }

        DB::transaction(function () use ($user) {
            // Revoke all tokens first
            $user->tokens()->delete();

            // Soft-delete ads (mark inactive) so historical data is preserved
            // for other users' chat history. Hard-delete if you prefer.
            Ad::where('user_id', $user->id)->update(['status' => 'inactive']);

            // Delete the user — cascades to settings, vouchers, activities
            $user->delete();
        });

        Log::info("Vendor account deleted", ['vendor_id' => $user->id, 'email' => $user->email]);

        return response()->json([
            'success' => true,
            'message' => 'Your account has been permanently deleted.',
        ]);
    }

    // ── GET /api/v1/vendor/settings/options ────────────────────────────────────
    /**
     * Returns all available picker values so the Flutter app doesn't need to
     * hard-code option lists. Used by the language and currency pickers.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'languages'  => VendorSettings::availableLanguages(),
                'currencies' => VendorSettings::availableCurrencies(),
            ],
        ]);
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function getOrCreateSettings(int $vendorId): VendorSettings
    {
        return VendorSettings::firstOrCreate(
            ['vendor_id' => $vendorId],
            VendorSettings::defaults($vendorId)
        );
    }
}
