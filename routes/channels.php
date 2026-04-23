<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::find($chatId);
    return $chat && (
        $user->id === $chat->buyer_id ||
        $user->id === $chat->vendor_id
    );
});
