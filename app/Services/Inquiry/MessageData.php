<?php

namespace App\Services\Inquiry;

use App\Models\Message;

class MessageData
{
    public static function from(Message $message): array
    {
        return [
            'id' => $message->id, 'inquiry_id' => $message->inquiry_id,
            'sender' => ['id' => $message->sender_id, 'name' => $message->sender->name],
            'content' => $message->content, 'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
