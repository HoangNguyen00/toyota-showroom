# Task list của Võ Nguyễn Thành Đạt (TV3) — Xe & Catalog

Phạm vi P1 theo SRS 1.4. Nhánh làm việc: `feature/car-crud-search`.
Vị trí file: `Controllers/Catalog`, `Requests/Catalog`, `Services/Catalog`,
`Policies/Car*`, `resources/views/admin/cars`, `resources/views/admin/car-categories`,
`resources/views/cars`, `tests/Feature/Catalog`, `routes/catalog.php`.

## T01 — Schema và model (nền, đã có từ baseline)

- [x] `car_categories` (brand/name/slug/description/is_visible), `cars`
  (category_id/code/slug/name/year/color/specs/price/status/is_visible),
  `car_images` (car_id/path/sort_order/is_primary) — không tạo lại bảng.
- [x] Migration mới `2026_10_19_000001_add_catalog_filter_indexes_to_cars_table`
  thêm index `(category_id,is_visible)`, `name`, `(car_id,is_primary)` cho filter/sắp xếp.
- [x] `Car::scopePubliclyVisible` (xe **và** dòng đều visible), `CarCategory::scopeVisible`,
  `CarImage::url()`, factory `CarFactory`, `CarCategoryFactory`.
- [x] Giá `decimal(15,0)`, form nhập số nguyên VND; brand/Toyota và slug do server gán.

## T02 — CRUD dòng xe (đã triển khai)

- [x] `AdminCarCategoryController`, `CarCategoryRequest`, `CarCategoryPolicy`
  (`admin.car-categories.index/create/store/edit/update/destroy`).
- [x] Validate `name` unique, slug sinh từ tên + unique, `brand` bị bỏ qua từ form.
- [x] Không xóa dòng còn xe (Policy `delete` + chặn FK), không có nút khóa/mở thay thế.

## T03 — CRUD xe, giá, tình trạng (đã triển khai)

- [x] `AdminCarController`, `CarRequest`, `CarPolicy`, `SaveCar` service
  (`admin.cars.index/create/store/edit/update/destroy`).
- [x] Mã chiếc xe unique, regex chữ in hoa/số/gạch ngang; `category_id` phải là dòng brand Toyota.
- [x] Năm 1990..(năm hiện tại + 1), giá integer ≥ 0, `status` = enum CarStatus,
  `specs` tối đa 30 dòng label/value.
- [x] Không bật visible nếu dòng xe đang ẩn; xe/dòng ẩn → 404 ở URL public (kể cả admin).
- [x] Xe có inquiry/appointment không xóa; trả lỗi và tư vấn ẩn thay vì xóa.

## T04 — Ảnh xe (upload an toàn — đã triển khai)

- [x] `AdminCarImageController`, `CarImageRequest`, `StoreCarImage`.
- [x] Chỉ JPG/PNG/WEBP, ≤ 4 MB, kích thước tối thiểu 320x180; lưu disk `public`.
- [x] Tên tệp = UUID + phần mở rộng do server chọn (`cars/{car_id}/...`), không dùng tên client.
- [x] Một ảnh chính/xe (ảnh mới được đặt chính sẽ bỏ cờ ảnh cũ), sort_order tăng dần.
- [x] Xóa ảnh chỉ khi ảnh thuộc đúng xe; ảnh seed dưới `public/assets` không bị xóa khỏi đĩa.
- [ ] Chưa làm: sắp xếp lại ảnh bằng kéo thả, resize/thumbnail, xóa nhiều ảnh cùng lúc (P2 nếu cần).

## T05 — Tìm kiếm, lọc, phân trang (đã triển khai)

- [x] Admin: `q` tên/mã (max 100), dòng xe, tình trạng, hiển thị; 20 dòng/trang, giữ query filter.
- [x] Public: `q`, `category` (slug), `status`, `min_price`/`max_price` (VND),
  `sort` newest/price_asc/price_desc; 12 xe/trang, chỉ xe + dòng visible.
- [x] Validation sai → web redirect kèm lỗi; `max_price` không được nhỏ hơn `min_price`.
- [x] Index DB cho filter; test MySQL xác nhận so sánh giá decimal(15,0) với bound integer.

## T06 — Data cho Đạt (hợp đồng trình bày)

- [x] `App\Support\Catalog\CarPresenter::fromCar()` trả allowlist:
  `id, name, category, category_name, code, year, color, price(int), status, status_label,
  specs[], image (URL), image_alt, images[{url}], is_visible`.
- [x] `resources/views/components/car-card.blade.php` nhận đúng shape trên; không serialize model.
- [x] `CarPresenter::fromArray()` cho fixture `resources/data/*-preview.php`
  (bản xem trước UI vẫn chạy ở local/testing tại `/collection-preview`, `/xe-preview/{id}`).
- [x] Public `/xe` và `/xe/{car}` đã nối DB thật, dùng `layouts.app` của Đạt.
- [ ] Còn lại: Đạt review responsive/màu sắc của grid + card mới; nếu muốn JSON API riêng
  (fetch lọc không tải lại trang) phải đăng ký hợp đồng route/response trước.

## T07 — Dữ liệu demo

- [x] `CatalogSeeder`: 7 dòng Toyota, 15 chiếc (mỗi chiếc một mã), ảnh chính từ
  `public/assets/products`, giá công bố minh họa. Idempotent: chạy lại không reset
  giá/visibility mà admin đã sửa. Đã gọi trong `DatabaseSeeder`.
- [ ] Chưa có ảnh thật nhiều góc cho từng chiếc; `HQ` cần bổ sung khi demo.

## Bằng chứng kiểm tra

- `php artisan test`: 47 pass, 1 skip (chỉ dùng MySQL), 429 assertions (SQLite).
- `DB_CONNECTION=mysql DB_DATABASE=toyota_showroom_test php artisan test --filter=CatalogTest`:
  13/13 pass, gồm test giới hạn giá (chạy thật trên MySQL).
- Chưa kiểm: Lighthouse/trình duyệt thật, upload dung lượng lớn, ảnh HEIC,
  nhiều admin upload cùng lúc trên MySQL (`lockForUpdate` chỉ khoá xe, không khoá cả hàng đợi).
