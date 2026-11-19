# Trạng thái thực tế

## Đồng bộ PR #4 — 19/11/2026

Main 96aa13c đã gộp backend TV4 (PR #1), Catalog TV3 và lịch/dịch vụ TV5 (PR #10).
Nhánh customer đồng bộ main, giữ account/chat thật, lịch khách có phân trang,
video Auth và route quản trị các module. PR #4 chuyển base về main theo review.
Catalog có dữ liệu DB từ main; phần slider/CTA và sửa quản lý ảnh nằm riêng ở PR #9.
SQLite 98 passed/1 skipped, MySQL 99 passed; 5 JS tests/build đạt. Browser 390px kiểm
account, chat hai chiều với phiên sales riêng, lịch và điều hướng danh sách/hội thoại.
Account sales/admin có liên kết vào các module đã triển khai. Chưa nghiệm thu toàn bộ P1;
mobile là viewport mô phỏng, chưa kiểm điện thoại thật/tải lớn/failover.
Các mốc bên dưới là lịch sử, không mô tả trạng thái main mới nhất.

16/11: nhánh feature/customer-realtime-newrepo ghép bản sửa xung đột PR #1 (backend 1151428)
vào giao diện mới nhất 3dc88d8. Giữ account, lịch hẹn, form tư vấn, chat thật và video Auth.
Backend đã kiểm lại 78 tests/635 assertions trên SQLite và MySQL 8.4.3; Reverb hai phiên
1303 ms, UUID/reconnect và thu hồi kênh/job cũ đạt. Chưa gộp main hoặc nghiệm thu P1;
review và kiểm tra nhánh giao diện được ghi trong PR tích hợp ở repository mới.
Nhánh giao diện đạt composer check 86 tests/700 assertions, 5 test JS, build và
browser chat hai chiều không reload; chưa kiểm lại mobile/tải lớn/failover.

05/11: sửa chữ TOYOTA ở hero không chồng cột mô tả. Layout đăng nhập/đăng ký hỗ trợ video Camry local 9 giây với ảnh dự phòng; clip nguồn không được commit, xem docs/development/AUTH_VIDEO.md.

28/10: nhánh giao diện đồng bộ lịch hẹn từ main, account customer có lịch sắp tới từ DB và liên kết /lich-hen. Trang lịch khách dùng layouts.customer, hủy theo endpoint TV5 có kiểm owner/time/state. Chat customer đã nối TV4 trên nhánh tích hợp riêng: dữ liệu own inquiry, HTTP history/send và Echo private channel; đã kiểm QA hai chiều trên browser, còn chờ review H02/PublicationGate trước merge main.

Ngày 20/10/2026. Đã gộp vào main: docs/review-v6-srs, H01 (feature/auth-middleware),
H02 (feature/admin-users), frontend home/catalog preview (feature/frontend-layout),
tích hợp auth vào giao diện frontend (feature/frontend-auth-integration) và nhánh
feature/appointment-schedule (TV5: đặt lịch, phân công, xác nhận, hủy, hoàn tất).
H03 mới có hợp đồng quyền/gói review. Chưa có nghiệp vụ tư vấn/chat hoàn chỉnh.

TV3 (19/10): catalog P1 trên nhánh feature/car-crud-search — CRUD dòng/xe/ảnh,
giá/tình trạng, search/filter/pagination, admin xe, public /xe + /xe/{id} dùng DB thật,
`publiclyVisible` scope, `CatalogSeeder` 15 xe. Chưa gộp main, chờ review.

Trang chủ tối, video/canvas, hai ảnh chi tiết, /xe slider/card/lọc và /xe/{id} ID 1–9
vẫn dùng fixture local/testing (chỉ chạy ở local/testing). Header đã nối Auth thật,
form đăng nhập/đăng ký dùng ảnh xe chạy và nền tối; backend H01/H02 giữ nguyên.
Nhánh tích hợp có backend tư vấn/chat TV4 và lịch hẹn TV5; main chưa gộp TV4. Chưa nghiệm thu toàn bộ concurrency hoặc giao diện các module.

| Module | Đã có | P1 còn làm | Chủ trách nhiệm |
| --- | --- | --- | --- |
| Framework | Laravel/lockfile/Vite/Tailwind/CI | Cài/demo máy khác | Hoàng + nhóm |
| Dữ liệu | 8 bảng core + audit, enum/model | Seed Toyota; giữ brand=Toyota, internal_note | Võ Nguyễn Thành Đạt (TV3)/Phan Võ Chí Bảo (TV5) + Hoàng |
| Auth | H01; H02 UserPolicy, admin list/search/pagination/tạo sales/khóa-mở, transaction tăng version khi khóa; test HTTP pass | Review/kiểm trực quan H02, tích hợp thu hồi socket/job với TV4 | Hoàng |
| Catalog | TV3 P1: CRUD dòng/xe/ảnh upload an toàn, giá/tình trạng, admin và public search/filter/sort/pagination, scope visible; test SQLite/MySQL đã được báo cáo | Review nhóm, ảnh demo và kiểm tra trực quan | Võ Nguyễn Thành Đạt (TV3) + Đạt |
| Tư vấn | Main: TV4 tiếp nhận AJAX, scoped list/detail, phân công/trạng thái/internal_note/quyền; Blade staff | Review nhóm và form catalog ở PR #9 | Nguyễn Huy Hoàng (TV4) + Đạt |
| Chat | Main: TV4 HTTP/UUID/history, private channel/event/Queue; PR #4 có UI khách thật | Review bản đồng bộ và mobile trước Ready | Nguyễn Huy Hoàng (TV4) + Đạt |
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
