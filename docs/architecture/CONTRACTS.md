# Hợp đồng route và giao tiếp — P1

Theo [SRS 1.4](../../SRS.md)/ADR 0003. H01 đã có endpoint Auth/tài khoản ở phần dưới.
Endpoint catalog/tư vấn/chat/lịch vẫn là đích; private channel còn deny-by-default.

GET / tên home: preview trang chủ Camry trong local/testing; production giữ welcome.
/#dich-vu và /#showroom là anchor giới thiệu, không phải endpoint dịch vụ/đặt lịch.

Preview local/testing: GET /xe-preview/{id} tên cars.preview.show chỉ còn cho fixture
ID 1–9 của giao diện minh họa; ID không có trả 404. Không truy vấn DB.

## Catalog đã triển khai (TV3)

GET /xe tên cars.index: q (tên/mã/màu, ≤100), category (slug), status (enum),
min_price/max_price (VND, max ≥ min), sort newest|price_asc|price_desc, page;
12 xe/trang. GET /xe/{car} tên cars.show, dùng ID (không slug), 404 nếu xe hoặc
dòng ẩn — kể cả admin đang đăng nhập. Chỉ xe và dòng cùng visible mới công khai
(scope `publiclyVisible` cho TV4/TV5 dùng lại).

Admin (web/auth/active/role:admin, Policy từng bản ghi):
GET/POST /admin/car-categories (admin.car-categories.index/store),
GET /admin/car-categories/create, GET/PUT/DELETE /admin/car-categories/{category}
(…create/edit/update/destroy); brand luôn Toyota do server gán, slug sinh từ tên.
GET /admin/cars (q/category/status/visible, 20/trang), GET /admin/cars/create,
POST /admin/cars, GET /admin/cars/{car}/edit, PUT/DELETE /admin/cars/{car}.
POST /admin/cars/{car}/images (multipart image + is_primary),
DELETE /admin/cars/{car}/images/{image}. Ảnh: JPG/PNG/WEBP ≤4 MB, tối thiểu 320x180,
tên file do server đặt; tối đa một ảnh chính.

Giá nhập số nguyên VND (decimal(15,0), không float). Mã chiếc xe unique, regex
chữ in hoa/số/gạch ngang; mỗi cars là một chiếc. Không đặt visible cho xe thuộc
dòng đang ẩn. Xe có inquiry/appointment trả validation (không xóa) → ẩn thay thế.
Trả view Blade + redirect/flash; chưa có JSON API cho catalog.

Bản xem trước fixture cũ đổi URL: GET /collection-preview (cars.collection-preview)
và GET /xe-preview/{id} (cars.preview.show), chỉ local/testing.

Các đoạn "Preview local 16/10" bên dưới giữ để truy vết lịch sử, /xe không còn là fixture.

Frontend local 16/10/2026 (lịch sử): /xe từng là fixture minh họa; từ 19/10 là catalog DB.
Các route nghiệp vụ khác bên dưới vẫn chưa triển khai.

**Các endpoint nghiệp vụ dưới đây là kế hoạch, chưa được đăng ký trong baseline.**
Khi làm route thật, module owner cập nhật bảng và FEATURE_STATUS.
Route chạy hiện tại xem php artisan route:list: home, catalog public /xe, auth/tài khoản,
admin users, admin cars/car-categories, health và broadcast auth.

| Nhóm | Route đích | Quyền |
| --- | --- | --- |
| Public | GET /xe, /xe/{id}, /dich-vu; POST /tu-van, /dat-lich/xem-xe, /dat-lich/bao-duong | Dữ liệu public, validate, throttle |
| Auth | GET/POST /dang-ky, /dang-nhap; POST /dang-xuat | guest/auth, active, CSRF |
| Customer | GET /yeu-cau, /yeu-cau/{inquiry}, /lich-hen; POST /lich-hen/{id}/huy | Own/active |
| Chat | GET/POST /yeu-cau/{inquiry}/tin-nhan | Own/assigned/admin; gửi chỉ assigned/in_progress |
| Staff/admin | /staff/... và /admin/... | Role/active + Policy từng hồ sơ |

Không có route đổi lịch/mở lại/audit UI/API status riêng trong P1.
Customer route đăng ký tại root URL, route name customer.*; không có prefix /customer.
Route file customer chưa có endpoint nghiệp vụ, TV4/TV5 thêm theo module.
Giữ slug/source/brand; Toyota cố định phía server. Category lọc bằng slug,
chi tiết xe dùng ID, xe ẩn 404. Car code là mã chiếc xe, không là mã cả dòng xe.

## Form AJAX và lỗi

Form tư vấn Accept JSON trả 201 với id, thông báo tiếp nhận; không trả cả model.
Không dùng mã/id public để đọc hồ sơ; guest liên hệ qua phone.
Email form ánh xạ contact_email. Không nhận customer_id/role/assigned_to từ khách.
422 validation/trùng lịch; 401 chưa đăng nhập; 403 sai quyền; 404 không tồn tại/ẩn;
429 throttle; 409 UUID dùng lại khác payload. Web dùng redirect/flash phù hợp.
Form public 3 lần/10 phút/IP/endpoint; login 5 thất bại/phút/IP+email.
LoginRequest đã thực hiện failure-only limiter; không gắn throttle đếm mọi request.

