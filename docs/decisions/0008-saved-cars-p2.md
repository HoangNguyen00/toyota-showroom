# ADR 0008 — Xe quan tâm lưu theo trình duyệt (P2 đề xuất)

Ngày: 23/11/2026. Trạng thái: đề xuất trong PR riêng, chờ leader duyệt.

## Bối cảnh

SRS 1.4 §9 xếp “so sánh/yêu thích” vào đề xuất sau nghiệm thu P1; COURSE_SCOPE §5 yêu cầu
ACCEPTANCE P1 đạt trước khi chọn P2. Người dùng yêu cầu làm sẵn “Lưu xe quan tâm” trong
nhánh riêng, bản đầu lưu theo trình duyệt, để leader review.

## Quyết định

- Lưu **chỉ ID xe** trong `localStorage` (khóa `toyota-showroom:saved-cars:v1`), tối đa 12,
  loại trùng/giá trị rác; không bảng, migration, session hay cookie mới; không cần đăng nhập.
- Trang `/xe-quan-tam` (cars.saved) là khung; JS gọi `GET /xe-quan-tam/the-xe?ids=` (cars.saved.cards,
  throttle 60/phút) để server render thẻ bằng `x-car-card` qua `Car::publiclyVisible()` +
  `CarPresenter`, giữ thứ tự đã lưu. Xe ẩn, dòng ẩn hoặc đã xóa không trả về và bị
  bỏ khỏi bộ nhớ trình duyệt (không phân biệt lý do để không lộ trạng thái nội bộ).
- Khi route `cars.compare` tồn tại (PR So sánh 2 xe), trang hiện ô chọn 2 xe để mở màn so sánh;
  nếu chưa có, phần này ẩn và luồng lưu xe vẫn dùng độc lập.
- localStorage bị chặn: nút vẫn focus được, bấm hiện thông báo; trang hiện cảnh báo.

## Hệ quả

- Danh sách không đồng bộ giữa thiết bị/trình duyệt; xóa dữ liệu trình duyệt là mất danh sách.
  Đồng bộ theo tài khoản là bước sau, cần bảng mới và quyết định riêng.
- Xe bị ẩn tạm thời rồi hiện lại sẽ không tự quay về danh sách.
- Bỏ được bằng cách gỡ route/view/JS/CSS, không ảnh hưởng dữ liệu.
