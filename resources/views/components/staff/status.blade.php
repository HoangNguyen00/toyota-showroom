{{-- Nhãn trạng thái khu nghiệp vụ. Truyền enum (InquiryStatus/AppointmentStatus) hoặc tone + nội dung.
     Nhãn tư vấn trùng với customer/chat.blade.php; nhãn lịch hẹn lấy từ AppointmentStatus::label(). --}}
@props(['status' => null, 'tone' => null])
@php
    $label = null;
    if ($status instanceof \App\Enums\InquiryStatus) {
        [$label, $tone] = match ($status) {
            \App\Enums\InquiryStatus::New => ['Chờ phân công', 'warning'],
            \App\Enums\InquiryStatus::Assigned => ['Đã phân công', 'info'],
            \App\Enums\InquiryStatus::InProgress => ['Đang tư vấn', 'info'],
            \App\Enums\InquiryStatus::Closed => ['Đã kết thúc', 'neutral'],
        };
    } elseif ($status instanceof \App\Enums\AppointmentStatus) {
        $label = $status->label();
        $tone = match ($status) {
            \App\Enums\AppointmentStatus::Pending => 'warning',
            \App\Enums\AppointmentStatus::Confirmed, \App\Enums\AppointmentStatus::Completed => 'success',
            \App\Enums\AppointmentStatus::Cancelled, \App\Enums\AppointmentStatus::NoShow => 'danger',
        };
    }
@endphp
<span {{ $attributes->class(['staff-status', 'staff-status--'.($tone ?? 'neutral')]) }}>{{ $label ?? $slot }}</span>
