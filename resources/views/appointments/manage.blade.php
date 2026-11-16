@extends('layouts.app')

@section('title', 'Quản lý lịch hẹn — Staff')

@section('content')
<section class="page-section">
    <div class="container">
        <p class="eyebrow">QUẢN LÝ</p>
        <h1>Lịch hẹn</h1>

        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="notice notice-error" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error" role="alert">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="GET" action="{{ route('staff.appointments.index') }}" class="filter-bar">
            <select name="status">
                <option value="">— Tất cả trạng thái —</option>
                @foreach (\App\Enums\AppointmentStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <select name="type">
                <option value="">— Tất cả loại —</option>
                @foreach (\App\Enums\AppointmentType::cases() as $t)
                    <option value="{{ $t->value }}" @selected(($filters['type'] ?? '') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
            <input type="text" name="q" placeholder="Tên / SĐT" value="{{ $filters['q'] ?? '' }}">
            <button type="submit" class="button button-dark">Lọc</button>
        </form>

        @forelse ($appointments as $appointment)
            <article class="card">
                <div class="card-body">
                    <span class="eyebrow">{{ $appointment->type->label() }} — {{ $appointment->status->label() }}</span>
                    <h2>{{ $appointment->contact_name }} ({{ $appointment->contact_phone }})</h2>
                    <p>Mong muốn: {{ $appointment->requested_at->format('d/m/Y H:i') }}</p>
                    @if ($appointment->car)<p>Xe: {{ $appointment->car->name }}</p>@endif
                    @if ($appointment->service)<p>Dịch vụ: {{ $appointment->service->name }}</p>@endif
                    @if ($appointment->customer_car_info)<p>Xe khách: {{ $appointment->customer_car_info }}</p>@endif
                    @if ($appointment->assignedStaff)<p>Phụ trách: {{ $appointment->assignedStaff->name }}</p>@endif
                    @if ($appointment->confirmed_at)<p>Đã xác nhận: {{ $appointment->confirmed_at->format('d/m/Y H:i') }}</p>@endif
                    @if ($appointment->note)<p>Ghi chú: {{ $appointment->note }}</p>@endif

                    @if ($appointment->status === \App\Enums\AppointmentStatus::Pending)
                        <form method="POST" action="{{ route('staff.appointments.assign', $appointment) }}">
                            @csrf
                            <label for="assigned_to-{{ $appointment->id }}">Phân công nhân viên</label>
                            <select name="assigned_to" id="assigned_to-{{ $appointment->id }}">
                                <option value="">— Chưa phân công —</option>
                                @foreach ($salesStaff as $s)
                                    <option value="{{ $s->id }}" @selected($appointment->assigned_to === $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="button button-dark">Lưu phân công</button>
                        </form>

                        @can('confirm', $appointment)
                            <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}">
                                @csrf
                                <label for="confirmed_at-{{ $appointment->id }}">Thời gian xác nhận</label>
                                <input type="datetime-local" name="confirmed_at" id="confirmed_at-{{ $appointment->id }}" required>
                                <button type="submit" class="button button-red">Xác nhận lịch</button>
                            </form>
                        @endcan
                    @endif

                    @if (in_array($appointment->status->value, ['pending', 'confirmed'], true))
                        <form method="POST" action="{{ route('staff.appointments.cancel', $appointment) }}" onsubmit="return confirm('Xác nhận hủy lịch này?');">
                            @csrf
                            <label for="cancel_reason-{{ $appointment->id }}">Lý do hủy</label>
                            <input type="text" name="cancel_reason" id="cancel_reason-{{ $appointment->id }}" maxlength="500" required>
                            <button type="submit" class="button button-dark">Hủy lịch</button>
                        </form>
                    @endif

                    @if ($appointment->status === \App\Enums\AppointmentStatus::Confirmed && $appointment->confirmed_at && $appointment->confirmed_at->isPast())
                        <form method="POST" action="{{ route('staff.appointments.conclude', $appointment) }}">
                            @csrf
                            <select name="outcome">
                                <option value="completed">Hoàn tất</option>
                                <option value="no_show">No-show</option>
                            </select>
                            <button type="submit" class="button button-dark">Kết thúc</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p>Không có lịch hẹn nào khớp bộ lọc.</p>
        @endforelse

        {{ $appointments->links() }}
    </div>
</section>
@endsection
