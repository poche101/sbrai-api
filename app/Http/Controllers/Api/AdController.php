<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Models\Ad;
use App\Models\Category;
use App\Services\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdController extends Controller
{
    public function __construct(private readonly WatermarkService $watermark)
    {
    }

    // ── GET /api/ads ───────────────────────────────────────────────────────────
    /**
     * List ads with optional filters:
     *   ?type=product|service|property
     *   ?category_id=1
     *   ?search=cement
     *   ?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ad::with(['category', 'images'])
            ->where('status', 'active')
            ->latest();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $ads = $query->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $ads,
        ]);
    }

    // ── POST /api/ads ──────────────────────────────────────────────────────────
    /**
     * Create a new ad (product, service, or property).
     * Images are watermarked before storage.
     */
    public function store(StoreAdRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // ── 1. Persist the ad record ────────────────────────────────────
            $adData = [
                'user_id'     => auth()->id(), // null if not authenticated yet
                'category_id' => $request->category_id,
                'type'        => $request->type,
                'title'       => $request->title,
                'description' => $request->description,
                'price'       => $request->price,
                'price_unit'  => $request->price_unit,
                'location'    => $request->location,
                'status'      => 'active',
            ];

            if ($request->type === 'property') {
                $adData['property_status'] = $request->property_status;
                $adData['bedrooms']        = $request->bedrooms;
                $adData['sqft']            = $request->sqft;
            }

            $ad = Ad::create($adData);

            // ── 2. Process & store each image ───────────────────────────────
            foreach ($request->file('images') as $index => $image) {
                $path = $this->watermark->processAndStore(
                    $image,
                    'ads/' . $ad->id,
                    'public'
                );

                $ad->images()->create([
                    'path'       => $path,
                    'disk'       => 'public',
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ad published successfully.',
                'data'    => $ad->load(['category', 'images']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AdController@store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to publish ad. Please try again.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ── GET /api/ads/{id} ──────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $ad = Ad::with(['category', 'images', 'user:id,name,phone'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $ad,
        ]);
    }

    // ── PUT /api/ads/{id} ──────────────────────────────────────────────────────
    public function update(StoreAdRequest $request, int $id): JsonResponse
    {
        $ad = Ad::findOrFail($id);

        // Ownership check — uncomment once auth is wired
        // if ($ad->user_id !== auth()->id()) {
        //     return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        // }

        DB::beginTransaction();

        try {
            $ad->update([
                'category_id'     => $request->category_id,
                'type'            => $request->type,
                'title'           => $request->title,
                'description'     => $request->description,
                'price'           => $request->price,
                'price_unit'      => $request->price_unit,
                'location'        => $request->location,
                'property_status' => $request->property_status,
                'bedrooms'        => $request->bedrooms,
                'sqft'            => $request->sqft,
            ]);

            // If new images are provided, replace all existing ones
            if ($request->hasFile('images')) {
                $ad->images()->delete();

                foreach ($request->file('images') as $index => $image) {
                    $path = $this->watermark->processAndStore(
                        $image,
                        'ads/' . $ad->id,
                        'public'
                    );

                    $ad->images()->create([
                        'path'       => $path,
                        'disk'       => 'public',
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ad updated successfully.',
                'data'    => $ad->load(['category', 'images']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AdController@update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Update failed. Please try again.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ── DELETE /api/ads/{id} ───────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $ad = Ad::findOrFail($id);

        // Ownership check — uncomment once auth is wired
        if ($ad->user_id !== auth()->id()) {
           return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
         }

        $ad->images()->delete();
        $ad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ad deleted successfully.',
        ]);
    }

    // ── GET /api/ads/my ────────────────────────────────────────────────────────
    /**
     * All ads belonging to the authenticated user.
     */
    public function myAds(Request $request): JsonResponse
    {
        $ads = Ad::with(['category', 'images'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $ads,
        ]);
    }
}
