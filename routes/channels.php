<?php

use App\Models\Inquiry;
use App\Models\User;
use App\Policies\MessagePolicy;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('inquiry.{inquiryId}.v{accessVersion}', function (User $user, string $inquiryId, string $accessVersion): bool {
    $inquiry = ctype_digit($inquiryId) ? Inquiry::find($inquiryId) : null;
    $currentUser = $user->fresh();

    return $inquiry && $currentUser && (string) $inquiry->access_version === $accessVersion
        && (new MessagePolicy)->viewHistory($currentUser, $inquiry);
});
