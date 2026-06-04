<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdFavorite;

class FavoritesController extends Controller
{
    /**
     * Display a listing of the user's favorites
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = AdFavorite::where('user_id', $user->id)
            ->with(['ad'])           // Load the ad details
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $favorites
        ]);
    }

    /**
     * Toggle favorite (Add or Remove)
     */
    public function toggle(Request $request, $adId)
    {
        $user = $request->user();

        $favorite = AdFavorite::where('user_id', $user->id)
                              ->where('ad_id', $adId)
                              ->first();

        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            $message = 'Removed from favorites';
        } else {
            // Add to favorites
            AdFavorite::create([
                'user_id' => $user->id,
                'ad_id'   => $adId,
            ]);
            $message = 'Added to favorites';
        }

        return response()->json([
            'status'  => 'success',
            'message' => $message
        ]);
    }

    /**
 * Remove a specific favorite by ad ID
 */
public function destroy(Request $request, $adId)
{
    $user = $request->user();

    $deleted = AdFavorite::where('user_id', $user->id)
                         ->where('ad_id', $adId)
                         ->delete();

    if (!$deleted) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Favorite not found',
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Removed from favorites',
    ]);
}
}
