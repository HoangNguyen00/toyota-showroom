# Hợp đồng route và giao tiếp — P1

Theo [SRS 1.4](../../SRS.md)/ADR 0003. H01 đã có endpoint Auth/tài khoản ở phần dưới.
Catalog/lịch vẫn là đích. Trên nhánh TV4 24/10/2026, tư vấn/chat đã có endpoint,
Policy/private channel thật. Xem phần triển khai và [hợp đồng đầy đủ](../development/TV4_INTEGRATION.md).

GET / tên home: preview trang chủ Camry trong local/testing; production giữ welcome.
/#dich-vu và /#showroom là anchor giới thiệu, không phải endpoint dịch vụ/đặt lịch.

Preview local/testing: GET /xe-preview/{id} tên cars.preview.show chỉ còn cho fixture
ID 1–9 của giao diện minh họa; ID không có trả 404. Không truy vấn DB.

## Catalog đã triển khai (TV3)

GET /xe tên cars.index: q (tên/mã/màu, ≤100), category (slug), status (enum),
min_price/max_price (VND, độc lập; nếu có cả hai thì max ≥ min),
sort newest|price_asc|price_desc, page; 12 xe/trang.
GET /xe/{car} tên cars.show, dùng ID (không slug), 404 nếu xe hoặc dòng ẩn — kể cả admin.
Chỉ xe và dòng cùng visible mới công khai (scope `publiclyVisible` cho TV4/TV5 dùng lại).

Admin (web/auth/active/role:admin, Policy từng bản ghi):
GET/POST /admin/car-categories (admin.car-categories.index/store),
GET /admin/car-categories/create, GET/PUT/DELETE /admin/car-categories/{category}
(…create/edit/update/destroy); brand luôn Toyota do server gán, slug sinh từ tên.
GET /admin/cars (q/category/status/visible, 20/trang), GET /admin/cars/create,
POST /admin/cars, GET /admin/cars/{car}/edit, PUT/DELETE /admin/cars/{car}.
POST /admin/cars/{car}/images (multipart image + is_primary),
PATCH /admin/cars/{car}/images/{image} (sort_order/is_primary),
DELETE /admin/cars/{car}/images/{image}. Ảnh JPG/PNG/WEBP ≤5 MB, tối thiểu 320x180,
tên file do server đặt; tối đa 10 ảnh và một ảnh chính; thao tác khóa hàng cars.

Giá nhập số nguyên VND từ 1 triệu (decimal(15,0), không float). Mã chiếc xe unique,
regex chữ in hoa/số/gạch ngang; mỗi cars là một chiếc. Xe mới ẩn; công khai cần
dòng visible và đúng một ảnh chính. Không xóa ảnh cuối của xe public.
Xe có inquiry/appointment trả validation (không xóa) → ẩn thay thế.
Trả view Blade + redirect/flash; chưa có JSON API CRUD riêng cho catalog.

Bản xem trước fixture cũ đổi URL: GET /collection-preview (cars.collection-preview)
và GET /xe-preview/{id} (cars.preview.show), chỉ local/testing.
Frontend local 16/10/2026 (lịch sử): /xe từng là fixture minh họa; từ 19/10 là catalog DB.

**Bảng dưới là hợp đồng P1; Catalog, lịch/dịch vụ và tư vấn/chat đã đăng ký trong main.**
Khi làm route thật, module owner cập nhật bảng và FEATURE_STATUS.
Route chạy hiện tại xem `php artisan route:list`: home, catalog public `/xe`,
dịch vụ/lịch TV5, auth/tài khoản, admin users/cars/car-categories/services,
health và broadcast auth. TV4 bổ sung tư vấn/chat theo TV4_INTEGRATION,
giữ nguyên các route đã có của TV3/TV5/Auth.

| Nhóm | Route đích | Quyền |
| --- | --- | --- |
| Public | GET /xe, /xe/{id}, /dich-vu; POST /tu-van, /dat-lich/xem-xe, /dat-lich/bao-duong | Dữ liệu public, validate, throttle |
| Auth | GET/POST /dang-ky, /dang-nhap; POST /dang-xuat | guest/auth, active, CSRF |
| Customer | GET /yeu-cau, /yeu-cau/{inquiry}, /lich-hen; POST /lich-hen/{id}/huy | Own/active |
| Chat | GET/POST /yeu-cau/{inquiry}/tin-nhan | Own/assigned/admin; gửi chỉ assigned/in_progress |
| Staff/admin | /staff/... và /admin/... | Role/active + Policy từng hồ sơ |

Không có route đổi lịch/mở lại/audit UI/API status riêng trong P1.
Customer route đăng ký tại root URL, route name customer.*; không có prefix /customer.
Route customer có list/detail inquiries; lịch chờ TV5 thêm theo module.
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
Tên route là hợp đồng cho Đạt. Sales/admin dùng layouts/staff (menu theo Policy viewAny, tiêu đề,
thông báo status/error/account_error); khách giữ layouts/customer, /yeu-cau HTML của khách giữ layouts/account.
Khối "Xe khách chọn" (components/staff/car) đọc quan hệ car đã lưu: ảnh chính, tên, mã chiếc, màu;
JSON hồ sơ tư vấn vẫn chỉ trả car id/name.

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
không đặt trong routes/customer.php vốn role:customer. InquiryPolicy/MessagePolicy
và kênh nghiệp vụ đã triển khai trên nhánh TV4; xem [ma trận H03](AUTHORIZATION_CONTRACT.md).

