<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    // Assign/confirm: sales on own appointment, admin on any.
    public function assign(User $user, Appointment $appointment): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Sales
            && $appointment->status === AppointmentStatus::Pending;
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        if ($user->role === UserRole::Admin) {
            return $appointment->status === AppointmentStatus::Pending;
        }

        return $user->role === UserRole::Sales
            && $appointment->status === AppointmentStatus::Pending
            && $appointment->assigned_to === $user->id;
    }

    // Staff/admin cancel (with reason).
    public function cancel(User $user, Appointment $appointment): bool
    {
        if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
            return false;
        }

        return $user->role === UserRole::Admin
            || ($user->role === UserRole::Sales && $appointment->assigned_to === $user->id);
    }

    public function conclude(User $user, Appointment $appointment): bool
    {
        return ($user->role === UserRole::Admin
            || ($user->role === UserRole::Sales && $appointment->assigned_to === $user->id))
            && $appointment->status === AppointmentStatus::Confirmed;
    }
}
