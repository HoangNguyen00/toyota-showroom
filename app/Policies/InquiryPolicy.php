<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(?User $user): bool
    {
        return $user === null || ($user->is_active && $user->role === UserRole::Customer);
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->is_active && match ($user->role) {
            UserRole::Admin => true,
            UserRole::Sales => $inquiry->assigned_to === $user->id,
            UserRole::Customer => $inquiry->customer_id === $user->id,
        };
    }

    public function manage(User $user, Inquiry $inquiry): bool
    {
        return $user->role !== UserRole::Customer && $this->view($user, $inquiry);
    }

    public function assign(User $user, Inquiry $inquiry): bool
    {
        return $user->is_active && $user->role === UserRole::Admin;
    }
}
