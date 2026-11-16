@extends('layouts.app')

@section('title', 'Lịch hẹn của tôi — Toyota Showroom')

@section('content')
<div class="appointment-page">
    <div class="appointment-shell">
        <p class="appointment-kicker">TOYOTA SHOWROOM</p>
        <h1>Lịch hẹn của tôi</h1>

        @if (session('status'))
            <div class="appointment-notice" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="appointment-notice appointment-notice--error" role="alert">{{ session('error') }}</div>
        @endif

        <div class="appointment-list">
        @forelse ($appointments as $appointment)
            <article class="appointment-item">
                <p class="eyebrow">{{ $appointment->type->value === 'viewing' ? 'LỊCH XEM XE' : 'LỊCH BẢO DƯỠNG' }}</p>
                <h2>{{ $appointment->type->label() }}</h2>
                <p>Thời gian mong muốn: {{ $appointment->requested_at->format('d/m/Y H:i') }}</p>
                <p>Trạng thái: <span class="appointment-status appointment-status--{{ $appointment->status->value }}">{{ $appointment->status->label() }}</span></p>
                @if ($appointment->assignedStaff)
                    <p>Nhân viên phụ trách: {{ $appointment->assignedStaff->name }}</p>
                @endif
                @if ($appointment->car)
                    <p>Xe: {{ $appointment->car->name }}</p>
                @endif
                @if ($appointment->service)
                    <p>Dịch vụ: {{ $appointment->service->name }}</p>
                @endif
                @if ($appointment->status === \App\Enums\AppointmentStatus::Pending)
                    <form method="POST" action="{{ route('customer.appointments.reschedule', $appointment) }}">
                        @csrf
                        <label for="reschedule_at-{{ $appointment->id }}">Đổi thời gian mong muốn</label>
                        <input type="datetime-local" name="requested_at" id="reschedule_at-{{ $appointment->id }}" required>
                        <button type="submit" class="button-outline">Đổi lịch</button>
                    </form>
                @endif
                @if ($appointment->status === \App\Enums\AppointmentStatus::Pending || $appointment->status === \App\Enums\AppointmentStatus::Confirmed)
                    <form method="POST" action="{{ route('customer.appointments.cancel', $appointment) }}" onsubmit="return confirm('Xác nhận hủy lịch hẹn này?');">
                        @csrf
                        <label for="cancel_reason-{{ $appointment->id }}">Lý do hủy</label>
                        <input type="text" name="cancel_reason" id="cancel_reason-{{ $appointment->id }}" maxlength="500" required>
                        <button type="submit" class="button-red">Hủy lịch</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="appointment-empty">Bạn chưa có lịch hẹn nào. <a href="{{ route('appointments.create') }}">Đặt lịch ngay</a>.</div>
        @endforelse
        </div>

        {{ $appointments->links() }}
    </div>
</div>
@endsection
