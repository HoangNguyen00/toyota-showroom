<?php

namespace App\Jobs;

use App\Events\InquiryMessageCreated;
use App\Models\Message;
use App\Policies\MessagePolicy;
use App\Services\Inquiry\InquiryLock;
use App\Services\Inquiry\MessageData;
use App\Services\Inquiry\PublicationGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use LogicException;

class BroadcastInquiryMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    // Queue payload contains identifiers only, not message bodies or customer contact data.
    public function __construct(public int $messageId, public int $version) {}

    public function handle(InquiryLock $lock): void
    {
        app(PublicationGate::class)->run(fn () => $this->publish($lock));
    }

    private function publish(InquiryLock $lock): void
    {
        $message = Message::find($this->messageId);
        if (! $message) {
            return;
        }
        $payload = $lock->run($message->inquiry_id, [$message->sender_id], function ($inquiry, $users) use ($message) {
            $sender = $users->get($message->sender_id);
            $customer = $users->get($inquiry->customer_id);
            $sales = $users->get($inquiry->assigned_to);
            if ($inquiry->access_version !== $this->version || ! $sender || ! $customer?->is_active || ! $sales?->is_active
                || ! (new MessagePolicy)->send($sender, $inquiry)) {
                return null;
            }

            return MessageData::from($message->load('sender'));
        });
        // Never hold a database transaction across network I/O.
        if ($payload && $message->inquiry()->where('access_version', $this->version)->exists()) {
            if (DB::transactionLevel() !== 0) {
                throw new LogicException('Cannot publish while a database transaction is open.');
            }
            app(PublicationGate::class)->assertHeld();
            event(new InquiryMessageCreated($payload, $this->version));
        }
    }
}
