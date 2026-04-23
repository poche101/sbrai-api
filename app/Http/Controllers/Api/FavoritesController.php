<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    // ── GET /api/favorites ─────────────────────────────────────────────────────
    /**
     * All ads the authenticated buyer has saved.
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favoriteAds()
            ->with(['images', 'category', 'user:id,name,business_name,is_verified'])
            ->withCount(['views', 'favorites'])
            ->latest('ad_favorites.created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $favorites,
        ]);
    }

    // ── DELETE /api/favorites/{adId} ───────────────────────────────────────────
    /**
     * Remove a single saved ad (unfavorite without toggling via ad endpoint).
     */
    public function destroy(Request $request, int $adId): JsonResponse
    {
        AdFavorite::where('ad_id', $adId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from saved ads.',
        ]);
    }
}
