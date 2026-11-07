# Kế hoạch nghiệm thu P1 — SRS 1.4

Bảng mô tả tiêu chí P1. Kết quả đã chạy ghi riêng bên dưới theo người kiểm, ngày và SHA.
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

## Bằng chứng của Hoàng — 2026-11-07

| Mục | SHA và môi trường | Kết quả và phần còn mở |
| --- | --- | --- |
| AUTH-01, AUTH-02 và quản trị | `ed06179`, PHP 8.3.36, SQLite `:memory:` | `composer check` đạt 56 test và 456 assertions. Test Auth kiểm tra đăng ký, đăng nhập, logout, limiter và tài khoản bị khóa. Test quản trị kiểm quyền admin và tăng phiên bản quyền khi khóa. |
| SET-01, kiểm tra máy mới | `10f207b`, Windows/Laragon, MySQL 8.4.3 | Cài từ lockfile, build, migrate và seed thành công. Đăng nhập admin, mở `/admin/users` và thấy đủ sáu tài khoản demo. Xem [SETUP](../development/SETUP.md). |
| CHAT-01 đến CHAT-03 và H02 | `1438af2`, [PR #12](https://github.com/grumhu124/toyota-showroom/pull/12), MySQL test riêng, Reverb và queue worker | Hai harness MySQL và realtime exit 0. Hai phiên nhận tin; retry, reconnect và thu hồi quyền đạt, kể cả job chờ. **Còn mở** nghiệm thu lại H02 sau khi PR đồng bộ với main. |
| H03-APT | `ed06179`, rà mã | **Còn mở.** Chờ PR TV5 rồi thử sales A đổi hoặc bỏ phân công lịch pending của sales B. Cả hai yêu cầu phải bị từ chối, người phụ trách giữ nguyên và admin vẫn phân công được. [Bàn giao TV5](https://github.com/grumhu124/toyota-showroom/pull/13#issuecomment-6707243670). |

## Kịch bản demo Auth và quản trị

1. Trên database demo local, đăng nhập `admin@example.test` và mở `/admin/users`.
2. Tạo một sales với email demo riêng cho mỗi lần tập. Kiểm tra danh sách hiển
   thị tài khoản hoạt động và phiên admin vẫn giữ nguyên.
3. Mở cửa sổ riêng tư, đăng nhập tài khoản sales vừa tạo. Quay lại phiên admin
   và khóa tài khoản đó.
4. Tải lại trang cần đăng nhập trong cửa sổ sales, rồi thử đăng nhập lại.
   Kết quả mong đợi là phiên cũ bị chặn ở request tiếp theo và đăng nhập lại
   thất bại. Mở khóa từ phiên admin sau khi tập nếu cần dùng lại tài khoản.

Kịch bản trên dùng để tập demo. Bằng chứng trên máy mới hiện ghi nhận đăng
nhập admin và mở danh sách, còn tạo sales và khóa phiên đã có test tự động.
