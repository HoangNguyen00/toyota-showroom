<?php

return [
    'appointment_minutes' => 60,
    'catalog_per_page' => 12,
    'demo_password' => env('DEMO_PASSWORD'),

    // Thông tin liên hệ in trong email khách. Mặc định là dữ liệu demo của đồ án,
    // không phải đại lý Toyota thật; đặt biến môi trường khi triển khai.
    'contact' => [
        'name' => env('SHOWROOM_NAME', 'Toyota Showroom (đồ án môn học)'),
        'address' => env('SHOWROOM_ADDRESS', 'Địa chỉ demo — nhân viên sẽ xác nhận khi liên hệ'),
        'phone' => env('SHOWROOM_PHONE', '0900 000 000 (demo)'),
        'hours' => env('SHOWROOM_HOURS', '08:00–17:30, Thứ Hai đến Thứ Bảy'),
    ],
];
