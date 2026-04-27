<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * POST /api/v1/auth/social/google
     *
     * Receives Google ID token from Flutter, verifies it with Google,
     * finds or creates the user, and returns a Sanctum token.
     */
    public function googleAuth(Request $request): JsonResponse
    {
        $request->validate([
            'id_token'  => 'required|string',
            'user_type' => 'required|in:buyer,vendor',
        ]);

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);

        } catch (\Throwable $e) {
            Log::error('Google auth failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Invalid Google token. Please try again.',
            ], 401);
        }

        // Find by Google ID first, then fall back to email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Link Google ID if user previously signed up with email
            if (! $user->google_id) {
                $user->update([
                    'google_id'     => $googleUser->getId(),
                    'avatar'        => $googleUser->getAvatar(),
                    'auth_provider' => 'google',
                ]);
            }
        } else {
            // Create new user from Google profile
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'auth_provider'     => 'google',
                'role'              => $request->user_type,
                'password'          => bcrypt(Str::random(24)),
                'email_verified_at' => now(), // Google already verified email
                'vendor_status'     => $request->user_type === 'vendor' ? 'pending' : null,
            ]);
        }

        // Revoke old tokens and issue fresh Sanctum token
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => $user->wasRecentlyCreated
                ? 'Account created successfully.'
                : 'Login successful.',
            'data'    => [
                'user'        => $user,
                'token'       => $token,
                'token_type'  => 'Bearer',
                'is_new_user' => $user->wasRecentlyCreated,
            ],
        ], $user->wasRecentlyCreated ? 201 : 200);
    }
}
