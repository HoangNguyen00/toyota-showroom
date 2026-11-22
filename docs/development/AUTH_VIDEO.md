# Video nền đăng nhập / đăng ký

Ngày 05/11/2026. Layout Auth dùng clip Camry thật nếu có media local, nếu thiếu thì tự dùng ảnh xe chạy cũ. Không thay controller, session hoặc form tài khoản.

- Nguồn: Toyota UK, “Toyota Camry Hybrid European B-Roll (2019 – Current)”, ngày 15/04/2019: https://media.toyota.co.uk/videos/toyota-camry-hybrid-european-b-roll-2019-current/
- Video gốc Vimeo 330437367; bản xem trên máy lấy giây 26–35, 1920×1080, 25fps, H.264, bỏ âm thanh. Xe Camry màu tối trên đường có cây xanh, góc quay phía trước.
- Clip và poster nằm ở `storage/app/public/site-media/camry-driving.mp4` và `camry-driving-poster.jpg`, qua junction `public/storage`. Không commit hoặc phân phối lại media Toyota trong Git. Điều khoản nguồn: https://media.toyota.co.uk/legal-notice/ ; không coi link tải công khai là giấy phép stock tự do, cần xác nhận quyền trước khi tái sử dụng/phát hành ngoài bản xem trên máy.
- Thành viên khác dùng media có quyền sử dụng tại đúng hai đường dẫn trên, chạy `php artisan storage:link` nếu thiếu liên kết. Nếu không có clip, layout vẫn hiện ảnh cũ, không tạo request tới file bị thiếu.
- Cập nhật 19/11 theo yêu cầu Đạt: video phủ toàn viewport, tự phát im lặng/lặp/inline,
  không có nút phát/dừng. Form đăng nhập/đăng ký nổi trên nền video bằng kính trong suốt,
  không chia hai cột nền. Video vẫn tự phát khi có reduced-motion; CSS chỉ bỏ transition.
- Ảnh dự phòng vẫn có đến khi video thực sự phát; lỗi media dùng ảnh. Nếu trình duyệt
  chặn autoplay, thử lại khi người dùng tương tác đầu tiên. Việc hệ điều hành/trình duyệt
  tự hạn chế phát nền vẫn nằm ngoài kiểm soát trang; không tải media ngoài website.
- Không tải video từ website bên ngoài khi khách mở trang. Nguồn media cục bộ không được đưa vào inventory hoặc fixture test.

Các kết quả 05/11 bên dưới là lịch sử trước bố cục toàn màn hình; kết quả 19/11 được ghi
trong CHANGELOG và PR giao diện mới.

Kiểm tra 19/11: composer check 108 tests/874 assertions và build đạt; browser 1440x900
và 390x844 xác nhận video phủ viewport, autoplay/muted/loop, không controls/nút video,
tiếp tục phát sau hơn 21 giây. Đăng ký dài cuộn trong khi nền fixed, footer không đè form;
hiện/ẩn mật khẩu vẫn hoạt động. Console không có lỗi, chưa kiểm điện thoại thật.

Kiểm tra 05/11: build và composer check đạt (86 tests / 700 assertions, Pint/encoding/syntax/inventory). Browser nhận video 1920×1080, 9 giây, muted/loop; phát hết rồi lặp, nút pause dừng và đổi nhãn đúng, đăng ký dùng cùng media. Browser kiểm tra có prefers-reduced-motion=true nên khởi đầu bằng ảnh và phát bằng nút; chưa xác minh autoplay với preference false hoặc điện thoại thật.
