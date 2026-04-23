<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\VendorActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class ChatController extends Controller
{
    // ── GET /api/v1/chats ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $field = $user->isVendor() ? 'vendor_id' : 'buyer_id';

        $chats = Chat::where($field, $user->id)
            ->with([
                'ad:id,title,type',
                'ad.images',
                'latestMessage',
                'buyer:id,name,profile_photo',
                'vendor:id,name,business_name,logo_path',
            ])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $chats,
        ]);
    }

    // ── POST /api/v1/chats ─────────────────────────────────────────────────────

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ad_id'   => ['required', 'integer', 'exists:ads,id'],
            'message' => ['required_without:image', 'nullable', 'string', 'max:1000'],
            'image'   => ['required_without:message', 'nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $buyer = $request->user();
        $ad    = Ad::findOrFail($data['ad_id']);

        if ($buyer->id === $ad->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot message yourself.',
            ], 422);
        }

        // Find or create thread
        $chat = Chat::firstOrCreate(
            ['ad_id' => $ad->id, 'buyer_id' => $buyer->id],
            ['vendor_id' => $ad->user_id, 'vendor_read' => false]
        );

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat/images', 'public');
        }

        // Create opening message
        $message = ChatMessage::create([
            'chat_id'    => $chat->id,
            'sender_id'  => $buyer->id,
            'body'       => $data['message'] ?? null,
            'image_path' => $imagePath,
        ]);

        $message->load('sender:id,name');

        $chat->update([
            'last_message_at' => now(),
            'vendor_read'     => false,
            'buyer_read'      => true,
        ]);

        // Broadcast real-time event
        broadcast(new MessageSent($message));

        // FCM push to vendor
        $this->sendPushNotification(
            recipientId: $ad->user_id,
            senderName:  $buyer->name,
            body:        $data['message'] ?? '📷 Image',
            chatId:      $chat->id,
        );

        // Vendor activity log
        if ($ad->user_id) {
            VendorActivity::log(
                $ad->user_id,
                'message',
                "New message from {$buyer->name}",
                $chat
            );
        }

        return response()->json([
            'success' => true,
            'data'    => $chat->load(['ad:id,title', 'latestMessage']),
        ], 201);
    }

    // ── GET /api/v1/chats/{id}/messages ───────────────────────────────────────

    public function messages(Request $request, int $chatId): JsonResponse
    {
        $user = $request->user();
        $chat = Chat::findOrFail($chatId);

        $this->authorizeParticipant($chat, $user->id);

        // Mark messages as read
        $readField = $user->isVendor() ? 'vendor_read' : 'buyer_read';
        $chat->update([$readField => true]);

        $updated = ChatMessage::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Broadcast read receipt if there were unread messages
        if ($updated > 0) {
            broadcast(new MessageRead($chat->id, $user->id));
        }

        $messages = ChatMessage::where('chat_id', $chat->id)
            ->with('sender:id,name')
            ->latest()
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    // ── POST /api/v1/chats/{id}/messages ──────────────────────────────────────

    public function send(Request $request, int $chatId): JsonResponse
    {
        $request->validate([
            'message' => ['required_without:image', 'nullable', 'string', 'max:1000'],
            'image'   => ['required_without:message', 'nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $user = $request->user();
        $chat = Chat::findOrFail($chatId);

        $this->authorizeParticipant($chat, $user->id);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat/images', 'public');
        }

        $message = ChatMessage::create([
            'chat_id'    => $chat->id,
            'sender_id'  => $user->id,
            'body'       => $request->message ?? null,
            'image_path' => $imagePath,
        ]);

        $message->load('sender:id,name');

        // Update thread metadata
        $isVendorSending = $user->isVendor();
        $chat->update([
            'last_message_at' => now(),
            'vendor_read'     => $isVendorSending,
            'buyer_read'      => ! $isVendorSending,
        ]);

        // Broadcast real-time event to both participants
        broadcast(new MessageSent($message));

        // FCM push to the other party
        $recipientId = $isVendorSending ? $chat->buyer_id : $chat->vendor_id;
        $this->sendPushNotification(
            recipientId: $recipientId,
            senderName:  $user->name,
            body:        $request->message ?? '📷 Image',
            chatId:      $chat->id,
        );

        // Vendor activity log
        if (! $isVendorSending) {
            VendorActivity::log(
                $chat->vendor_id,
                'message',
                "New message from {$user->name}",
                $chat
            );
        }

        return response()->json([
            'success' => true,
            'data'    => $message,
        ], 201);
    }

    // ── POST /api/v1/chats/{id}/read ──────────────────────────────────────────

    public function markRead(Request $request, int $chatId): JsonResponse
    {
        $user = $request->user();
        $chat = Chat::findOrFail($chatId);

        $this->authorizeParticipant($chat, $user->id);

        $readField = $user->isVendor() ? 'vendor_read' : 'buyer_read';
        $chat->update([$readField => true]);

        $updated = ChatMessage::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated > 0) {
            broadcast(new MessageRead($chat->id, $user->id));
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
        ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function authorizeParticipant(Chat $chat, int $userId): void
    {
        if ($chat->vendor_id !== $userId && $chat->buyer_id !== $userId) {
            abort(response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403));
        }
    }

    private function sendPushNotification(
        int    $recipientId,
        string $senderName,
        string $body,
        int    $chatId
    ): void {
        try {
            $recipient = \App\Models\User::find($recipientId);

            if (! $recipient?->fcm_token) return;

            $factory   = (new Factory)->withServiceAccount(
                storage_path('app/firebase-credentials.json')
            );
            $messaging = $factory->createMessaging();

            $message = CloudMessage::withTarget('token', $recipient->fcm_token)
                ->withData([
                    'type'      => 'new_message',
                    'chat_id'   => (string) $chatId,
                    'sender'    => $senderName,
                    'body'      => $body,
                ])
                ->withAndroidConfig(['priority' => 'high'])
                ->withApnsConfig([
                    'headers' => ['apns-priority' => '10'],
                ]);

            $messaging->send($message);

        } catch (\Throwable $e) {
            // Non-fatal — message is saved, push is best-effort
            Log::warning('Chat FCM push failed: ' . $e->getMessage());
        }
    }
}