## H01 đã triển khai

GET /dang-ky (register), POST /dang-ky (register.store);
GET /dang-nhap (login), POST /dang-nhap (login.store), đều guest.
POST /dang-xuat (logout), GET /tai-khoan (account), đều auth + ensure.active.
Đăng ký tự login rồi vào account; login dùng intended URL hoặc account.
Form đăng ký name/email/phone/password/password_confirmation; login email/password.
Mật khẩu không có NUL và tối đa 72 byte cho bcrypt hiện tại;
đăng ký vẫn tối thiểu 8 ký tự. Giới hạn byte khác giới hạn số ký tự Unicode.
Mật khẩu không được fill lại vào HTML/session flash. Email trim/lowercase.
Guest vào route auth bị redirect login; JSON trả 401.
User khóa bị logout/invalidate session: web redirect login với account_error,
JSON 403; kiểm active áp dụng cả web công khai để tránh giữ phiên khóa.
Sai thông tin login trả lỗi email, quá ngưỡng trả 429. Đăng xuất chỉ POST + CSRF.
Tên route là hợp đồng cho Đạt; layout accounts chỉ là giao diện tối thiểu.

## H02 đã triển khai

GET /admin/users (admin.users.index): q tên/email tối đa 100, role từ enum, page;
15 user/trang, giữ filter. GET /admin/users/create (admin.users.create).
POST /admin/users (admin.users.store): name/email/phone/password/password_confirmation,
role cố định sales, is_active true; không đổi session admin sang user vừa tạo.
PATCH /admin/users/{user}/status (admin.users.status): is_active boolean, bắt buộc
gửi trạng thái đích (0/1), không toggle mù. Tất cả web/auth/active/admin + UserPolicy.
POST/PATCH thành công redirect về danh sách với flash status; validation web redirect
với lỗi, JSON 422; sai quyền 403. H02 không cung cấp JSON API CRUD riêng.
Không khóa bất kỳ admin nào, không xóa user/cấp admin/sửa mật khẩu qua màn này.
UpdateUserStatus khóa actor/target theo ID, đọc lại Policy trong transaction,
đổi active và tăng version inquiry liên quan khi chuyển từ active sang locked.
Lặp trạng thái không tăng version; mở lại không phục hồi version cũ.
Giữ assignment/history, không khóa appointment trong service này.
HTTP phiên cũ bị H01 thu hồi ở request kế tiếp; socket/job còn phải tích hợp TV4.

## Chat — hợp đồng TV4

HTTP chat dùng auth group cho customer/sales/admin trong routes/inquiries.php,
không đặt trong routes/customer.php vốn role:customer. Quyền từng inquiry kiểm
bằng Policy; xem [ma trận H03](AUTHORIZATION_CONTRACT.md). Đây là hợp đồng đích,
chưa có endpoint/kênh nghiệp vụ để nghiệm thu.

POST content/client_message_id, sender từ session; trả id/inquiry_id,
sender(id,name), content, created_at; không serialize user/inquiry/internal_note.
History tối đa 50/lần, after_id/cursor; client dedupe ID, kết nối lại bù lịch sử.
Unique sender/UUID; retry cùng payload trả bản cũ 200, khác trả 409.
Lưu rồi phát sau commit, chỉ lần tạo mới phát. Subscribe trước catch-up.
Private inquiry.{id}.v{version} kiểm active/role/ownership/assigned/version.
Chuyển sales/khóa tài khoản tăng version; kiểm quyền/version hiện tại khi gửi/phát,
không chuyển tin của job cũ sang kênh cũ. Dữ liệu đã nhận không thu lại được.
Người còn quyền có thể tải lại để lấy version mới; kênh users.{id} tự chuyển là P2.
New/closed chỉ đọc; không có reopen trong P1. UI chưa gửi được phải nói rõ.

## Lịch

Validate type/giờ/xe/dịch vụ; admin phân công sales active.
Xác nhận transaction khóa appointment rồi sales, đọc lại pending và quyền,
kiểm giờ/xe/service và lịch confirmed khác cách <60 phút, rồi ghi.
Mọi confirmation dùng cùng service/protocol, tránh hai request ghi đè/trùng.
Đúng 60 phút hợp lệ; đây là quy ước tiếp nhận demo, không mô hình xưởng.
Customer hủy pending/confirmed chưa tới giờ, server kiểm own và lưu lý do.
Nhân viên/admin hủy kèm lý do; hoàn tất/no_show sau giờ confirmed_at.
Không sửa trạng thái cuối, không tự giữ xe từ lịch; không gán inquiry khác customer.
Giờ không phù hợp: hủy, tạo yêu cầu mới; đổi lịch chuyên biệt là P2.

## Hợp đồng làm song song

Xem [PARALLEL_WORK](../team/PARALLEL_WORK.md) về namespace route, fixture/seed,
phân công file chung và thứ tự khóa giữa tài khoản/tư vấn/lịch.
TV4/TV5 dùng hợp đồng chung và test MySQL, không khóa dữ liệu ngược thứ tự.
