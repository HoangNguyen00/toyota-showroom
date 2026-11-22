@extends('layouts.customer')
@section('title', 'Lịch hẹn của tôi')
@section('content')
<div class="customer-page-heading"><div><p class="customer-eyebrow">MY TOYOTA / LỊCH HẸN</p><h1>Lịch hẹn của tôi</h1></div><a class="customer-button customer-button-red" href="{{ route('appointments.create') }}">Đặt lịch mới <x-customer-icon name="arrow"/></a></div>
<p class="customer-chat-disclaimer">Thời gian hiển thị theo giờ Việt Nam. Lịch chờ xác nhận là yêu cầu; showroom sẽ liên hệ để chốt thời gian.</p>
@if($errors->any())<div class="workspace-errors" role="alert"><p>Chưa thể cập nhật lịch hẹn:</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('error'))<p class="workspace-errors" role="alert">{{ session('error') }}</p>@endif
<div class="workspace-appointments">
@forelse($appointments as $appointment)
    @php
        $time = $appointment->confirmed_at ?? $appointment->requested_at;
        $canCancel = in_array($appointment->status, [\App\Enums\AppointmentStatus::Pending, \App\Enums\AppointmentStatus::Confirmed], true) && $time?->isFuture();
    @endphp
    <article class="customer-panel workspace-appointment-card">
        <div class="customer-section-heading"><p class="customer-eyebrow">LỊCH HẸN #{{ $appointment->id }}</p><span class="workspace-status workspace-status-{{ $appointment->status->value }}">{{ $appointment->status->label() }}</span></div>
        <h2>{{ $appointment->type->label() }}</h2><p class="workspace-appointment-subtitle">{{ $appointment->car?->name ?? $appointment->service?->name ?? 'Hẹn tại showroom' }}</p>
        <dl class="workspace-appointment-facts"><div><dt>Thời gian mong muốn</dt><dd>{{ $appointment->requested_at->format('H:i · d/m/Y') }}</dd></div><div><dt>Thời gian đã xác nhận</dt><dd>{{ $appointment->confirmed_at?->format('H:i · d/m/Y') ?? 'Chưa xác nhận' }}</dd></div><div><dt>Nhân viên phụ trách</dt><dd>{{ $appointment->assignedStaff?->name ?? 'Chưa phân công' }}</dd></div>@if($appointment->customer_car_info)<div><dt>Xe của bạn</dt><dd>{{ $appointment->customer_car_info }}</dd></div>@endif</dl>
        @if($appointment->cancel_reason)<p class="customer-muted">Lý do hủy: {{ $appointment->cancel_reason }}</p>@endif
        @if($canCancel)
            <details class="workspace-cancel"><summary>Hủy lịch hẹn này</summary><form method="POST" action="{{ route('customer.appointments.cancel', $appointment) }}">@csrf<label for="reason-{{ $appointment->id }}">Lý do hủy</label><input id="reason-{{ $appointment->id }}" name="cancel_reason" maxlength="500" required placeholder="Nhập lý do để showroom nắm thông tin"><button class="customer-button" type="submit">Xác nhận hủy lịch</button></form></details>
        @endif
    </article>
@empty
    <section class="customer-panel workspace-empty"><x-customer-icon name="calendar"/><h2>Bạn chưa có lịch hẹn nào</h2><p>Đặt lịch xem xe hoặc bảo dưỡng để showroom chuẩn bị đón tiếp bạn.</p><a href="{{ route('appointments.create') }}" class="customer-button customer-button-red">Đặt lịch ngay <x-customer-icon name="arrow"/></a></section>
@endforelse
</div>
<div class="workspace-pagination">{{ $appointments->links() }}</div>
@endsection
