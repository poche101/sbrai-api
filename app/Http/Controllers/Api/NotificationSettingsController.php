<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    // The columns we manage, with their default values (all true except promotions).
    // Keeping defaults here makes it easy to add new toggles in future.
    private const DEFAULTS = [
        // Notifications
        'notif_new_listings'  => true,
        'notif_price_drops'   => true,
        'notif_messages'      => true,
        'notif_promotions'    => false,
        // Privacy & Security
        'privacy_show_online' => true,
        'privacy_show_phone'  => true,
        'privacy_allow_msgs'  => true,
    ];

    // ── GET /api/v1/settings/notifications ────────────────────────────────────
    /**
     * Return the authenticated user's current notification & privacy settings.
     * Missing columns default to the values in DEFAULTS above so the response
     * is always complete even before the user has explicitly saved anything.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->buildSettingsResource($user),
        ]);
    }

    // ── PATCH /api/v1/settings/notifications ──────────────────────────────────
    /**
     * Update one or more notification / privacy toggles.
     * Only the keys the client sends are updated (partial update).
     *
     * Body (all optional booleans):
     * {
     *   "notif_new_listings":  true,
     *   "notif_price_drops":   true,
     *   "notif_messages":      true,
     *   "notif_promotions":    false,
     *   "privacy_show_online": true,
     *   "privacy_show_phone":  true,
     *   "privacy_allow_msgs":  true
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $boolRule = ['sometimes', 'boolean'];

        $data = $request->validate([
            'notif_new_listings'  => $boolRule,
            'notif_price_drops'   => $boolRule,
            'notif_messages'      => $boolRule,
            'notif_promotions'    => $boolRule,
            'privacy_show_online' => $boolRule,
            'privacy_show_phone'  => $boolRule,
            'privacy_allow_msgs'  => $boolRule,
        ]);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid settings provided.',
            ], 422);
        }

        $request->user()->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data'    => $this->buildSettingsResource($request->user()->fresh()),
        ]);
    }

    // ── Private helper ─────────────────────────────────────────────────────────

    /**
     * Build the settings array from the user model, falling back to defaults
     * for any column that has not been set yet (NULL in the database).
     */
    private function buildSettingsResource($user): array
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            // If the column exists on the model and is not null, use it;
            // otherwise use the default so the client always gets a boolean.
            $value = $user->{$key};
            $settings[$key] = $value !== null ? (bool) $value : $default;
        }

        return $settings;
    }
}
