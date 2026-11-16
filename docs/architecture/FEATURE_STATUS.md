# Trạng thái thực tế

## Đồng bộ repository mới — 16/11/2026

PR #1 tại `HoangNguyen00/toyota-showroom` tái tạo backend TV4. Nhánh sửa xung đột
ghép main `ed06179`, giữ endpoint tư vấn/chat và toàn bộ lịch hẹn TV5. Chưa gộp main;
review H02/PublicationGate và kiểm thử mới được ghi ở PR. UI khách của Đạt nằm trên
nhánh tích hợp riêng, không đưa vào backend TV4. Các kết quả bên dưới là lịch sử.

## Bàn giao TV4 sau push — 24/10/2026

Mã TV4 đã commit và push tại `a7bf4059764fc506f1ee3e1aa10a8ea2b61199d6` lên
`feature/inquiry-chat-websocket`. Chưa tạo Pull Request TV4, chưa merge vào main.
Hoàng vẫn cần review H02/PublicationGate; TV3 review lock protocol và Đạt tích hợp
UI khách. Không nâng mức nghiệm thu vì push; giữ nguyên giới hạn bảo mật và kết quả
kiểm thử lịch sử bên dưới. Đợt đồng bộ tài liệu này không chạy lại MySQL/Reverb.

## Sau audit TV4 — 24/10/2026

F01 đã có gate chung publication/reassign/H02 theo phê duyệt giới hạn của người dùng;
security gate, MySQL process và Reverb thật đạt trong phạm vi ADR 0006. H02 chỉ bọc
transaction, cần Hoàng review trước merge. Không nhận bảo đảm qua failover/network in-flight.
F02 đã đổi sang message + database job cùng transaction/connection; F03 phía TV4
khóa category → car; F04 giữ draft theo input revision. Có regression PHPUnit/Node
và harness MySQL/crash/locking. Kết quả mới nhất ở TV4_VALIDATION; suite pass không
thay security gate F01 độc lập (nay exit 0). Không sửa TV3/Auth khác H02/TV5, schema hay PR #11.
Phần dưới là lịch sử triển khai trước audit; kết quả mới nhất ở TV4_VALIDATION.

## TV4 trên nhánh feature/inquiry-chat-websocket — 24/10/2026

Đã triển khai và push nhánh riêng, chưa merge: POST tư vấn AJAX; customer/staff/admin
list/detail scoped + phân trang; assign/reassign, start, internal_note, close_result;
HTTP message/history/UUID; private channel thật; job after-commit có kiểm lại quyền/
version; màn Blade TV4 tối thiểu với Echo/retry/catch-up. Giữ schema/H01/H02.
MySQL concurrency và hai phiên HTTP/session/CSRF + Reverb thật đã chạy; độ trễ
1731 ms với worker đang chạy. Job chờ sau reassign/H02 lock không phát kênh cũ.
Chi tiết/bằng chứng: [TV4_VALIDATION](../testing/TV4_VALIDATION.md).

PR #11 vẫn draft/open tại lúc kiểm, chưa tích hợp UI customer của Đạt.
Public form backend sẵn sàng, form/CTA khách chung chờ Frontend nối. Chưa review
trực quan responsive; chưa coi toàn bộ P1 hoặc UI khách hoàn tất.
Các ghi chú main ngày 19/10 bên dưới là lịch sử baseline trước thay đổi TV4 này.

Ngày 20/10/2026. Đã gộp vào main: docs/review-v6-srs, H01 (feature/auth-middleware),
H02 (feature/admin-users), frontend home/catalog preview (feature/frontend-layout),
tích hợp auth vào giao diện frontend (feature/frontend-auth-integration) và nhánh
feature/appointment-schedule (TV5: đặt lịch, phân công, xác nhận, hủy, hoàn tất).
H03 mới có hợp đồng quyền/gói review. Chưa có nghiệp vụ tư vấn/chat hoàn chỉnh.

Trang chủ tối, video/canvas, hai ảnh chi tiết, /xe slider/card/lọc và /xe/{id} ID 1–9
vẫn dùng fixture local/testing (chỉ chạy ở local/testing). Header đã nối Auth thật,
form đăng nhập/đăng ký dùng ảnh xe chạy và nền tối; backend H01/H02 giữ nguyên.
Nhánh TV4 có backend tư vấn/chat và lịch hẹn; form tư vấn và UI khách chung chờ
nhánh tích hợp frontend. Main chưa có backend TV4.

| Module | Đã có | P1 còn làm | Chủ trách nhiệm |
| --- | --- | --- | --- |
| Framework | Laravel/lockfile/Vite/Tailwind/CI | Cài/demo máy khác | Hoàng + nhóm |
| Dữ liệu | 8 bảng core + audit, enum/model | Seed Toyota; giữ brand=Toyota, internal_note | Võ Nguyễn Thành Đạt (TV3)/Phan Võ Chí Bảo (TV5) + Hoàng |
| Auth | H01; H02 UserPolicy, admin list/search/pagination/tạo sales/khóa-mở, transaction tăng version khi khóa; test HTTP pass | Review/kiểm trực quan H02, tích hợp thu hồi socket/job với TV4 | Hoàng |
| Catalog | Schema/model/thư mục | CRUD/ảnh/search/filter/pagination/UI | Võ Nguyễn Thành Đạt (TV3) + Đạt |
| Tư vấn | Nhánh TV4: tiếp nhận AJAX, scoped list/detail, phân công/trạng thái/internal_note/quyền; Blade staff | Review nhóm, nối form/UI khách | Nguyễn Huy Hoàng (TV4) + Đạt |
| Chat | Nhánh TV4: HTTP/UUID/history, private channel/event/Queue, thu hồi version; test hai phiên Reverb thật | Nối UI khách PR #11, review UI/responsive | Nguyễn Huy Hoàng (TV4) + Đạt |
| Lịch/dịch vụ | Form + rights; CRUD dịch vụ admin; xác nhận khóa/trùng giờ; hủy/hoàn tất/no_show; tests pass | Kiểm trực quan trình duyệt và MySQL thật | Phan Võ Chí Bảo (TV5) |
| Audit | Bảng/model/namespace | Không buộc làm module riêng P1; giữ nền | Hoàng |
| Bàn giao | Kiểm tra nền từng pass local và CI | Test nghiệp vụ, responsive, báo cáo, demo cả 5 người | Cả nhóm |

Chưa triển khai InquiryNote/code/closed_at/other; các mục này là P2, không còn
chặn hoàn thành P1. Không cần migration bỏ brand trong P1. Kênh users control,
khách tự đổi lịch/mở lại cũng P2. Chưa có chức năng nào tự hoàn tất vì sửa SRS.
Chỉ đánh dấu hoàn tất khi chạy end-to-end, có quyền/validation và bằng chứng kiểm tra.
Suite sau tích hợp main gồm nền, Auth, admin user, frontend tích hợp, preview và inventory;
chưa nghiệm thu các module khác hoặc race trên hai kết nối MySQL.
Xem AUTH_VALIDATION và docs/team/HOANG_TASKS.md. Chưa kiểm trực quan responsive;
build/Blade render pass không thay việc Đạt review giao diện.
