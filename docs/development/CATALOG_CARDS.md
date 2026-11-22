# Card sản phẩm và slider

16/11: /xe và /xe/{car} đọc DB qua TV3; tìm/lọc/sắp xếp/phân trang 12 xe phía server,
giữ bố cục card 3 cột. Ảnh card lấy từ ảnh chính DB, không lấy ảnh ngang slider làm ảnh
sản phẩm. Ba ảnh hero chỉ giới thiệu dòng Toyota; liên kết lọc theo slug, không gán ID fixture.
Tư vấn gửi JSON/CSRF vào TV4, booking chọn đúng ID trong TV5. Preview thiết kế riêng
ở /collection-preview, /xe-preview/{id}. Phần dưới là ghi chú thiết kế trước khi nối DB.

Tham khảo bố cục ảnh người dùng gửi (card Porsche), giữ nội dung Toyota.
Ba card/hàng desktop >=1200px, hai card tablet, một card mobile nhỏ.
Sidebar filter giữ tên field/logic query của frontend fixture, không tạo backend.
Sáu xe dùng ảnh nguyên bản public/assets/products từ thư mục xe-san-pham.
Không dùng ảnh ngang cho card danh sách. Giá/màu/năm/phiên bản là minh họa,
không nhận thông số công suất/tăng tốc/tốc độ Porsche làm thông số Toyota.
Không thêm Compare hoặc Configure vì ngoài phạm vi đã chốt.

Tên lớn trong model-title-window độc lập với car-slide: chữ vào từ dưới, ra trên.
Caption nhỏ cũng xuất hiện từ dưới. Xe tiếp tục trượt ngang 1.3s.
Home quay lại camry-exploded.mp4 và canvas key nền, giữ endTime/tốc độ.
