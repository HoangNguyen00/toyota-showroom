# ADR 0007 — So sánh 2 xe (P2 đề xuất)

Ngày: 23/11/2026. Trạng thái: đề xuất trong PR riêng, chờ leader duyệt.

## Bối cảnh

SRS 1.4 §9 xếp “so sánh/yêu thích” vào đề xuất sau nghiệm thu P1. COURSE_SCOPE §5 yêu cầu
hoàn tất ACCEPTANCE P1 trước khi chọn **một** P2. Người dùng yêu cầu làm sẵn chức năng so
sánh 2 xe ở nhánh riêng để leader review, không gộp với PR lịch hẹn #12.

## Quyết định

- Một route GET chỉ đọc `/xe/so-sanh` (cars.compare) trong module Catalog, không schema,
  migration hay dependency mới; không lưu lựa chọn vào DB/session.
- Dùng lại `Car::publiclyVisible()` và `CarPresenter::fromCar()`; lớp `CarComparison`
  chỉ dựng dòng so sánh từ mảng allowlist, không serialize model.
- Chỉ so sánh đúng 2 chiếc, khác nhau, cùng công khai (xe và dòng xe). Không ràng buộc
  hai chiếc phải cùng dòng xe.
- Mục thiếu hiện “Đang cập nhật”; dòng khác nhau tô nền và có nhãn chữ “Khác”.

## Hệ quả

- Nếu leader hoãn P2, có thể bỏ route/view/CSS/controller mà không ảnh hưởng dữ liệu.
- Nếu leader giữ, cần cập nhật SRS §9/COURSE_SCOPE ghi đây là P2 đã chọn và thêm kịch bản
  vào ACCEPTANCE. PR này không tự sửa SRS.