POST content/client_message_id, sender từ session; trả id/inquiry_id,
sender(id,name), content, created_at; không serialize user/inquiry/internal_note.
History tối đa 50/lần, after_id/cursor; client dedupe ID, kết nối lại bù lịch sử.
GET mặc định 50 tin mới nhất theo thứ tự tăng dần, before_id cho tin cũ;
after_id cho catch-up tăng dần, limit 1–50, không dùng cả hai cursor cùng lúc.
Response `{data,has_more,next_after_id,next_before_id,access_version,channel}`.
Event `.message.created` có payload bằng response message, không bọc model.
Unique sender/UUID; retry cùng payload trả bản cũ 200, khác trả 409.
Lưu rồi phát sau commit, chỉ lần tạo mới phát. Subscribe trước catch-up.
Sau khắc phục F02, message và intent trong database jobs cùng transaction/connection;
worker chỉ xử lý sau commit. HTTP retry không thêm job; worker delivery at-least-once,
client dedupe ID. API/route/event payload không đổi. Redis/sync không là queue thay thế
được hỗ trợ cho MessageService. F01 dùng PublicationGate chung H02/reassign/publisher,
gate trước transaction, không row lock qua network. API/event không đổi; phạm vi và
giới hạn session/primary/in-flight ở ADR 0006/TV4_INTEGRATION, cần Hoàng review H02.
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

## Preview UI khách hàng — 23/10/2026 (lịch sử; đã thay trên nhánh tích hợp 28/10)

GET /tai-khoan giữ name account, auth/active; customer dùng view customer.account, admin/sales giữ auth.account.
GET /tai-khoan/tin-nhan (customer.chat.preview) chỉ local/testing và customer active.
Chỉ dữ liệu fixture, không có HTTP mutation hoặc mở private channel. Shared chat thật vẫn theo TV4 trong routes/inquiries.php; preview không là endpoint nghiệp vụ.
## TV4 triển khai 24/10/2026 (chưa merge)

POST /tu-van nhận name/phone/email/content/car_id, ánh xạ contact_*; guest/customer,
3/10 phút từ limiter nền; JSON 201 id/message. Customer_id/source/status lấy server.
GET /yeu-cau(/id), /staff/inquiries(/id), /admin/inquiries(/id) phân trang 15,
lọc status và scoped theo owner/assigned/admin. Customer response không có internal_note/contact_*.
PATCH admin.inquiries.assign nhận assigned_to sales active; staff/admin start/note/close.
Chỉ assigned → in_progress → closed; close_result theo enum. Closed chỉ đọc, không DELETE.
Ghi chú một ô tối đa 10000 ký tự; reassign giữ in_progress, chỉ bump version khi đổi sales.
Schema endpoint/response/error/demo đầy đủ ở [TV4_INTEGRATION](../development/TV4_INTEGRATION.md).

## Customer realtime integration — 28/10/2026

Nhánh tích hợp nối GET /tai-khoan/tin-nhan (customer.chat.index) vào DB own inquiry, tối đa 15 thread/trang; tìm q và chọn conversation đã scoped owner. Không serialize ghi chú nội bộ. Dùng lại HTTP messages TV4, UUID/cursor và Echo, không tạo API gửi thứ hai. Shared HTTP vẫn cho ba role với Policy.
Preview fixture không còn là màn mặc định. Main và PR #11 vẫn cần review trước khi gộp backend TV4. Appointment UI dùng các route TV5, không sửa protocol khóa hoặc schema.

## Catalog tích hợp TV3 — 16/11/2026

GET /xe (cars.index) và /xe/{car} (cars.show) đọc DB, chỉ xe và dòng visible.
q tên/mã/màu, category slug, status, min_price/max_price độc lập, sort newest/price_asc/price_desc;
12 xe/trang, giữ query. Slider dùng hình giới thiệu dòng xe; card/chi tiết/giá/trạng thái dùng DB.
Admin CRUD dòng/xe/ảnh ở admin.car-categories.* và admin.cars.*, có role/active/Policy.
Năm từ 2000 đến năm hiện tại+1, giá VND nguyên từ 1.000.000; tạo xe ẩn, công khai cần ảnh chính.
Ảnh JPEG/PNG/WebP tối đa 5MB, tối đa 10 ảnh/xe; thao tác ảnh khóa hàng cars.
Preview fixture chỉ còn /collection-preview và /xe-preview/{id}, không là nguồn public catalog.
Nút tư vấn public gửi POST /tu-van qua JSON/CSRF, car_id đúng chiếc; guest nhận xác nhận,
customer có liên kết cuộc tư vấn. Đặt lịch dùng appointments.create?type=viewing&car={id}.
Danh sách xe đặt lịch hiển thị tên, mã chiếc và màu; value vẫn là car_id thật.
PATCH /admin/cars/{car}/images/{image} nhận sort_order và is_primary, kiểm đúng xe/Policy.
