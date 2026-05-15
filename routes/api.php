<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\VendorDashboardController;
use App\Http\Controllers\Api\FavoritesController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\VendorSettingsController;
use App\Http\Controllers\Api\VendorProfileController;
use App\Http\Controllers\Api\BuyerProfileController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\AgoraController;
use App\Http\Controllers\Api\KycController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SocialAuthController;

/*
|--------------------------------------------------------------------------
| SBRAI Solutions – API Routes (Version 1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    Route::get('/login', function () {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    })->name('login');

    // ── Social Auth (Public) ───────────────────────────────────────────────────
    Route::prefix('auth/social')->group(function () {
        Route::post('google',   [SocialAuthController::class, 'googleAuth']);
        Route::post('facebook', [SocialAuthController::class, 'facebookAuth']);
    });

    // ── Translation System ─────────────────────────────────────────────────────
    Route::prefix('translations')->group(function () {
        Route::get('locales',                 [TranslationController::class, 'locales']);
        Route::get('version',                 [TranslationController::class, 'version']);
        Route::get('{locale}',                [TranslationController::class, 'byLocale']);
        Route::get('{locale}/{group}',        [TranslationController::class, 'byGroup']);
        Route::post('{locale}/{group}/{key}', [TranslationController::class, 'upsert']);
    });

    // ── Auth ───────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {

        // Public
        Route::post('register/buyer',  [AuthController::class, 'registerBuyer']);
        Route::post('register/vendor', [AuthController::class, 'registerVendor']);
        Route::post('login/buyer',     [AuthController::class, 'loginBuyer']);
        Route::post('login/vendor',    [AuthController::class, 'loginVendor']);

        // Protected
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout',         [AuthController::class, 'logout']);
            Route::get('me',              [AuthController::class, 'me']);
            Route::post('vendor/profile', [AuthController::class, 'updateVendorProfile']);

            // FCM token — saved on login from Flutter so calls/chat
            // push notifications can reach the device
            Route::post('fcm-token', function (\Illuminate\Http\Request $request) {
                $request->validate(['fcm_token' => 'required|string']);
                $request->user()->update(['fcm_token' => $request->fcm_token]);
                return response()->json(['status' => true]);
            });
        });
    });

    // ── Categories (Public) ────────────────────────────────────────────────────
    Route::prefix('categories')->group(function () {
        Route::get('/',       [CategoryController::class, 'index']);
        Route::get('/{type}', [CategoryController::class, 'byType']);
    });

    // ── Public Ad Routes ───────────────────────────────────────────────────────
    Route::get('ads',      [AdController::class, 'index']);
    Route::get('ads/{id}', [AdController::class, 'show'])->where('id', '[0-9]+');

    // Ad Engagement
    Route::post('ads/{id}/view', [VendorDashboardController::class, 'recordView'])
        ->where('id', '[0-9]+');
    Route::middleware('auth:sanctum')
        ->post('ads/{id}/favorite', [VendorDashboardController::class, 'toggleFavorite'])
        ->where('id', '[0-9]+');

    // ── KYC Verification (Shared – Buyer & Vendor) ─────────────────────────────
    // Both buyers and vendors go through the same KYC flow.
    // The Flutter KYCScreen is shared between both roles.
    Route::middleware('auth:sanctum')->prefix('kyc')->group(function () {

        // GET  /api/v1/kyc/status
        Route::get('status', [KycController::class, 'status']);

        // ── Email ──────────────────────────────────────────────────────────────
        Route::post('email/send',   [KycController::class, 'sendEmailOtp']);
        Route::post('email/verify', [KycController::class, 'verifyEmail']);

        // ── Phone ──────────────────────────────────────────────────────────────
        Route::post('phone/send',   [KycController::class, 'sendPhoneOtp']);
        Route::post('phone/verify', [KycController::class, 'verifyPhone']);

        // ── Identity ───────────────────────────────────────────────────────────
        // POST /api/v1/kyc/identity/verify
        // Accepts: nin (required), document (optional file)
        Route::post('identity/verify', [KycController::class, 'verify']);
    });

    // ── Buyer Protected Routes ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->prefix('buyers')->group(function () {

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/',             [BuyerProfileController::class, 'show']);
            Route::put('update',        [BuyerProfileController::class, 'update']);
            Route::post('upload-photo', [BuyerProfileController::class, 'uploadPhoto']);
        });

        // Favorites
        // Toggle favorite (most important for frontend)
        Route::post('/ads/{ad}/favorite', [FavoritesController::class, 'toggle']);
        Route::get('favorites',           [FavoritesController::class, 'index']);
        Route::delete('favorites/{adId}', [FavoritesController::class, 'destroy'])
            ->where('adId', '[0-9]+');
    });

    // ── Messaging / Chats (Shared – Buyer & Vendor) ────────────────────────────
    Route::middleware('auth:sanctum')->prefix('chats')->group(function () {
        Route::get('/',              [ChatController::class, 'index']);
        Route::post('/',             [ChatController::class, 'start']);
        Route::get('{id}/messages',  [ChatController::class, 'messages']);
        Route::post('{id}/messages', [ChatController::class, 'send']);
        Route::post('{id}/read',     [ChatController::class, 'markRead']);
    });

    // ── Calls (Shared – Buyer & Vendor) ───────────────────────────────────────
    // Both buyers and vendors can initiate and receive calls.
    // Placed outside vendor-only middleware so buyers can call vendors too.
    Route::middleware('auth:sanctum')->prefix('calls')->group(function () {

        // POST /api/v1/calls/token
        // Body: { "channel_name": "uuid-string", "uid": 1 }
        Route::post('token',    [AgoraController::class, 'generateToken']);

        // POST /api/v1/calls/initiate
        // Body: { "receiver_id": 5, "channel_name": "uuid", "caller_name": "John", "call_type": "audio|video" }
        Route::post('initiate', [AgoraController::class, 'initiateCall']);

        // POST /api/v1/calls/end
        // Body: { "receiver_id": 5, "channel_name": "uuid" }
        Route::post('end',      [AgoraController::class, 'endCall']);
    });

    // ── Vendor Protected Routes ────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'vendor'])->prefix('vendor')->group(function () {

        // Dashboard & Analytics
        Route::get('dashboard', [VendorDashboardController::class, 'index']);
        Route::get('analytics', [VendorDashboardController::class, 'analytics']);

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/',      [VendorProfileController::class, 'show']);
            Route::match(['POST', 'PATCH'], '/', [VendorProfileController::class, 'update']);
            Route::post('photo', [VendorProfileController::class, 'updatePhoto']);
            Route::post('logo',  [VendorProfileController::class, 'updateLogo']);
        });

        // Voucher / Wallet
        Route::prefix('voucher')->group(function () {
            Route::get('/',            [VoucherController::class, 'show']);
            Route::post('topup',       [VoucherController::class, 'topUp']);
            Route::post('spend',       [VoucherController::class, 'spend']);
            Route::get('transactions', [VoucherController::class, 'transactions']);
        });

        // Settings & Account
        Route::prefix('settings')->group(function () {
            Route::get('/',                [VendorSettingsController::class, 'show']);
            Route::patch('/',              [VendorSettingsController::class, 'update']);
            Route::get('options',          [VendorSettingsController::class, 'options']);
            Route::post('change-password', [VendorSettingsController::class, 'changePassword']);
            Route::delete('account',       [VendorSettingsController::class, 'deleteAccount']);
        });

        // ── Ad / Listing Management ────────────────────────────────────────────
        //
        // AdController handles the standard CRUD flow (store, bulk update, destroy)
        // that was already in place. The three VendorDashboardController routes below
        // add the per-listing show, edit, and delete actions introduced to support
        // the Flutter Edit/Delete Listing screens. Both controller scopes enforce
        // ownership — every query is filtered to the authenticated vendor's user_id.
        //
        //  GET    /api/v1/vendor/ads/my          → list all of the vendor's ads
        //  POST   /api/v1/vendor/ads             → create a new ad
        //  GET    /api/v1/vendor/ads/{id}        → fetch a single ad (pre-fill edit form)
        //  PUT    /api/v1/vendor/ads/{id}        → update title/price/images/status/etc.
        //  POST   /api/v1/vendor/ads/{id}        → same as PUT for multipart clients
        //                                          that cannot send PUT with files
        //  DELETE /api/v1/vendor/ads/{id}        → permanently delete ad + images

        Route::get('ads/my', [AdController::class, 'myAds']);
        Route::post('ads',   [AdController::class, 'store']);

        Route::where(['id' => '[0-9]+'])->group(function () {

            // Show a single listing (for the Edit Listing pre-fill)
            Route::get('ads/{id}', [VendorDashboardController::class, 'showListing']);

            // Update listing — accepts PUT or POST (multipart workaround)
            Route::put('ads/{id}',  [VendorDashboardController::class, 'updateListing']);
            Route::post('ads/{id}', [VendorDashboardController::class, 'updateListing']);

            // Permanently delete a listing
            Route::delete('ads/{id}', [VendorDashboardController::class, 'deleteListing']);
        });
    });

});
