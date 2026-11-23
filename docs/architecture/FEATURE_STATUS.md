# Trạng thái thực tế

## So sánh 2 xe (P2 đề xuất, chờ leader duyệt) — 23/11/2026

Nhánh codex/car-comparison từ main 1c70a4d thêm GET /xe/so-sanh (cars.compare), chỉ đọc.
Khách vãng lai chọn hai chiếc công khai theo tên · mã · màu, nhóm theo dòng xe;
lối vào từ bộ lọc Catalog và link “So sánh với xe khác” trên card (chọn sẵn xe1).
Server dùng Car::publiclyVisible() + CarPresenter: từ chối trùng xe, ID sai, xe ẩn
hoặc dòng ẩn (422, giữ lựa chọn); giữ thứ tự khách chọn; thông số thiếu “Đang cập nhật”.
Không schema/dependency mới; không đổi luồng Catalog/tư vấn/đặt lịch.
Đây là P2 ngoài cổng COURSE_SCOPE §5: ACCEPTANCE P1 chưa được nhóm ghi đạt toàn bộ,
nên tính năng chờ leader quyết định giữ hay hoãn. Đã kiểm SQLite + browser 1440/390px;
chưa chạy MySQL cục bộ, chờ CI.
## Xe quan tâm (P2 đề xuất, chờ leader duyệt) — 23/11/2026

Nhánh codex/saved-cars từ main 1c70a4d: nút Lưu xe/Đã lưu trên thẻ xe và trang chi tiết,
trang /xe-quan-tam có trạng thái rỗng, bỏ lưu, đếm số xe. ID lưu ở localStorage (tối đa 12,
loại trùng, giữ sau reload); server chỉ render xe publiclyVisible, xe ẩn/dòng ẩn/đã xóa bị loại.
Chọn 2 xe để so sánh chỉ hiện khi route cars.compare có (PR So sánh 2 xe chưa merge).
Dialog Tư vấn chuyển sang ủy quyền sự kiện để thẻ chèn sau tải vẫn mở được.
P2 ngoài cổng COURSE_SCOPE §5: ACCEPTANCE P1 chưa ghi đạt toàn bộ. Đã kiểm SQLite,
JS unit, browser 1440/390px, bàn phím, reload, localStorage bị chặn; chưa chạy MySQL cục bộ.
## Quên mật khẩu (P2 đề xuất, chờ leader duyệt) — 23/11/2026

Nhánh codex/password-reset từ main 1c70a4d: link “Quên mật khẩu?” ở đăng nhập, form yêu cầu
link và form đặt mật khẩu mới trong layout Auth; Laravel Password Broker + bảng
password_reset_tokens có sẵn. Phản hồi giống nhau cho mọi email, có giới hạn tần suất;
token băm, 60 phút, một lần; link theo APP_URL; tài khoản khóa không đặt lại được; đổi
remember_token và xóa sessions DB của user. Sau review: token được kiểm tra và tiêu thụ trong
transaction khóa hàng users; email dạng mảng trả lỗi validation. Đã kiểm SQLite + MySQL 8.4.3 riêng
(119 tests), thử hai yêu cầu đồng thời trên MySQL, và browser + Mailpit (trước bản sửa review). P2 ngoài cổng COURSE_SCOPE §5: ACCEPTANCE P1 chưa ghi đạt toàn bộ.
## Email xác nhận lịch hẹn (P2 đề xuất, chờ leader duyệt) — 23/11/2026

Nhánh codex/appointment-confirmation-email từ main 1c70a4d: confirm() dispatch job afterCommit;
email gồm giờ xác nhận theo Asia/Ho_Chi_Minh, xe/dịch vụ, thông tin showroom (demo).
Chỉ gửi tới email tài khoản khách; guest bỏ qua và ghi log. Chống trùng bằng ShouldBeUnique +
khóa cache + activity_logs; lỗi gửi retry 3 lần, lịch vẫn confirmed. Sau review: lỗi đưa vào
hàng đợi sau commit được ghi nhận, phản hồi xác nhận vẫn thành công. Chưa có email nhắc giờ.
Đã kiểm SQLite và MySQL 8.4.3 riêng (32 tests), demo Mailpit + queue database: Mailpit tắt →
retry, bật lại → đúng 1 thư, job trùng không gửi thêm. P2 ngoài cổng COURSE_SCOPE §5.

## Auth toàn màn hình — 19/11/2026

Đổi layout đăng nhập/đăng ký sang video nền fixed phủ viewport, form trong suốt nổi
phía trên; bỏ nút video và tự phát/lặp kể cả reduced-motion theo yêu cầu Đạt.
Giữ ảnh dự phòng và media local, không sửa form/session/quyền hoặc database.
Composer check đạt 108 tests/874 assertions và build đạt. Browser 1440x900/390x844:
video tự phát im lặng, tiếp tục lặp sau hơn 21 giây, không controls/nút video;
form không tràn ngang, đăng ký cuộn và footer không đè form. Chưa kiểm điện thoại thật.

## Đồng bộ catalog PR #9 — 19/11/2026

Nhánh catalog ghép customer 7d039c3 và main 96aa13c, giữ slider/card/ảnh chính,
lọc DB và CTA tư vấn/đặt lịch thật. Giữ cả hai migration index hỗ trợ đã có để
tương thích máy đã chạy bản 16/11 và main 17/11; không đổi cột hoặc dữ liệu.
Nhánh đồng bộ đạt 108 tests/874 assertions trên SQLite/MySQL 8.4.3, 5 JS tests và build.
Browser 1280/390px: slider, lọc max riêng, card/chi tiết không tràn ngang, tư vấn AJAX
nhận ID thật và đặt lịch lưu cùng chiếc Vios. Bộ chọn lịch thêm mã/màu để phân biệt
các chiếc cùng tên; thông số không lặp Dòng xe, ảnh thiếu hiện đúng số lượng.
Kết quả CI của SHA cuối ghi trên PR; chưa nghiệm thu điện thoại thật/tải lớn/failover.

## Lịch sử tích hợp catalog — 16/11/2026

16/11: nhánh feature/customer-catalog-integration ghép TV3 từ PR #2/admin 56211fe
với customer 9a71fe5. /xe và /xe/{car} đọc DB, giữ slider giới thiệu và card 3 cột;
đã ghép main eb6399a/PR #8, giữ sửa test và trạng thái nút xóa dòng xe của nhóm.
gallery mở đúng ảnh, tư vấn AJAX/booking dùng đúng ID xe. Giữ chat/lịch/video Auth.
Đã kiểm 108 tests/874 assertions trên SQLite và MySQL 8.4.3, 5 JS tests, build;
race upload giữ 10 ảnh/1 primary, locking tư vấn với xe sold/hidden đạt.
Browser: lọc max riêng, slider, gallery, tư vấn lỗi/giữ draft/gửi thành công, booking
và admin thêm xe nháp/chặn public thiếu ảnh đều đạt. Chưa merge main hoặc nghiệm thu
toàn bộ P1; chưa kiểm mới điện thoại thật, tải lớn và failover.
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
