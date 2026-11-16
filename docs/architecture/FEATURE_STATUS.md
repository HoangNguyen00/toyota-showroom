# Trạng thái thực tế

16/11: nhánh feature/customer-catalog-integration ghép TV3 từ PR #2/admin 56211fe
với customer 9a71fe5. /xe và /xe/{car} đọc DB, giữ slider giới thiệu và card 3 cột;
đã ghép main eb6399a/PR #8, giữ sửa test và trạng thái nút xóa dòng xe của nhóm.
gallery mở đúng ảnh, tư vấn AJAX/booking dùng đúng ID xe. Giữ chat/lịch/video Auth.
Đã kiểm 108 tests/874 assertions trên SQLite và MySQL 8.4.3, 5 JS tests, build;
race upload giữ 10 ảnh/1 primary, locking tư vấn với xe sold/hidden đạt.
Browser: lọc max riêng, slider, gallery, tư vấn lỗi/giữ draft/gửi thành công, booking
và admin thêm xe nháp/chặn public thiếu ảnh đều đạt. Chưa merge main hoặc nghiệm thu
toàn bộ P1; chưa kiểm mới điện thoại thật, tải lớn và failover.

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

Trang chủ tối, video/canvas và hai ảnh chi tiết giữ thiết kế hiện có. Catalog /xe
và /xe/{car} dùng DB; fixture chỉ ở /collection-preview và /xe-preview/{id} local/testing.
Header đã nối Auth thật,
form đăng nhập/đăng ký dùng ảnh xe chạy và nền tối; backend H01/H02 giữ nguyên.
Nhánh tích hợp có backend tư vấn/chat TV4 và lịch hẹn TV5; main chưa gộp TV4. Chưa nghiệm thu toàn bộ concurrency hoặc giao diện các module.

| Module | Đã có | P1 còn làm | Chủ trách nhiệm |
| --- | --- | --- | --- |
| Framework | Laravel/lockfile/Vite/Tailwind/CI | Cài/demo máy khác | Hoàng + nhóm |
| Dữ liệu | 8 bảng core + audit, enum/model | Seed Toyota; giữ brand=Toyota, internal_note | Võ Nguyễn Thành Đạt (TV3)/Phan Võ Chí Bảo (TV5) + Hoàng |
| Auth | H01; H02 UserPolicy, admin list/search/pagination/tạo sales/khóa-mở, transaction tăng version khi khóa; test HTTP pass | Review/kiểm trực quan H02, tích hợp thu hồi socket/job với TV4 | Hoàng |
| Catalog | Nhánh tích hợp: TV3 CRUD/dòng/ảnh/giá/trạng thái, /xe DB + filter/sort/12-trang, gallery/tư vấn/booking; regression và browser đạt | Nhóm review PR tích hợp, kiểm mobile và dữ liệu demo toàn luồng | Võ Nguyễn Thành Đạt (TV3) + Đạt |
| Tư vấn | Main: model/enum; nhánh tích hợp: TV4 backend + form AJAX khách thật | Review H02/PublicationGate, nghiệm thu quyền/race MySQL | Nguyễn Huy Hoàng (TV4) + Đạt |
| Chat | Nhánh tích hợp: TV4 gửi/lưu/phát/history + UI khách; QA browser hai chiều pass | Chưa gộp main; review thu hồi quyền và kiểm concurrency MySQL | Nguyễn Huy Hoàng (TV4) + Đạt |
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
