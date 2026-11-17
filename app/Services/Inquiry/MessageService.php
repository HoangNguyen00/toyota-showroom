<?php

namespace App\Services\Inquiry;

use App\Enums\InquiryStatus;
use App\Enums\UserRole;
use App\Jobs\BroadcastInquiryMessage;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\User;
use App\Policies\MessagePolicy;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\Queue;
use LogicException;

class MessageService
{
    public function send(User $actor, Inquiry $inquiry, array $data): array
    {
        return app(InquiryLock::class)->run($inquiry->id, [$actor->id], function ($locked, $users) use ($actor, $data) {
            $actor = $users->get($actor->id);
            abort_unless($actor && (new MessagePolicy)->viewHistory($actor, $locked), 403);
            abort_unless((new MessagePolicy)->send($actor, $locked), 403);
            $existing = Message::where('sender_id', $actor->id)
                ->where('client_message_id', $data['client_message_id'])->first();
            if ($existing) {
                abort_unless($existing->inquiry_id === $locked->id && $existing->content === $data['content'], 409, 'UUID đã dùng với nội dung khác.');

                return [$existing->load('sender'), false];
            }
            // All sends lock sender first. The existing DB unique key is the final guard.
            $message = Message::create([
                'inquiry_id' => $locked->id, 'sender_id' => $actor->id,
                'content' => $data['content'], 'client_message_id' => $data['client_message_id'],
            ]);
            if ($actor->role === UserRole::Sales && $locked->status === InquiryStatus::Assigned) {
                $locked->update(['status' => InquiryStatus::InProgress]);
            }
            // Persist the delivery intent in the SAME transaction/connection as the message.
            // Other workers cannot see either row until commit; enqueue failure rolls both back.
            $queue = Queue::connection('database');
            if (! $queue instanceof DatabaseQueue || $queue->getDatabase() !== $message->getConnection()) {
                throw new LogicException('Inquiry messages require a database queue on the message connection.');
            }
            $queue->push((new BroadcastInquiryMessage($message->id, $locked->access_version))->beforeCommit());

            return [$message->load('sender'), true];
        });
    }
}
