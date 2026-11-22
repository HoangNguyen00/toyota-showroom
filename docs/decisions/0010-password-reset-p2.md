# ADR 0010 — Quên mật khẩu qua Password Broker (P2 đề xuất)

Ngày: 23/11/2026. Trạng thái: đề xuất trong PR riêng, chờ leader duyệt.

## Bối cảnh

SRS 1.4 §9 xếp “quên mật khẩu/OTP/email” vào đề xuất sau nghiệm thu P1. Repo đã có bảng
`password_reset_tokens` (migration mặc định) và cấu hình broker `users` (hết hạn 60 phút,
throttle 60 giây) nhưng chưa có route/màn hình.

## Quyết định

- Dùng `Password::broker()` của Laravel, không schema mới. Token chỉ lưu dạng băm; broker xóa
  token sau khi đặt lại thành công nên mỗi link dùng một lần; hết hạn theo `auth.passwords.users.expire`.
- Phản hồi công khai giống nhau cho email có/không có tài khoản/tài khoản khóa. Tài khoản khóa
  không được gửi link; nếu bị khóa sau khi đã có link thì đặt lại bị từ chối như link không hợp lệ.
- Giới hạn: `throttle:10,1` theo IP trên route, thêm RateLimiter 3 lần/10 phút theo IP + email,
  áp dụng y hệt cho email không tồn tại; broker chỉ tạo token mới mỗi 60 giây.
- Email tiếng Việt (`ResetPasswordNotification`), gửi đồng bộ để token thô không nằm trong bảng
  `jobs`. Link dựng từ `APP_URL`, không từ Host header (chống password reset poisoning).
- Mật khẩu mới dùng cùng quy tắc với đăng ký (≥ 8 ký tự, ≤ 72 byte, không NUL, xác nhận khớp).
- Thu hồi phiên theo cơ chế hiện hành: đổi `remember_token` và xóa các dòng `sessions` của user
  (SESSION_DRIVER=database). Không tự đăng nhập; không đổi role/is_active.

## Hệ quả

- Với SESSION_DRIVER file/array/redis, phiên cũ chỉ hết khi hết hạn; dự án dùng database.
- Gửi đồng bộ làm request của email có tài khoản chậm hơn một chút (khác biệt thời gian nhỏ có thể
  bị đo); chấp nhận để không lưu token thô trong hàng đợi.
- Cần SMTP (Mailpit khi demo) để khách nhận link.
