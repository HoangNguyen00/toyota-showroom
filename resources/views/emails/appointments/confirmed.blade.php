<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xác nhận lịch hẹn #{{ $appointment->id }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f1f4;font-family:Arial,Helvetica,sans-serif;color:#171918">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f1f4;padding:24px 12px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden">
            <tr><td style="padding:24px 28px;border-bottom:1px solid #e0e3dd">
                <p style="margin:0;font-size:20px;font-weight:bold;color:#d71920;letter-spacing:.02em">TOYOTA</p>
                <p style="margin:4px 0 0;font-size:12px;color:#737873">{{ $showroom['name'] }}</p>
            </td></tr>
            <tr><td style="padding:28px">
                <p style="margin:0 0 6px;font-size:11px;letter-spacing:.12em;color:#d71920">LỊCH HẸN ĐÃ XÁC NHẬN</p>
                <h1 style="margin:0 0 16px;font-size:24px;font-weight:normal;line-height:1.3">Chào {{ $appointment->contact_name }},</h1>
                <p style="margin:0 0 20px;font-size:14px;line-height:1.7">Showroom đã xác nhận lịch hẹn của bạn. Vui lòng đến đúng giờ dưới đây.</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;border-top:1px solid #e0e3dd">
                    <tr><td style="padding:12px 0;color:#737873;width:40%;border-bottom:1px solid #e0e3dd">Mã lịch hẹn</td><td style="padding:12px 0;border-bottom:1px solid #e0e3dd">#{{ $appointment->id }}</td></tr>
                    <tr><td style="padding:12px 0;color:#737873;border-bottom:1px solid #e0e3dd">Thời gian</td><td style="padding:12px 0;border-bottom:1px solid #e0e3dd"><strong>{{ $confirmedAt->format('H:i') }}, {{ $confirmedAt->format('d/m/Y') }}</strong><br><span style="font-size:12px;color:#737873">Múi giờ {{ $timezone }} (GMT{{ $confirmedAt->format('P') }})</span></td></tr>
                    <tr><td style="padding:12px 0;color:#737873;border-bottom:1px solid #e0e3dd">Loại lịch</td><td style="padding:12px 0;border-bottom:1px solid #e0e3dd">{{ $typeLabel }}</td></tr>
                    <tr><td style="padding:12px 0;color:#737873;border-bottom:1px solid #e0e3dd">{{ $appointment->type === \App\Enums\AppointmentType::Viewing ? 'Xe' : 'Dịch vụ' }}</td><td style="padding:12px 0;border-bottom:1px solid #e0e3dd">{{ $target }}</td></tr>
                    @if ($appointment->customer_car_info)
                        <tr><td style="padding:12px 0;color:#737873;border-bottom:1px solid #e0e3dd">Xe của bạn</td><td style="padding:12px 0;border-bottom:1px solid #e0e3dd">{{ $appointment->customer_car_info }}</td></tr>
                    @endif
                </table>
                <h2 style="margin:28px 0 8px;font-size:15px">Thông tin showroom</h2>
                <p style="margin:0;font-size:14px;line-height:1.8">{{ $showroom['name'] }}<br>{{ $showroom['address'] }}<br>Điện thoại: {{ $showroom['phone'] }}<br>Giờ làm việc: {{ $showroom['hours'] }}</p>
                <p style="margin:28px 0 0"><a href="{{ $manageUrl }}" style="display:inline-block;padding:12px 20px;background:#171918;color:#ffffff;text-decoration:none;border-radius:10px;font-size:14px">Xem lịch hẹn của tôi</a></p>
                <p style="margin:20px 0 0;font-size:12px;line-height:1.7;color:#737873">Cần đổi giờ? Hủy lịch trong mục Lịch hẹn của tôi rồi đặt lại, hoặc gọi showroom. Lịch hẹn không giữ xe; giá công bố chưa phải giá lăn bánh.</p>
            </td></tr>
            <tr><td style="padding:16px 28px;background:#f5f6f3;font-size:11px;line-height:1.6;color:#737873">Email tự động từ đồ án môn học, không phải website đại lý Toyota chính thức. Vui lòng không trả lời email này.</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
