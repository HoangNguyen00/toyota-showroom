# Video nền đăng nhập / đăng ký

Ngày 05/11/2026. Layout Auth dùng clip Camry thật nếu có media local, nếu thiếu thì tự dùng ảnh xe chạy cũ. Không thay controller, session hoặc form tài khoản.

- Nguồn: Toyota UK, “Toyota Camry Hybrid European B-Roll (2019 – Current)”, ngày 15/04/2019: https://media.toyota.co.uk/videos/toyota-camry-hybrid-european-b-roll-2019-current/
- Video gốc Vimeo 330437367; bản xem trên máy lấy giây 26–35, 1920×1080, 25fps, H.264, bỏ âm thanh. Xe Camry màu tối trên đường có cây xanh, góc quay phía trước.
- Clip và poster nằm ở `storage/app/public/site-media/camry-driving.mp4` và `camry-driving-poster.jpg`, qua junction `public/storage`. Không commit hoặc phân phối lại media Toyota trong Git. Điều khoản nguồn: https://media.toyota.co.uk/legal-notice/ ; không coi link tải công khai là giấy phép stock tự do, cần xác nhận quyền trước khi tái sử dụng/phát hành ngoài bản xem trên máy.
- Thành viên khác dùng media có quyền sử dụng tại đúng hai đường dẫn trên, chạy `php artisan storage:link` nếu thiếu liên kết. Nếu không có clip, layout vẫn hiện ảnh cũ, không tạo request tới file bị thiếu.
- Tự phát im lặng, lặp, phát inline; ảnh dự phòng vẫn có đến khi video thực sự phát. Có nút tạm dừng/phát. Lỗi media trở về ảnh; tab/khung ra ngoài màn hình thì dừng; chế độ giảm chuyển động không tự phát.
- Không tải video từ website bên ngoài khi khách mở trang. Nguồn media cục bộ không được đưa vào inventory hoặc fixture test.

Kiểm tra 05/11: build và composer check đạt (86 tests / 700 assertions, Pint/encoding/syntax/inventory). Browser nhận video 1920×1080, 9 giây, muted/loop; phát hết rồi lặp, nút pause dừng và đổi nhãn đúng, đăng ký dùng cùng media. Browser kiểm tra có prefers-reduced-motion=true nên khởi đầu bằng ảnh và phát bằng nút; chưa xác minh autoplay với preference false hoặc điện thoại thật.
