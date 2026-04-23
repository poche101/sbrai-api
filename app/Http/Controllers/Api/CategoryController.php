<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * Returns all categories grouped by type.
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('type')->orderBy('name')->get();

        $grouped = $categories->groupBy('type')->map(function ($items) {
            return $items->map(fn($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values();
        });

        return response()->json([
            'success' => true,
            'data'    => $grouped,
        ]);
    }

    /**
     * GET /api/categories/{type}
     * Returns categories for a specific type: product | service | property
     */
    public function byType(string $type): JsonResponse
    {
        if (!in_array($type, ['product', 'service', 'property'])) {
            return response()->json([
                'success' => false,
                'message' => "Invalid type. Use: product, service, or property.",
            ], 422);
        }

        $categories = Category::where('type', $type)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }
}
