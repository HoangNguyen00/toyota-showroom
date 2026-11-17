# Hợp đồng dữ liệu

Migrations là schema thật của baseline 2026-10-14; PR review 16/10 chưa đổi mã.
SRS 1.4 giữ 9 bảng nền cho P1; không coi yêu cầu trong tài liệu là mã đã chạy.

| Bảng | Trường nghiệp vụ |
| --- | --- |
| users | name, email unique, phone nullable, password hash, role, is_active |
| car_categories | brand, name, slug, description, is_visible |
| cars | category_id, code, slug, name, year, color, specs, price, status, is_visible |
| car_images | car_id, path, sort_order, is_primary |
| inquiries | customer_id, car_id, contact_name, contact_phone, contact_email, content, source, assigned_to, status, access_version, internal_note, close_result |
| messages | inquiry_id, sender_id, content, client_message_id |
| services | name, slug, type, description, allow_booking, is_visible |
| appointments | type, customer_id, inquiry_id, car_id, service_id, customer_car_info, contact_name, contact_phone, assigned_to, requested_at, confirmed_at, status, note, cancel_reason |
| activity_logs | user_id, action, target_type, target_id, payload, ip |

Tất cả bảng có id/timestamps. Mọi FK nghiệp vụ restrictOnDelete, ngoại trừ
car_images.car_id cascade khi xóa xe chưa được tham chiếu. Activity target là tham chiếu
đa loại (target_type/target_id), không tạo FK giả tới nhiều bảng. Giá decimal(15,0), không float.User.role/is_active không mass assignable. Inquiry.internal_note hidden mặc định, nhưng
điều này không thay API Resource/policy để tránh lộ các trường liên hệ khác.

Phone users cho phép null ở DB để factory/seed nền; FormRequest đăng ký vẫn bắt buộc phone.
Form tư vấn email nhập được ánh xạ contact_email. Cars/services.slug hiện có và unique; route đích v6 dùng ID xe, chưa xóa cột này.
messages unique(sender_id, client_message_id); chưa có bảng read receipts hoặc attachments.
Một xe có tối đa một ảnh primary là invariant của service, chưa được migration tự bảo đảm.

## Quan hệ

CarCategory → nhiều Car → nhiều CarImage. Inquiry → customer/assignedStaff User,
Car tùy chọn, nhiều Message và Appointment. Message → Inquiry và sender User.
Appointment → customer/assignedStaff User, Inquiry tùy chọn, Car hoặc Service theo type.
ActivityLog → actor User tùy chọn. Quan hệ customer và assignedStaff là hai FK riêng.

## Trạng thái

- UserRole: customer, sales, admin.
- CarStatus: available, reserved, sold.
- InquiryStatus: new, assigned, in_progress, closed.
- AppointmentType: viewing, maintenance.
- AppointmentStatus: pending, confirmed, completed, cancelled, no_show.
- ServiceType: warranty, maintenance.

close_result: da_tu_van, khach_khong_tiep_tuc, khach_giao_dich_truc_tiep, khong_lien_he_duoc.
source inquiry: form hoặc chat. Các cột trạng thái string được cast sang PHP enum;
FormRequest và service phải validate/kiểm tra chuyển trạng thái, DB cast không tự validate input.

## Ràng buộc nghiệp vụ cần service triển khai

Xem xe cần car_id, maintenance cần service_id và customer_car_info. assigned_to là sales active.
customer_id chat không rỗng. Confirm phải hợp lệ xe/dịch vụ và không overlap theo sales.
Cập nhật access_version trong transaction với chuyển người/khóa account; queue event đọc version hiện tại.
Dữ liệu vãng lai không gắn sang account chỉ vì trùng phone. Không hard-delete hồ sơ/tin/lịch.

Sau baseline, sửa schema bằng migration mới; cập nhật model, đặc tả, contracts, test và inventory.

## TV3 catalog (19/10)

Migration mới `2026_10_19_000001_add_catalog_filter_indexes_to_cars_table` chỉ thêm index:
cars(category_id,is_visible), cars(name), car_images(car_id,is_primary). Không đổi cột/enum.
Ảnh upload lưu `storage/app/public/cars/{car_id}/{uuid}.ext` (disk public, cần `storage:link`);
seed dùng path `assets/products/...` trong public. One-primary-per-car chỉ được bảo đảm
bởi StoreCarImage trong transaction (khóa hàng cars), không phải unique index DB.
Scope `Car::publiclyVisible()` là nguồn duy nhất cho điều kiện public; TV4/TV5 dùng lại.
`specs` là mảng [{label,value}] tối đa 30 dòng; rỗng lưu null.
Giá lọc so sánh decimal(15,0) với integer — trên MySQL đã test; SQLite so sánh chuỗi
nên test giới hạn giá chỉ chạy trên MySQL.

## Đích P1 theo SRS 1.4 / ADR 0003

Giữ 9 bảng, internal_note, brand cố định Toyota; không cần migration bỏ brand.
Cars là từng chiếc code unique; không phải tồn kho tổng của cả phiên bản.
inquiries.code/closed_at, InquiryNote và ServiceType.other chuyển P2; chưa có trong mã.
User phone DB nullable không thay validation phone required của form đăng ký.
Giữ slug/source, contact_email. ActivityLog là nền, không yêu cầu UI audit P1.
Không sửa migration đã merge hoặc xóa bảng/cột vì đổi phạm vi tài liệu.
