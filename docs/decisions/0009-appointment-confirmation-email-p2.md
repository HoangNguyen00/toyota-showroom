# ADR 0009 — Email xác nhận lịch hẹn (P2 đề xuất)

Ngày: 23/11/2026. Trạng thái: đề xuất trong PR riêng, chờ leader duyệt.

## Bối cảnh

SRS 1.4 §9 xếp email/OTP vào đề xuất sau nghiệm thu P1. Người dùng yêu cầu gửi email ngay
khi lịch chuyển sang “Đã xác nhận”, giữ nguyên Policy, kiểm trùng giờ và phân công của TV5,
demo bằng Mailpit. Chưa làm email nhắc trước giờ hẹn.

## Quyết định

- `AppointmentService::confirm()` dispatch `SendAppointmentConfirmation(id, confirmed_at)` với
  `DB::afterCommit` ngay sau khi ghi; lỗi trước/rollback transaction thì không có job.
  Nếu bước đưa vào hàng đợi ném lỗi sau commit (queue/cache hỏng), lịch vẫn confirmed nên phản hồi
  xác nhận vẫn thành công: lỗi được ghi `failed` (reason enqueue_failed, tên lớp lỗi) và Log::warning
  không PII, khóa unique được nhả để có thể xếp lại.
  Không đổi Policy, khóa hàng, kiểm overlap hay luồng phân công.
- Job đọc lại lịch từ DB; chỉ gửi khi vẫn confirmed và đúng mốc confirmed_at. Payload chỉ ID +
  timestamp. Email người nhận lấy từ tài khoản `customer_id`; lịch khách vãng lai (form không
  thu email) bị bỏ qua và ghi `appointment.confirmation_mail.skipped`.
- Chống gửi trùng: `ShouldBeUnique` (appointment + confirmed_at) khi xếp hàng, khóa cache quanh
  kiểm–gửi–ghi, và bản ghi `activity_logs` `appointment.confirmation_mail.sent` cho đúng mốc.
  Không thêm bảng/cột: dùng activity_logs có sẵn.
- Lỗi gửi: job ném lỗi để queue retry (3 lần, backoff 60s/300s); hết lượt ghi
  `appointment.confirmation_mail.failed` (chỉ tên lớp lỗi, không email) và log warning.
  Lịch vẫn confirmed.
- Thông tin showroom đọc `config('showroom.contact')`, mặc định là dữ liệu demo ghi rõ.

## Hệ quả

- At-least-once: nếu SMTP đã nhận thư nhưng tiến trình dừng trước khi ghi “sent”, retry sẽ gửi
  lại một thư. Ứng dụng không loại trừ được ca này nếu nhà cung cấp không hỗ trợ khóa idempotency.
- Cần worker queue chạy (`php artisan queue:work`) để gửi; QUEUE sync thì gửi ngay sau commit.
- Khóa cache phụ thuộc cache store hỗ trợ lock (database/file/redis đều có).
