<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function __construct(private readonly WatermarkService $watermark)
    {
    }

    // ── POST /api/auth/register/buyer ──────────────────────────────────────────
    /**
     * Registers a buyer (SignupPage in Flutter).
     * Only name, email, phone, password required — no business fields.
     */
    public function registerBuyer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role'     => 'buyer',
        ]);

        $token = $user->createToken('buyer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Buyer account created successfully.',
            'data'    => [
                'user'  => $this->buyerResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    // ── POST /api/auth/register/vendor ─────────────────────────────────────────
    /**
     * Registers a vendor (RegisterScreen in Flutter).
     * Includes business profile fields + optional logo upload.
     * vendor_status defaults to 'pending' until approved.
     */
   public function registerVendor(Request $request): JsonResponse
{
    $data = $request->validate([
        'name'                 => ['required', 'string', 'max:255'],
        'email'                => ['required', 'email', 'unique:users,email'],
        'phone'                => ['required', 'string', 'max:20'],
        'password'             => ['required', 'confirmed', Password::min(8)],
        'business_name'        => ['required', 'string', 'max:255'],
        'business_category'    => ['nullable', 'string', 'max:100'],
        'business_address'     => ['required', 'string', 'max:255'],
        'state'                => ['nullable', 'string', 'max:100'],
        'city'                 => ['nullable', 'string', 'max:100'],
        'business_description' => ['nullable', 'string', 'max:1000'],
        'logo'                 => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
    ]);

    // Handle logo upload
    $logoPath = null;
    if ($request->hasFile('logo')) {
        $logoPath = $request->file('logo')->store('vendors/logos', 'public');
    }

    $user = User::create([
        'name'                 => $data['name'],
        'email'                => strtolower($data['email']), // Normalize email to lowercase
        'phone'                => $data['phone'],
        'password'             => Hash::make($data['password']),
        'role'                 => 'vendor',
        'vendor_status'        => 'pending',
        'business_name'        => $data['business_name'],
        'business_category'    => data_get($data, 'business_category'),
        'business_address'     => $data['business_address'],
        'state'                => data_get($data, 'state'),
        'city'                 => data_get($data, 'city'),
        'business_description' => data_get($data, 'business_description'),
        'logo_path'            => $logoPath,
    ]);

    $token = $user->createToken('vendor-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Vendor account created. Pending approval.',
        'data'    => [
            'user'  => $this->vendorResource($user),
            'token' => $token,
        ],
    ], 201);
}
    // ── POST /api/auth/login/buyer ─────────────────────────────────────────────
    /**
     * Login for buyers (SigninScreen in Flutter).
     * Returns 403 if the account is actually a vendor.
     */
    public function loginBuyer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->role !== 'buyer') {
            return response()->json([
                'success' => false,
                'message' => 'This account is registered as a vendor. Please use the vendor login.',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('buyer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Welcome back!',
            'data'    => [
                'user'  => $this->buyerResource($user),
                'token' => $token,
            ],
        ]);
    }

    // ── POST /api/auth/login/vendor ────────────────────────────────────────────
    /**
     * Login for vendors (LoginScreen in Flutter).
     * Returns 403 if account is a buyer, or if vendor_status is suspended.
     * Returns pending status clearly so the app can show an approval screen.
     */
 public function loginVendor(Request $request): JsonResponse
{
    // 1. Validate the input
    $data = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    // 2. Find user using case-insensitive search
    // Using trim() ensures no accidental leading/trailing spaces from Postman
    $email = trim(strtolower($data['email']));
    $user = User::where('email', $email)->first();

    // 3. Verify user existence and password
    // We check them separately internally if you need to debug,
    // but keep the response generic for security.
    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.',
        ], 401);
    }

    // 4. Role Authorization
    // Ensure the user trying to log in via the vendor portal is actually a vendor
    if ($user->role !== 'vendor') {
        return response()->json([
            'success' => false,
            'message' => 'This account is registered as a buyer. Please use the buyer login.',
        ], 403);
    }

    // 5. Account Status Verification
    if ($user->vendor_status === 'suspended') {
        return response()->json([
            'success' => false,
            'message' => 'Your account has been suspended. Please contact support.',
        ], 403);
    }

    // 6. Token Management
    // Revoke previous tokens to prevent multiple sessions (optional, but cleaner)
    $user->tokens()->delete();

    // Create the new Sanctum token
    $token = $user->createToken('vendor-token')->plainTextToken;

    // 7. Success Response
    return response()->json([
        'success'       => true,
        'message'       => $user->vendor_status === 'pending'
            ? 'Login successful. Your account is awaiting approval.'
            : 'Welcome back!',
        'vendor_status' => $user->vendor_status, // Useful for Flutter routing
        'data'          => [
            'user'  => $this->vendorResource($user),
            'token' => $token,
        ],
    ]);
}
    // ── POST /api/auth/logout ──────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    // ── GET /api/auth/me ───────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $user->isVendor()
                ? $this->vendorResource($user)
                : $this->buyerResource($user),
        ]);
    }

    // ── PUT /api/auth/vendor/profile ───────────────────────────────────────────
    /**
     * Lets a vendor update their business profile + logo.
     */
    public function updateVendorProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isVendor()) {
            return response()->json(['success' => false, 'message' => 'Not a vendor account.'], 403);
        }

        $data = $request->validate([
            'business_name'        => ['sometimes', 'string', 'max:255'],
            'business_category'    => ['sometimes', 'string', 'max:100'],
            'business_address'     => ['sometimes', 'string', 'max:255'],
            'state'                => ['sometimes', 'string', 'max:100'],
            'city'                 => ['sometimes', 'string', 'max:100'],
            'business_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'phone'                => ['sometimes', 'string', 'max:20'],
            'logo'                 => ['sometimes', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('vendors/logos', 'public');
        }

        unset($data['logo']); // remove the file key before mass-assigning
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data'    => $this->vendorResource($user->fresh()),
        ]);
    }

    // ── Private resource formatters ────────────────────────────────────────────

    private function buyerResource(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'role'        => $user->role,
            'is_verified' => $user->is_verified,
        ];
    }

    private function vendorResource(User $user): array
    {
        return [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'email'                => $user->email,
            'phone'                => $user->phone,
            'role'                 => $user->role,
            'vendor_status'        => $user->vendor_status,
            'is_verified'          => $user->is_verified,
            'business_name'        => $user->business_name,
            'business_category'    => $user->business_category,
            'business_address'     => $user->business_address,
            'state'                => $user->state,
            'city'                 => $user->city,
            'business_description' => $user->business_description,
            'logo_url'             => $user->logo_url,
        ];
    }

    public function updateBuyerProfilePhoto(Request $request)
{
    // 1. Validate the incoming file
    $request->validate([
        'profile_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $user = $request->user();

    // 2. Handle the file upload
    if ($request->hasFile('profile_photo')) {
        // Delete the old photo if it exists to save space
        if ($user->profile_photo) {
            \Storage::disk('public')->delete($user->profile_photo);
        }

        // Store the new photo
        $path = $request->file('profile_photo')->store('profiles/buyers', 'public');

        // 3. Update the database
        $user->update(['profile_photo' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Buyer profile photo updated successfully',
            'data' => [
                'photo_url' => asset('storage/' . $path)
            ]
        ]);
    }

    return response()->json(['message' => 'No file detected'], 400);
}
}
