<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\AgoraTokenBuilder;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class AgoraController extends Controller
{
    // -------------------------------------------------------------------------
    // FCM Helper
    // -------------------------------------------------------------------------

    /**
     * Send a high-priority FCM data message using the V1 API via
     * the kreait/laravel-firebase SDK and a service account JSON file.
     * Data-only messages (no 'notification' key) wake the app on both
     * Android and iOS even when the screen is locked — same as WhatsApp.
     */
    private function sendFcmMessage(string $fcmToken, array $data): void
    {
        $factory   = (new Factory)->withServiceAccount(
            storage_path('app/firebase-credentials.json')
        );
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withData($data)                      // data-only, no visible notification
            ->withAndroidConfig([
                'priority' => 'high',              // wakes Android from Doze mode
                'ttl'      => '30s',               // discard if not delivered in 30s
            ])
            ->withApnsConfig([
                'headers' => [
                    'apns-priority'   => '10',     // immediate delivery on iOS
                    'apns-push-type'  => 'voip',   // required for iOS CallKit
                ],
            ]);

        $messaging->send($message);
    }

    // -------------------------------------------------------------------------
    // Agora Token
    // -------------------------------------------------------------------------

    /**
     * Generate an Agora RTC token for the given channel and user.
     *
     * POST /api/v1/calls/token
     * Body: { "channel_name": "uuid-string", "uid": 1 }
     */
    public function generateToken(Request $request)
    {
        $request->validate([
            'channel_name' => 'required|string',
            'uid'          => 'required|integer',
        ]);

        $appId          = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');
        $tokenExpire    = 3600; // 1 hour

        $token = AgoraTokenBuilder::buildTokenWithUid(
            $appId,
            $appCertificate,
            $request->channel_name,
            $request->uid,
            AgoraTokenBuilder::ROLE_PUBLISHER,
            $tokenExpire
        );

        return response()->json([
            'status' => true,
            'token'  => $token,
            'app_id' => $appId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Call Signalling
    // -------------------------------------------------------------------------

    /**
     * Notify the receiver of an incoming call.
     * The caller's device hits this endpoint after joining the Agora channel.
     *
     * POST /api/v1/calls/initiate
     * Body: {
     *   "receiver_id":  5,
     *   "channel_name": "uuid-string",
     *   "caller_name":  "John Doe",
     *   "call_type":    "audio|video"
     * }
     */
    public function initiateCall(Request $request)
    {
        $request->validate([
            'receiver_id'  => 'required|integer',
            'channel_name' => 'required|string',
            'caller_name'  => 'required|string',
            'call_type'    => 'required|in:audio,video',
        ]);

        $receiver = \App\Models\User::findOrFail($request->receiver_id);

        if (! $receiver->fcm_token) {
            return response()->json([
                'status'  => false,
                'message' => 'Receiver is not available.',
            ], 422);
        }

        try {
            $this->sendFcmMessage($receiver->fcm_token, [
                'type'         => 'incoming_call',
                'channel_name' => $request->channel_name,
                'caller_name'  => $request->caller_name,
                'caller_id'    => (string) auth()->id(),
                'call_type'    => $request->call_type,
            ]);
        } catch (\Throwable $e) {
            \Log::error('FCM initiateCall error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to reach receiver. They may be offline.',
            ], 502);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Call initiated successfully.',
        ]);
    }

    /**
     * Notify the receiver that the caller hung up or declined.
     * Triggers the Flutter CallKeep UI to dismiss on the receiver's device.
     *
     * POST /api/v1/calls/end
     * Body: { "receiver_id": 5, "channel_name": "uuid-string" }
     */
    public function endCall(Request $request)
    {
        $request->validate([
            'receiver_id'  => 'required|integer',
            'channel_name' => 'required|string',
        ]);

        $receiver = \App\Models\User::findOrFail($request->receiver_id);

        if (! $receiver->fcm_token) {
            return response()->json([
                'status'  => true,
                'message' => 'Call ended. Receiver had no FCM token.',
            ]);
        }

        try {
            $this->sendFcmMessage($receiver->fcm_token, [
                'type'         => 'call_ended',
                'channel_name' => $request->channel_name,
            ]);
        } catch (\Throwable $e) {
            // Non-fatal — the call is over regardless, just log it
            \Log::warning('FCM endCall error: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => true,
            'message' => 'Call ended successfully.',
        ]);
    }
}
