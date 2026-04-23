<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    // Cache TTL — translations rarely change; 24 hours is safe.
    private const CACHE_TTL = 86400; // seconds

    // ── GET /api/v1/translations/{locale} ─────────────────────────────────────
    /**
     * Returns ALL translations for a locale, grouped by screen.
     * Flutter caches this response locally (SharedPreferences) and only
     * re-fetches when the locale changes or the cache version changes.
     *
     * Response shape:
     * {
     *   "success": true,
     *   "locale": "yo",
     *   "version": "2025-01-01",        ← Flutter uses this to detect stale cache
     *   "data": {
     *     "common":     { "loading": "Ngba...", ... },
     *     "home":       { "search_header": "Kini O Ń Wá?", ... },
     *     "categories": { "cement": "Simenti", ... },
     *     ...
     *   }
     * }
     */
    public function byLocale(string $locale): JsonResponse
    {
        if (!in_array($locale, Translation::$supportedLocales)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported locale. Supported: ' . implode(', ', Translation::$supportedLocales),
            ], 422);
        }

        $cacheKey = "translations.{$locale}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            return Translation::where('locale', $locale)
                ->get(['group', 'key', 'value'])
                ->groupBy('group')
                ->map(fn($items) => $items->pluck('value', 'key'))
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'locale'  => $locale,
            'version' => $this->cacheVersion(),
            'data'    => $data,
        ]);
    }

    // ── GET /api/v1/translations/{locale}/{group} ──────────────────────────────
    /**
     * Returns translations for a single group only.
     * Useful for lazy-loading a single screen's strings.
     *
     * Example: GET /api/v1/translations/ha/settings
     */
    public function byGroup(string $locale, string $group): JsonResponse
    {
        if (!in_array($locale, Translation::$supportedLocales)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported locale.',
            ], 422);
        }

        if (!in_array($group, Translation::$groups)) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown group. Available: ' . implode(', ', Translation::$groups),
            ], 422);
        }

        $cacheKey = "translations.{$locale}.{$group}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale, $group) {
            return Translation::where('locale', $locale)
                ->where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'locale'  => $locale,
            'group'   => $group,
            'version' => $this->cacheVersion(),
            'data'    => $data,
        ]);
    }

    // ── GET /api/v1/translations/locales ──────────────────────────────────────
    /**
     * Returns the list of supported locales with their human-readable labels.
     * Flutter calls this once on startup to populate the language picker.
     *
     * Response:
     * {
     *   "success": true,
     *   "data": [
     *     { "code": "en", "label": "English",  "is_default": true  },
     *     { "code": "yo", "label": "Yoruba",   "is_default": false },
     *     ...
     *   ]
     * }
     */
    public function locales(): JsonResponse
    {
        $locales = collect(Translation::$localeLabels)->map(fn($label, $code) => [
            'code'       => $code,
            'label'      => $label,
            'is_default' => $code === 'en',
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $locales,
        ]);
    }

    // ── GET /api/v1/translations/version ─────────────────────────────────────
    /**
     * Returns the current translation version string.
     * Flutter polls this cheaply to decide whether to re-fetch the full bundle.
     */
    public function version(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'version' => $this->cacheVersion(),
        ]);
    }

    // ── POST /api/v1/translations/{locale}/{group}/{key} (admin only) ─────────
    /**
     * Update or add a single translation string.
     * Clears the affected cache keys automatically.
     * Protected by the 'admin' middleware — not exposed to Flutter users.
     */
    public function upsert(Request $request, string $locale, string $group, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        Translation::updateOrCreate(
            compact('locale', 'group', 'key'),
            ['value' => $request->value]
        );

        // Bust cache for this locale
        Cache::forget("translations.{$locale}");
        Cache::forget("translations.{$locale}.{$group}");

        return response()->json([
            'success' => true,
            'message' => "Translation [{$locale}.{$group}.{$key}] updated.",
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * A date-stamped version string Flutter stores alongside its local cache.
     * Bump this by updating any translation row — the `updated_at` timestamp
     * of the most recently changed row becomes the version.
     */
    private function cacheVersion(): string
    {
        return Cache::remember('translations.version', self::CACHE_TTL, function () {
            $latest = Translation::max('updated_at');
            return $latest ?? now()->toDateString();
        });
    }
}
