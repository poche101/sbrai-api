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

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update([
                    'google_id'     => $googleUser->getId(),
                    'avatar'        => $googleUser->getAvatar(),
                    'auth_provider' => 'google',
                ]);
            }
        } else {
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'auth_provider'     => 'google',
                'role'              => $request->user_type,
                'password'          => bcrypt(Str::random(24)),
                'email_verified_at' => now(),
                'vendor_status'     => $request->user_type === 'vendor' ? 'pending' : null,
            ]);
        }

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

    /**
     * POST /api/v1/auth/social/facebook
     *
     * Receives Facebook access token from Flutter, verifies it with Facebook,
     * finds or creates the user, and returns a Sanctum token.
     */
    public function facebookAuth(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
            'user_type'    => 'required|in:buyer,vendor',
        ]);

        try {
            $facebookUser = Socialite::driver('facebook')
                ->stateless()
                ->userFromToken($request->access_token);

        } catch (\Throwable $e) {
            Log::error('Facebook auth failed: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Invalid Facebook token. Please try again.',
            ], 401);
        }

        // Find by Facebook ID first, then fall back to email
        $user = User::where('facebook_id', $facebookUser->getId())
            ->orWhere('email', $facebookUser->getEmail())
            ->first();

        if ($user) {
            // Link Facebook ID if user previously signed up with email or Google
            if (! $user->facebook_id) {
                $user->update([
                    'facebook_id'   => $facebookUser->getId(),
                    'avatar'        => $user->avatar ?? $facebookUser->getAvatar(),
                    'auth_provider' => $user->auth_provider ?? 'facebook',
                ]);
            }
        } else {
            // Create new user from Facebook profile
            $user = User::create([
                'name'              => $facebookUser->getName(),
                'email'             => $facebookUser->getEmail(),
                'facebook_id'       => $facebookUser->getId(),
                'avatar'            => $facebookUser->getAvatar(),
                'auth_provider'     => 'facebook',
                'role'              => $request->user_type,
                'password'          => bcrypt(Str::random(24)),
                'email_verified_at' => now(),
                'vendor_status'     => $request->user_type === 'vendor' ? 'pending' : null,
            ]);
        }

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
