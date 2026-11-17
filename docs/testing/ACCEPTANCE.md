# Kế hoạch nghiệm thu P1 — SRS 1.4

Sau sửa F01 được phê duyệt 24/10: security gate, MySQL hai process và Reverb thật tại
SELECT/publish boundary đạt cho reassign/H02 lock customer/sales. CHAT-03 backend có
bằng chứng gate chung trong phạm vi ADR 0006; không thu hồi ngược in-flight hoặc hứa
failover tuyệt đối. H02 cần Hoàng review trước merge. F02/F03/F04 regression giữ đạt;
không nâng toàn bộ UI khách/P1 lên hoàn tất chỉ vì backend suite pass.

Chưa phải kết quả đã chạy. Mỗi mục ghi người kiểm, ngày, kết quả và PR/bằng chứng.
Các tính năng P2 không nằm trong điều kiện đạt P1.

| ID | Kiểm tra | Kết quả cần đạt |
| --- | --- | --- |
| AUTH-01 | Đăng ký/login/logout, email trùng, gửi role admin | Customer, hash, session đúng; lỗi tại trường; logout hủy phiên |
| AUTH-02 | Login sai và tài khoản khóa | Failure-only limiter 5/phút/IP+email; khóa chặn HTTP và tin mới |
| CAT-01 | CRUD dòng/xe/ảnh/dịch vụ, dữ liệu sai | Lưu DB thật, FK/unique; xe nháp được, công khai cần ảnh chính |
| CAT-02 | Tìm/lọc/sort/phân trang/rỗng | Dữ liệu đúng và giữ filter; min>max có lỗi |
| CAT-03 | Ẩn xe/dòng; nhập URL/POST trực tiếp | 404/validation, không lộ hay tạo yêu cầu cho xe ẩn |
| CAT-04 | Xóa đã có hồ sơ vs chưa tham chiếu | Chặn xóa lịch sử, cho ẩn; xóa bản chưa tham chiếu được |
| CAT-05 | Hai chiếc cùng phiên bản, sold một chiếc | Chiếc còn lại vẫn available; nhãn giá tham khảo/nguồn/ngày rõ |
| INQ-01 | Guest/customer gửi form AJAX, thiếu phone, spam | JSON/lỗi theo trường, tạo đúng owner, lần 4/10 phút bị 429 |
| INQ-02 | Giao A rồi chuyển B; đoán ID; nội bộ | Chỉ own/assigned/admin đọc/xử lý, không lộ internal_note |
| INQ-03 | New → assigned → in_progress → closed | Kiểm quyền/trạng thái, lưu close_result; new/closed không gửi chat |
| CHAT-01 | Customer/sales hai phiên gửi nhận | Dưới 2 giây ở mạng demo ổn định, không reload, lưu DB |
| CHAT-02 | Reload/mất mạng/retry UUID/khác payload | Đủ lịch sử, không trùng; khác payload 409; hiển thị lỗi thật |
| CHAT-03 | Chuyển sales/khóa user khi đang nối | Người cũ không nhận tin mới kể cả job chờ; HTTP/kênh từ chối |
| APT-01 | Xem available/reserved/sold/ẩn; service tắt | Chỉ nhận đầu vào hợp lệ; lịch không tự giữ xe |
| APT-02 | Thiếu trường/type sai/quá khứ; guest/customer | Pending đúng contact/owner, guest không tra lịch bằng phone |
| APT-03 | Xác nhận đồng thời cùng sales và cùng appointment | Không trùng/ghi đè; chênh <60 phút chặn, đúng 60 hợp lệ |
| APT-04 | Customer hủy lịch own/khác owner/đã tới giờ | Chỉ own pending/confirmed chưa tới giờ, lưu lý do |
| APT-05 | Completed/no_show và trạng thái cuối | Sau giờ confirmed; chuyển đúng; không sửa trạng thái cuối |
| UI-01 | Khách/staff/admin, mobile/tablet/desktop | Đồng bộ Toyota, không tràn; đủ loading/empty/error/success/disabled |
| SET-01 | Máy thành viên khác, MySQL/web/Reverb/worker | Cài lockfile/migrate/seed/build và chạy theo README; không lộ secret |
| REPORT-01 | Báo cáo, Git và demo 5 người | Đủ rubric, nguồn/ảnh/test, PR/review và giải thích được phần mình |

APT-03 phải dùng hai giao dịch/kết nối MySQL thật, không kết luận từ SQLite/mock
hoặc hai lần request tuần tự. CHAT cần hai session độc lập và thử ngắt/nối mạng.
Seed đủ >12 xe để thấy phân trang và đủ các trạng thái demo.
Chỉ mở P2 sau khi nhóm ghi kết quả đạt toàn bộ bảng và xử lý lỗi P1.

## Bằng chứng TV4 local — 24/10/2026

Nhánh feature/inquiry-chat-websocket, chưa commit/merge. INQ-01/02/03 đã chạy
backend tự động: input/owner/throttle/car visibility, assignment/reassignment,
scope list/detail, note không lộ, trạng thái/close_result. CHAT-01/02/03 đã thử
HTTP/session/CSRF thật, MySQL riêng, Reverb hai phiên: 1731 ms; retry không phát
lặp, reconnect catch-up, socket/job cũ sau reassign/H02 lock không nhận tin mới.
MySQL hai tiến trình độc lập cùng UUID và send/reassign: pass, không reset demo.
Chi tiết: [TV4_VALIDATION](TV4_VALIDATION.md), harness trong scripts/testing.

Đây là bằng chứng backend nhánh TV4, chưa nghiệm thu UI khách PR #11, form AJAX
trực quan, responsive hoặc toàn bộ P1. Không đánh dấu các mục Catalog/Appointment
đã đạt từ test TV4. Cần review nhóm và nối UI khách trước demo nghiệm thu tổng.
