LỊCH HẸN ĐÃ XÁC NHẬN — {{ $showroom['name'] }}

Chào {{ $appointment->contact_name }},
Showroom đã xác nhận lịch hẹn của bạn.

Mã lịch hẹn: #{{ $appointment->id }}
Thời gian: {{ $confirmedAt->format('H:i') }}, {{ $confirmedAt->format('d/m/Y') }} (múi giờ {{ $timezone }}, GMT{{ $confirmedAt->format('P') }})
Loại lịch: {{ $typeLabel }}
{{ $appointment->type === \App\Enums\AppointmentType::Viewing ? 'Xe' : 'Dịch vụ' }}: {{ $target }}
@if ($appointment->customer_car_info)
Xe của bạn: {{ $appointment->customer_car_info }}
@endif

Thông tin showroom
{{ $showroom['name'] }}
{{ $showroom['address'] }}
Điện thoại: {{ $showroom['phone'] }}
Giờ làm việc: {{ $showroom['hours'] }}

Xem lịch hẹn của tôi: {{ $manageUrl }}

Cần đổi giờ? Hủy lịch trong mục Lịch hẹn của tôi rồi đặt lại, hoặc gọi showroom.
Lịch hẹn không giữ xe; giá công bố chưa phải giá lăn bánh.
Email tự động từ đồ án môn học, không phải website đại lý Toyota chính thức.
