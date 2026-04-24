<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/v1/kyc/status
    // -------------------------------------------------------------------------

    /**
     * Returns the current KYC verification status for the authenticated user.
     * The Flutter KYCScreen uses this to pre-fill the progress bar on load.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data'   => [
                'email_verified'    => $user->email_verified_at !== null,
                'phone_verified'    => $user->phone_verified_at !== null,
                'identity_verified' => $user->nin_verified_at !== null,
                'is_verified'       => $user->is_verified,
                'progress'          => $this->calculateProgress($user),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Email OTP
    // -------------------------------------------------------------------------

    /**
     * POST /api/v1/kyc/email/send
     * Generates a 6-digit OTP and sends it to the user's email.
     * Called when Flutter taps "Send Verification Code" in EmailVerification.
     */
    public function sendEmailOtp(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'status'  => false,
                'message' => 'Email is already verified.',
            ], 422);
        }

        $otp = $this->generateOtp($user, 'email');

        // Send OTP via email
        try {
            Mail::raw(
                "Your SBRAI email verification code is: {$otp->code}\n\nThis code expires in 10 minutes.",
                function ($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Email Verification Code - SBRAI');
                }
            );
        } catch (\Throwable $e) {
            Log::error('KYC Email OTP send failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to send email. Please try again.',
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Verification code sent to ' . $user->email,
        ]);
    }

    /**
     * POST /api/v1/kyc/email/verify
     * Confirms the OTP entered by the user in Flutter.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user   = $request->user();
        $result = $this->validateOtp($user->id, 'email', $request->code);

        if (! $result['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'],
            ], 422);
        }

        $user->update(['email_verified_at' => now()]);
        $this->updateIsVerified($user);

        return response()->json([
            'status'  => true,
            'message' => 'Email verified successfully.',
            'data'    => ['progress' => $this->calculateProgress($user->fresh())],
        ]);
    }

    // -------------------------------------------------------------------------
    // Phone OTP
    // -------------------------------------------------------------------------

    /**
     * POST /api/v1/kyc/phone/send
     * Generates a 6-digit OTP and sends it via SMS.
     * Called when Flutter taps "Send SMS Code" in PhoneVerification.
     *
     * NOTE: SMS sending uses a log channel by default.
     * Swap the Log::info() call with your SMS provider (Termii, Twilio, etc.)
     * when you have one configured.
     */
   public function sendPhoneOtp(Request $request): JsonResponse
{
    $user = $request->user();

    if ($user->phone_verified_at) {
        return response()->json([
            'status'  => false,
            'message' => 'Phone number is already verified.',
        ], 422);
    }

    if (! $user->phone) {
        return response()->json([
            'status'  => false,
            'message' => 'No phone number on your account. Please update your profile first.',
        ], 422);
    }

    $otp = $this->generateOtp($user, 'phone');

    // TODO: Uncomment Termii block once sender ID "Sbrai" is approved
    // in your Termii dashboard (usually 24-48 hours after submission).
    // Until then OTP is written to the log for testing.

    // ── Termii SMS (uncomment when sender ID is approved) ─────────────────
    // $phone    = $this->formatPhoneNumber($user->phone);
    // $response = \Illuminate\Support\Facades\Http::withoutVerifying()
    //     ->post('https://api.ng.termii.com/api/sms/send', [
    //         'to'      => $phone,
    //         'from'    => config('services.termii.sender_id'),
    //         'sms'     => "Your SBRAI verification code is: {$otp->code}. Valid for 10 minutes.",
    //         'type'    => 'plain',
    //         'channel' => 'generic',
    //         'api_key' => config('services.termii.api_key'),
    //     ]);
    //
    // if (! $response->successful()) {
    //     Log::error('Termii SMS failed: ' . $response->body());
    //     return response()->json([
    //         'status'  => false,
    //         'message' => 'Failed to send SMS. Please try again.',
    //     ], 500);
    // }
    // ─────────────────────────────────────────────────────────────────────

    // Log OTP for testing until Termii sender ID is approved
    Log::info("PHONE OTP for {$user->phone}: {$otp->code}");

    return response()->json([
        'status'  => true,
        'message' => 'SMS code sent to ' . $user->phone,
    ]);
}
    /**
     * POST /api/v1/kyc/phone/verify
     * Confirms the SMS OTP entered by the user.
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user   = $request->user();
        $result = $this->validateOtp($user->id, 'phone', $request->code);

        if (! $result['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'],
            ], 422);
        }

        $user->update(['phone_verified_at' => now()]);
        $this->updateIsVerified($user);

        return response()->json([
            'status'  => true,
            'message' => 'Phone number verified successfully.',
            'data'    => ['progress' => $this->calculateProgress($user->fresh())],
        ]);
    }

    // -------------------------------------------------------------------------
    // Identity (NIN)
    // -------------------------------------------------------------------------

    /**
     * POST /api/v1/kyc/identity/verify
     * Accepts NIN + optional document file.
     * Called when Flutter taps "Verify with NIN" in IdentityVerification.
     */
    public function verifyIdentity(Request $request): JsonResponse
    {
        $request->validate([
            'nin'      => 'required|string|size:11|regex:/^[0-9]+$/',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = $request->user();

        if ($user->nin_verified_at) {
            return response()->json([
                'status'  => false,
                'message' => 'Identity is already verified.',
            ], 422);
        }

        // Check NIN is not already used by another account
        $ninTaken = User::where('nin', $request->nin)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($ninTaken) {
            return response()->json([
                'status'  => false,
                'message' => 'This NIN has already been used for verification.',
            ], 422);
        }

        // Store optional document
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')
                ->store("kyc/documents/{$user->id}", 'public');
        }

        // Save NIN and mark as verified
        // In production: call NIMC API here to validate the NIN before saving
        $user->update([
            'nin'            => $request->nin,
            'nin_verified_at' => now(),
        ]);

        $this->updateIsVerified($user);

        return response()->json([
            'status'  => true,
            'message' => 'Identity verified successfully.',
            'data'    => [
                'document_uploaded' => $documentPath !== null,
                'progress'          => $this->calculateProgress($user->fresh()),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a fresh 6-digit OTP for the user, invalidating any previous
     * unused OTPs of the same type first.
     */
    private function generateOtp(User $user, string $type): Otp
    {
        // Invalidate previous unused OTPs of this type
        Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('used_at')
            ->delete();

        return Otp::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Validate a submitted OTP code.
     * Returns ['valid' => bool, 'message' => string].
     */
    private function validateOtp(int $userId, string $type, string $code): array
    {
        $otp = Otp::where('user_id', $userId)
            ->where('type', $type)
            ->where('code', $code)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otp) {
            return ['valid' => false, 'message' => 'Invalid verification code.'];
        }

        if ($otp->isExpired()) {
            return ['valid' => false, 'message' => 'Verification code has expired. Please request a new one.'];
        }

        // Mark as used
        $otp->update(['used_at' => now()]);

        return ['valid' => true, 'message' => 'OTP verified.'];
    }

    /**
     * Calculate overall KYC progress as a fraction (0.0 – 1.0).
     * Mirrors the Flutter _calculationProgress getter exactly.
     */
    private function calculateProgress(User $user): float
    {
        $completed = 0;
        if ($user->email_verified_at)  $completed++;
        if ($user->phone_verified_at)  $completed++;
        if ($user->nin_verified_at)    $completed++;

        return round($completed / 3, 2);
    }

    /**
     * Set is_verified = true when all three steps are complete.
     */
    private function updateIsVerified(User $user): void
    {
        $fresh = $user->fresh();

        if (
            $fresh->email_verified_at &&
            $fresh->phone_verified_at &&
            $fresh->nin_verified_at
        ) {
            $fresh->update(['is_verified' => true]);
        }
    }

    /**
 * Convert Nigerian phone number to international format.
 * 08136386103  → 2348136386103
 * 8136386103   → 2348136386103
 * +2348136386103 → 2348136386103
 */
private function formatPhoneNumber(string $phone): string
{
    // Remove all spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);

    // Remove leading +
    if (str_starts_with($phone, '+')) {
        $phone = substr($phone, 1);
    }

    // Already in international format
    if (str_starts_with($phone, '234')) {
        return $phone;
    }

    // Convert 0XXXXXXXXXX to 234XXXXXXXXXX
    if (str_starts_with($phone, '0')) {
        return '234' . substr($phone, 1);
    }

    // Add 234 prefix if missing
    return '234' . $phone;
}
}
