# ADR 0005 — Ý định phát tin TV4 nguyên tử với message

24/10/2026. Áp dụng trong nhánh TV4 theo yêu cầu khắc phục F02.
Mã đã commit và push tại `a7bf4059764fc506f1ee3e1aa10a8ea2b61199d6` lên
`feature/inquiry-chat-websocket`; chưa tạo Pull Request TV4, chưa merge vào main.

Database queue `database` dùng cùng đối tượng Connection với Message. Ghi message
và jobs trong một transaction; job beforeCommit vô hiệu trì hoãn enqueue nhưng KHÔNG
phát network trước commit. Worker độc lập chỉ thấy hàng jobs đã commit. Nếu queue
khác driver/connection thì fail closed, rollback thay vì lưu một phần. Tái dùng bảng
jobs kỹ thuật hiện có, không thêm schema/dependency, không tự chuyển Redis/sync.

Crash trước commit rollback cả hai; sau commit giữ cả hai. UUID retry thông thường
không tạo intent lần hai. Worker retry là at-least-once, có thể lặp cùng message ID
nếu crash sau publish trước acknowledge; client dedupe/catch-up, không hứa exactly-once.
Không tự backfill/phát lại toàn bộ tin lịch sử đã có trước bản sửa.

F01 được xử lý tiếp trong ADR 0006 theo phê duyệt giới hạn của người dùng. Đoạn sau
là trạng thái trước phê duyệt, không phải kết luận mới nhất:
F01 KHÔNG được quyết định xong trong ADR này. H02 không có application fence chung;
không thể loại bỏ race sát dispatch chỉ từ publisher hoặc thêm SELECT. Thiết kế fence
publisher/reassign/H02 phải được Hoàng review/phê duyệt, kiểm crash/timeout/lease loss
và MySQL/Reverb thật; không giữ transaction hoặc row lock khi gọi Reverb. Chưa READY.

F03 chỉ thêm locking reads ở InquiryService; không thay CRUD Catalog. Category trước
car, car đổi category thì từ chối. TV3 review giao thức multi-row khi tích hợp.
