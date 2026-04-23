<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBuyerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BuyerProfileController extends Controller
{
    // ── GET /api/v1/buyers/profile ─────────────────────────────────────────────
    /**
     * Returns the buyer's profile.
     *
     * Flutter's UserProfile.fromJson reads these fields from response['data']:
     *   full_name, email, phone, address, created_at, photo, profile_photo
     *
     * We include BOTH 'photo' and 'profile_photo' keys so either legacy
     * or updated Flutter code works without modification.
     */
    public function show(Request $request): JsonResponse
    {
        $buyer = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->profileResource($buyer),
        ]);
    }

    // ── PUT /api/v1/buyers/profile/update ──────────────────────────────────────
    /**
     * Updates name, phone, and/or address.
     * Flutter sends: { name, phone, address } via _api.put()
     */
    public function update(UpdateBuyerProfileRequest $request): JsonResponse
    {
        $buyer   = $request->user();
        $updates = array_filter([
            'name'    => $request->input('name'),
            'phone'   => $request->input('phone'),
            'address' => $request->input('address'),
        ], fn($v) => !is_null($v));

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No fields provided to update.',
            ], 422);
        }

        $buyer->update($updates);
        Log::info('Buyer profile updated', ['buyer_id' => $buyer->id]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => $this->profileResource($buyer->fresh()),
        ]);
    }

    // ── PUT /api/v1/buyers/profile/upload-photo ────────────────────────────────
    /**
     * Accepts a profile photo upload.
     * Flutter sends multipart with:
     *   fileField: 'profile_photo'
     *   method spoofing: { _method: 'PUT' }
     *
     * Steps:
     *   1. Validate the uploaded file
     *   2. Delete the old photo if present
     *   3. Store the new photo as a JPEG in buyers/photos/{uuid}.jpg
     *   4. Return the updated profile with the new photo URL
     *
     * No watermark applied — this is the buyer's own face/avatar.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'profile_photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5 MB
            ],
        ], [
            'profile_photo.required' => 'Please select a photo to upload.',
            'profile_photo.image'    => 'The file must be an image.',
            'profile_photo.max'      => 'Photo must be under 5 MB.',
        ]);

        $buyer = $request->user();

        // 1. Delete old photo from storage
        if ($buyer->profile_photo) {
            Storage::disk('public')->delete($buyer->profile_photo);
        }

        // 2. Store new photo
        $file     = $request->file('profile_photo');
        $filename = 'buyers/photos/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('', $filename, 'public');

        // 3. Persist path
        $buyer->update(['profile_photo' => $filename]);
        Log::info('Buyer photo uploaded', ['buyer_id' => $buyer->id, 'path' => $filename]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated!',
            'data'    => $this->profileResource($buyer->fresh()),
        ]);
    }

    // ── Private resource formatter ─────────────────────────────────────────────

    /**
     * Shapes the user object to exactly match what Flutter's
     * UserProfile.fromJson() expects.
     *
     * Flutter checks: userData['full_name'] ?? userData['name'] ?? ...
     * Flutter checks: userData['photo']     ?? userData['profile_photo']
     * Flutter checks: userData['phone']?.toString()
     * Flutter checks: userData['created_at']
     */
    private function profileResource($buyer): array
    {
        $photoUrl = $buyer->profile_photo_url; // computed accessor on User model

        return [
            // Identity — Flutter reads 'full_name' first, then falls back to 'name'
            'id'            => $buyer->id,
            'full_name'     => $buyer->name,
            'name'          => $buyer->name,    // fallback key
            'email'         => $buyer->email,

            // Phone — Flutter casts to string to avoid int crashes
            'phone'         => (string) ($buyer->phone ?? ''),

            // Address — buyer-specific field
            'address'       => $buyer->address ?? '',

            // Photo — Flutter checks 'photo' first, then 'profile_photo'
            'photo'         => $photoUrl,          // primary key Flutter reads
            'profile_photo' => $photoUrl,          // fallback key

            // Timestamps — Flutter parses this with DateTime.parse()
            'created_at'    => $buyer->created_at->toIso8601String(),
            'updated_at'    => $buyer->updated_at->toIso8601String(),

            // Role flag
            'role'          => $buyer->role,
        ];
    }
}
