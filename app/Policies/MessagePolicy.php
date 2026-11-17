<?php

namespace App\Policies;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;

class MessagePolicy
{
    public function viewHistory(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->customer_id !== null && (new InquiryPolicy)->view($user, $inquiry);
    }

    public function send(User $user, Inquiry $inquiry): bool
    {
        return $this->viewHistory($user, $inquiry)
            && in_array($inquiry->status, [InquiryStatus::Assigned, InquiryStatus::InProgress], true);
    }
}
