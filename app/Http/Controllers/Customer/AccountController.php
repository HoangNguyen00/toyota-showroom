<?php

namespace App\Http\Controllers\Customer;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        if ($request->user()->role !== UserRole::Customer) {
            return view('auth.account');
        }

        $upcomingAppointments = Appointment::query()
            ->where('customer_id', $request->user()->id)
            ->where(function ($query): void {
                $query->where(fn ($q) => $q->where('status', AppointmentStatus::Pending)->where('requested_at', '>', now()))
                    ->orWhere(fn ($q) => $q->where('status', AppointmentStatus::Confirmed)->where('confirmed_at', '>', now()));
            })
            ->with(['car:id,name', 'service:id,name', 'assignedStaff:id,name'])
            ->orderByRaw('COALESCE(confirmed_at, requested_at) ASC')
            ->limit(3)->get();

        return view('customer.account', compact('upcomingAppointments'));
    }
}
