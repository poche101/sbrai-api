<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Ad;
use App\Models\AdView;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VendorProfileController extends Controller
{
    // ── GET /api/v1/vendor/profile ─────────────────────────────────────────────
    /**
     * Returns the full vendor profile in the shape the Flutter ProfileScreen
     * expects. Field names deliberately match what the Dart code reads:
     *   full_name, phone_number, business_name, business_address,
     *   nin, email_verified_at, nin_verified_at, rating, created_at,
     *   active_listings, total_views, total_chats
     */
    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user();

        // ── Inline statistics ──────────────────────────────────────────────────
        $adIds = Ad::where('user_id', $vendor->id)
            ->where('status', 'active')
            ->pluck('id');

        $activeListings = $adIds->count();

        $totalViews = $adIds->isEmpty()
            ? 0
            : AdView::whereIn('ad_id', $adIds)->count();

        $totalChats = Chat::where('vendor_id', $vendor->id)->count();

        return response()->json([
            'status'  => 'success',
            'data'    => [
                // ── Identity ─────────────────────────────────────────────────
                'id'               => $vendor->id,
                'full_name'        => $vendor->name,          // Flutter reads 'full_name'
                'email'            => $vendor->email,
                'phone_number'     => $vendor->phone,         // Flutter reads 'phone_number'

                // ── Business ─────────────────────────────────────────────────
                'business_name'    => $vendor->business_name    ?? '',
                'business_address' => $vendor->business_address ?? '',
                'business_category'=> $vendor->business_category ?? '',
                'state'            => $vendor->state ?? '',
                'city'             => $vendor->city  ?? '',
                'business_description' => $vendor->business_description ?? '',
                'logo_url'         => $vendor->logo_url,
                'profile_photo_url'    => $vendor->profile_photo_url,

                // ── KYC ──────────────────────────────────────────────────────
                'nin'              => $vendor->nin ?? '',
                'nin_verified_at'  => $vendor->nin_verified_at?->toIso8601String(),

                // ── Verification & rating ─────────────────────────────────────
                'email_verified_at'=> $vendor->email_verified_at?->toIso8601String(),
                'is_verified'      => $vendor->is_verified,
                'vendor_status'    => $vendor->vendor_status,
                'rating'           => (float) ($vendor->rating ?? 0.0),

                // ── Timestamps ────────────────────────────────────────────────
                'created_at'       => $vendor->created_at->toIso8601String(),
                'updated_at'       => $vendor->updated_at->toIso8601String(),

                // ── Statistics (Flutter reads these directly from data) ────────
                'active_listings'  => $activeListings,
                'total_views'      => $totalViews,
                'total_chats'      => $totalChats,
            ],
        ]);
    }

    // ── PATCH /api/v1/vendor/profile ───────────────────────────────────────────
    /**
     * Updates the four editable fields from the Flutter edit form.
     * Flutter reads response['status'] == 'success' and response['message'].
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $vendor = $request->user();

        // Map Flutter field names → DB column names
        $updates = array_filter([
            'name'             => $request->input('name'),
            'phone'            => $request->input('phone'),
            'business_name'    => $request->input('business_name'),
            'business_address' => $request->input('business_address'),
        ], fn($v) => !is_null($v));

        if (empty($updates)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No fields provided to update.',
            ], 422);
        }

        $vendor->update($updates);

        Log::info('Vendor profile updated', ['vendor_id' => $vendor->id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully!',
            'data'    => [
                'full_name'        => $vendor->fresh()->name,
                'phone_number'     => $vendor->fresh()->phone,
                'business_name'    => $vendor->fresh()->business_name,
                'business_address' => $vendor->fresh()->business_address,
            ],
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old photo if it exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store('profile_photos', 'public');
        $user->update(['profile_photo_path' => $path]);
        $user = $user->fresh();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile photo updated successfully!',
            'data'    => [
                'profile_photo_url' => $user->profile_photo_url,
            ],
        ]);
    }

    /**
     * POST /api/v1/vendor/profile/logo
     * Business Logo
     */
    public function updateLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $vendor = $request->user();

        if ($vendor->logo_path) {
            Storage::disk('public')->delete($vendor->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $vendor->update(['logo_path' => $path]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Business logo updated successfully!',
            'data'    => [
                'logo_url' => $vendor->logo_url,
            ],
        ]);
    }
}
