<?php

// Local/testing presentation fixtures only. Never stored as customer records.
return [
    ['key' => 'camry', 'name' => 'Toyota Camry', 'reference' => 'MẪU 001', 'status' => 'in_progress', 'status_label' => 'Đang tư vấn', 'advisor' => 'Tư vấn viên mẫu', 'initial' => 'C', 'image' => 'assets/collection/camry.png', 'summary' => 'Thông tin phiên bản và trang bị', 'messages' => [
        ['sender' => 'staff', 'content' => 'Chào bạn, mình có thể hỗ trợ bạn tìm hiểu về Toyota Camry. Bạn đang quan tâm đến trang bị hay không gian nội thất?', 'time' => '09:30'],
        ['sender' => 'customer', 'content' => 'Mình muốn tìm hiểu thêm về khoang lái và các trang bị an toàn của xe.', 'time' => '09:32'],
        ['sender' => 'staff', 'content' => 'Mình sẽ cùng bạn xem thông tin từng phiên bản để lựa chọn chiếc xe phù hợp với nhu cầu nhé.', 'time' => '09:33'],
    ]],
    ['key' => 'cross', 'name' => 'Toyota Corolla Cross', 'reference' => 'MẪU 002', 'status' => 'new', 'status_label' => 'Chờ phân công', 'advisor' => 'Chưa có tư vấn viên', 'initial' => 'C', 'image' => 'assets/collection/corolla.png', 'summary' => 'Đang chờ showroom tiếp nhận', 'messages' => []],
    ['key' => 'vios', 'name' => 'Toyota Vios', 'reference' => 'MẪU 003', 'status' => 'closed', 'status_label' => 'Đã kết thúc', 'advisor' => 'Tư vấn viên mẫu', 'initial' => 'V', 'image' => 'assets/collection/vios.png', 'summary' => 'Cảm ơn bạn đã tìm hiểu Toyota Vios.', 'messages' => [
        ['sender' => 'customer', 'content' => 'Cảm ơn bạn đã tư vấn. Mình đã có đủ thông tin để tham khảo rồi.', 'time' => '15:20'],
        ['sender' => 'staff', 'content' => 'Cảm ơn bạn đã quan tâm Toyota Vios. Chúc bạn có những hành trình thật nhiều niềm vui!', 'time' => '15:22'],
    ]],
];
