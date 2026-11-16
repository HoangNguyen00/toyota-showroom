<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\CarStatus;
use App\Enums\UserRole;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\Service;
use App\Models\User;
use App\Services\Appointment\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $service) {}

    // Public list of bookable maintenance services.
    public function services(): View
    {
        $services = Service::query()
            ->where('is_visible', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type', 'description', 'allow_booking']);

        return view('services.index', ['services' => $services]);
    }

    // Public booking page (viewing or maintenance), preselect via query string.
    public function create(Request $request): View
    {
        $preselectType = $request->query('type') === 'maintenance'
            ? AppointmentType::Maintenance->value
            : AppointmentType::Viewing->value;

        $cars = Car::query()
            ->where('is_visible', true)
            ->where('status', CarStatus::Available)
            ->whereHas('category', fn ($q) => $q->where('is_visible', true))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $services = Service::query()
            ->where('is_visible', true)
            ->where('allow_booking', true)
            ->whereIn('type', ['maintenance', 'warranty'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('appointments.create', [
            'preselectType' => $preselectType,
            'preselectCar' => (int) $request->query('car', 0) ?: null,
            'cars' => $cars,
            'services' => $services,
            'appointment' => null,
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Chặn đặt trùng khung giờ ±60 phút với lịch hiện có (theo user hoặc SĐT).
        $this->service->assertNoCustomerOverlap(
            auth()->id(),
            Carbon::parse($data['requested_at'])->format('Y-m-d H:i:s'),
            $data['contact_phone']
        );

        $appointment = new Appointment;
        $appointment->type = AppointmentType::from($data['type']);
        $appointment->car_id = $data['car_id'] ?? null;
        $appointment->service_id = $data['service_id'] ?? null;
        $appointment->customer_car_info = trim((string) ($data['customer_car_info'] ?? '')) ?: null;
        $appointment->contact_name = $data['contact_name'];
        $appointment->contact_phone = $data['contact_phone'];
        $appointment->requested_at = Carbon::parse($data['requested_at']);
        $appointment->note = $data['note'] ?? null;
        $appointment->status = AppointmentStatus::Pending;
        $appointment->customer_id = auth()->id(); // null for guests

        $appointment->save();

        return redirect()
            ->route('appointments.create')
            ->with('status', 'Đã gửi yêu cầu đặt lịch. Showroom sẽ liên hệ xác nhận trong thời gian sớm nhất.');
    }

    // Customer: own appointments list. Staff/admin are redirected to their
    // full management view so nobody sees an empty list by visiting the wrong URL.
    public function index(Request $request): RedirectResponse|View
    {
        if ($request->user()->role !== UserRole::Customer) {
            return redirect()->route('staff.appointments.index')
                ->with('status', 'Bạn đang dùng tài khoản nhân viên/quản trị — đây là danh sách lịch quản lý.');
        }

        $appointments = Appointment::query()
            ->where('customer_id', $request->user()->id)
            ->with(['car', 'service', 'assignedStaff'])
            ->orderByDesc('requested_at')
            ->paginate(15);

        return view('appointments.index', ['appointments' => $appointments]);
    }

    // Staff/admin: all appointments with filters.
    public function manage(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'type' => ['nullable', Rule::enum(AppointmentType::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Appointment::query()->with(['car', 'service', 'customer', 'assignedStaff']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('contact_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_phone', 'like', '%'.$search.'%');
            });
        }
        if ($request->user()->role === UserRole::Sales) {
            $query->where(function ($q) use ($request): void {
                $q->where('assigned_to', $request->user()->id)
                    ->orWhereNull('assigned_to');
            });
        }

        return view('staff.appointments.index', [
            'appointments' => $query->orderBy('requested_at')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'salesStaff' => User::query()->where('role', UserRole::Sales)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function assign(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('assign', $appointment);
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer'],
        ]);
        $this->service->assign($request->user(), $appointment, $data['assigned_to'] ?? null);

        return back()->with('status', 'Đã cập nhật phân công.');
    }

    public function confirm(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('confirm', $appointment);
        $data = $request->validate([
            'confirmed_at' => ['required', 'date'],
        ]);
        $this->service->confirm($request->user(), $appointment, $data['confirmed_at']);

        return back()->with('status', 'Đã xác nhận lịch hẹn.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($user->role === UserRole::Customer) {
            $this->service->cancelByCustomer($user, $appointment, $data['cancel_reason']);
        } else {
            Gate::authorize('cancel', $appointment);
            $this->service->cancelByStaff($user, $appointment, $data['cancel_reason']);
        }

        return back()->with('status', 'Đã hủy lịch hẹn.');
    }

    public function conclude(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('conclude', $appointment);
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['completed', 'no_show'])],
        ]);
        $this->service->conclude($request->user(), $appointment, $data['outcome'] === 'no_show');

        return back()->with('status', 'Đã cập nhật kết quả lịch hẹn.');
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'requested_at' => ['required', 'date', 'after:now'],
        ]);

        if ($user->role === UserRole::Customer) {
            $this->service->rescheduleByCustomer($user, $appointment, $data['requested_at']);
        } else {
            Gate::authorize('cancel', $appointment);
            $this->service->rescheduleByStaff($user, $appointment, $data['requested_at']);
        }

        return back()->with('status', 'Đã đổi thời gian lịch hẹn.');
    }
}
