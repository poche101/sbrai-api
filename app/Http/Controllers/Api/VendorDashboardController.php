<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdFavorite;
use App\Models\AdView;
use App\Models\Chat;
use App\Models\VendorActivity;
use App\Models\VendorVoucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorDashboardController extends Controller
{
    // ── GET /api/vendor/dashboard ──────────────────────────────────────────────
    /**
     * Returns everything the Flutter VendorDashboardScreen needs in one call:
     *   - stats (activeListings, totalViews, messages, totalSales)
     *   - voucherBalance
     *   - activities (last 10)
     *   - products (vendor's ads with engagement counts)
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();

        // ── 1. Active listings ─────────────────────────────────────────────────
        $activeListings = Ad::where('user_id', $vendor->id)
            ->where('status', 'active')
            ->count();

        // ── 2. Total views across all vendor ads ───────────────────────────────
        $totalViews = AdView::whereHas('ad', fn($q) => $q->where('user_id', $vendor->id))
            ->count();

        // ── 3. Unread message threads ──────────────────────────────────────────
        $unreadMessages = Chat::where('vendor_id', $vendor->id)
            ->where('vendor_read', false)
            ->count();

        // ── 4. Total sales (placeholder — wire to orders when implemented) ─────
        $totalSales = '₦ 0'; // Replace with Order model sum when available

        // ── 5. Voucher balance ─────────────────────────────────────────────────
        $voucher = VendorVoucher::firstOrCreate(
            ['vendor_id' => $vendor->id],
            ['balance'   => 0.00]
        );

        // ── 6. Recent activity (last 10) ───────────────────────────────────────
        $activities = VendorActivity::where('vendor_id', $vendor->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'type'      => $a->type,
                'title'     => $a->title,
                'icon_name' => $a->icon_name,
                'color_hex' => $a->color_hex,
                'time'      => $a->created_at->diffForHumans(),
            ]);

        // ── 7. Vendor's listings with engagement counts ────────────────────────
        $products = Ad::where('user_id', $vendor->id)
            ->with(['images', 'category'])
            ->withEngagementCounts()
            ->latest()
            ->get()
            ->map(fn($ad) => $this->formatAdForDashboard($ad));

        return response()->json([
            'success' => true,
            'data'    => [
                'stats' => [
                    'active_listings' => (string) $activeListings,
                    'total_views'     => (string) $totalViews,
                    'messages'        => (string) $unreadMessages,
                    'total_sales'     => $totalSales,
                ],
                'voucher_balance' => (float) $voucher->balance,
                'activities'      => $activities,
                'products'        => $products,
            ],
        ]);
    }

    // ── GET /api/vendor/analytics ──────────────────────────────────────────────
    /**
     * Performance breakdown used by the Analytics tab.
     *   - profileViews: total views across all ads
     *   - activeListings count
     *   - responseRate: % of chats the vendor has replied to
     *   - topListings: top 5 ads by view count
     */
    public function analytics(Request $request): JsonResponse
    {
        $vendor   = $request->user();
        $adIds    = Ad::where('user_id', $vendor->id)->pluck('id');
        $adCount  = $adIds->count();

        // Total views
        $profileViews = AdView::whereIn('ad_id', $adIds)->count();

        // Response rate: chats where vendor sent at least one reply
        $totalChats   = Chat::where('vendor_id', $vendor->id)->count();
        $repliedChats = Chat::where('vendor_id', $vendor->id)
            ->whereHas('messages', fn($q) => $q->where('sender_id', $vendor->id))
            ->count();
        $responseRate = $totalChats > 0
            ? round(($repliedChats / $totalChats) * 100)
            : 0;

        // Top performing listings by views
        $topListings = Ad::whereIn('id', $adIds)
            ->with(['images', 'category'])
            ->withEngagementCounts()
            ->orderByDesc('views_count')
            ->limit(5)
            ->get()
            ->map(fn($ad) => $this->formatAdForDashboard($ad));

        // Views over the last 7 days (for a simple sparkline)
        $viewsByDay = AdView::whereIn('ad_id', $adIds)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn($row) => [$row->date => $row->count]);

        return response()->json([
            'success' => true,
            'data'    => [
                'profile_views'    => $profileViews,
                'active_listings'  => $adCount,
                'response_rate'    => $responseRate,
                'top_listings'     => $topListings,
                'views_last_7days' => $viewsByDay,
            ],
        ]);
    }

    // ── GET /api/vendor/ads/{id} ───────────────────────────────────────────────
    /**
     * Returns the full detail of a single vendor listing, including all images
     * and category info. Used to pre-populate the Edit Listing screen in Flutter.
     */
    public function showListing(Request $request, int $adId): JsonResponse
    {
        $ad = Ad::where('user_id', $request->user()->id)
            ->with(['images', 'category'])
            ->withEngagementCounts()
            ->findOrFail($adId);

        return response()->json([
            'success' => true,
            'data'    => $this->formatAdForDashboard($ad),
        ]);
    }

    // ── PUT /api/vendor/ads/{id} ───────────────────────────────────────────────
    /**
     * Updates a vendor's own listing.
     *
     * Accepts multipart/form-data so images can optionally be re-uploaded.
     * Fields:
     *   title        (string, required)
     *   description  (string, optional)
     *   price        (numeric, required)
     *   price_unit   (string, optional — e.g. "per kg", "per piece")
     *   location     (string, optional)
     *   status       (active|inactive|sold, optional)
     *   category_id  (integer, optional)
     *   type         (string, optional)
     *   images[]     (file, optional — replaces ALL existing images when provided)
     *   keep_images  (comma-separated image IDs to retain, optional)
     *
     * Only the listing's owner may update it (enforced via policy check below).
     */
    public function updateListing(Request $request, int $adId): JsonResponse
    {
        $vendor = $request->user();

        // Ownership check — 404 if it doesn't belong to this vendor
        $ad = Ad::where('user_id', $vendor->id)->findOrFail($adId);

        $validated = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'price_unit'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'location'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'status'      => ['sometimes', Rule::in(['active', 'inactive', 'sold'])],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'type'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'images'      => ['sometimes', 'array', 'max:10'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB each
            'keep_images' => ['sometimes', 'nullable', 'string'], // "1,2,5"
        ]);

        DB::beginTransaction();

        try {
            // ── Core fields ────────────────────────────────────────────────────
            $ad->fill(collect($validated)->except(['images', 'keep_images'])->toArray());
            $ad->save();

            // ── Image handling ─────────────────────────────────────────────────
            if ($request->hasFile('images')) {
                // Determine which existing image IDs the client wants to keep
                $keepIds = collect(
                    $request->filled('keep_images')
                        ? explode(',', $request->input('keep_images'))
                        : []
                )->map('intval')->filter()->values();

                // Delete images not in the keep list
                $ad->images()
                    ->when($keepIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $keepIds))
                    ->each(function ($img) {
                        Storage::disk('public')->delete($img->path);
                        $img->delete();
                    });

                // Store and attach new images
                foreach ($request->file('images') as $file) {
                    $path = $file->store("ads/{$ad->id}", 'public');
                    $ad->images()->create([
                        'path' => $path,
                        'url'  => Storage::disk('public')->url($path),
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing. Please try again.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        // Log the activity
        VendorActivity::log(
            $vendor->id,
            'edit',
            "You updated your listing: {$ad->title}",
            $ad
        );

        // Return the freshly loaded ad
        $ad->load(['images', 'category']);
        $ad->loadCount(['views as views_count', 'favorites as favorites_count', 'chats as chats_count']);

        return response()->json([
            'success' => true,
            'message' => 'Listing updated successfully.',
            'data'    => $this->formatAdForDashboard($ad),
        ]);
    }

    // ── DELETE /api/vendor/ads/{id} ────────────────────────────────────────────
    /**
     * Permanently deletes a vendor's own listing, its images (from disk + DB),
     * and all associated AdView / AdFavorite records.
     *
     * Only the listing's owner may delete it.
     */
    public function deleteListing(Request $request, int $adId): JsonResponse
    {
        $vendor = $request->user();

        // Ownership check — 404 if it doesn't belong to this vendor
        $ad = Ad::where('user_id', $vendor->id)
            ->with('images')
            ->findOrFail($adId);

        $title = $ad->title; // capture before deletion for the activity log

        DB::beginTransaction();

        try {
            // Delete image files from storage
            foreach ($ad->images as $img) {
                Storage::disk('public')->delete($img->path);
            }

            // Cascade deletes views, favorites, images via DB constraints or explicit cleanup
            AdView::where('ad_id', $ad->id)->delete();
            AdFavorite::where('ad_id', $ad->id)->delete();
            $ad->images()->delete();
            $ad->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete listing. Please try again.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        // Log the activity after a successful delete
        VendorActivity::log(
            $vendor->id,
            'delete',
            "You deleted your listing: {$title}",
            null   // ad no longer exists, pass null
        );

        return response()->json([
            'success' => true,
            'message' => 'Listing deleted successfully.',
        ]);
    }

    // ── POST /api/vendor/ads/{id}/view ─────────────────────────────────────────
    /**
     * Records a view event when a buyer opens an ad.
     * Called by the buyer's app, not the vendor's.
     * Deduplicates: one view per (ad + user/IP) per calendar day.
     */
    public function recordView(Request $request, int $adId): JsonResponse
    {
        $ad = Ad::findOrFail($adId);

        $viewerId = auth()->id();
        $ip       = $request->ip();

        $exists = AdView::where('ad_id', $adId)
            ->when($viewerId, fn($q) => $q->where('viewer_id', $viewerId),
                              fn($q) => $q->where('ip_address', $ip))
            ->whereDate('created_at', today())
            ->exists();

        if (!$exists) {
            AdView::create([
                'ad_id'      => $adId,
                'viewer_id'  => $viewerId,
                'ip_address' => $ip,
            ]);

            if ($ad->user_id) {
                VendorActivity::log(
                    $ad->user_id,
                    'view',
                    "New view on {$ad->title}",
                    $ad
                );
            }
        }

        return response()->json(['success' => true]);
    }

    // ── POST /api/ads/{id}/favorite ────────────────────────────────────────────
    /**
     * Toggle favorite on/off for the authenticated buyer.
     * Returns { favorited: true|false, count: N }
     */
    public function toggleFavorite(Request $request, int $adId): JsonResponse
    {
        $ad   = Ad::findOrFail($adId);
        $user = $request->user();

        $existing = AdFavorite::where('ad_id', $adId)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            AdFavorite::create(['ad_id' => $adId, 'user_id' => $user->id]);
            $favorited = true;

            if ($ad->user_id) {
                VendorActivity::log(
                    $ad->user_id,
                    'favorite',
                    "Someone liked your listing: {$ad->title}",
                    $ad
                );
            }
        }

        $count = AdFavorite::where('ad_id', $adId)->count();

        return response()->json([
            'success'   => true,
            'favorited' => $favorited,
            'count'     => $count,
        ]);
    }

    // ── GET /api/vendor/voucher ────────────────────────────────────────────────
    public function voucher(Request $request): JsonResponse
    {
        $voucher = VendorVoucher::firstOrCreate(
            ['vendor_id' => $request->user()->id],
            ['balance'   => 0.00]
        );

        $transactions = $voucher->transactions()
            ->limit(20)
            ->get(['type', 'amount', 'balance_after', 'description', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => [
                'balance'      => (float) $voucher->balance,
                'transactions' => $transactions,
            ],
        ]);
    }

    // ── Private formatter ──────────────────────────────────────────────────────

    private function formatAdForDashboard(Ad $ad): array
    {
        $firstImage = $ad->images->first();

        return [
            'id'         => $ad->id,
            'title'      => $ad->title,
            'description'=> $ad->description ?? '',
            'price'      => '₦ ' . number_format((float) $ad->price, 0),
            'price_raw'  => (float) $ad->price,
            'price_unit' => $ad->price_unit,
            'image_url'  => $firstImage?->url ?? '',
            'images'     => $ad->images->map(fn($img) => [
                'id'  => $img->id,
                'url' => $img->url,
            ])->values(),
            'category'   => $ad->category?->name ?? '',
            'category_id'=> $ad->category_id,
            'type'       => $ad->type,
            'status'     => $ad->status,
            'location'   => $ad->location,
            'views'      => $ad->views_count ?? 0,
            'favorites'  => $ad->favorites_count ?? 0,
            'chats'      => $ad->chats_count ?? 0,
            'created_at' => $ad->created_at->toDateTimeString(),
            'updated_at' => $ad->updated_at->toDateTimeString(),
        ];
    }
}
