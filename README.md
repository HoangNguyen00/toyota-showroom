# Toyota Showroom

Đồ án Lập trình mã nguồn mở của nhóm 5 người. Website giới thiệu xe, giá công bố,
tiếp nhận tư vấn, chat thời gian thực và lịch xem xe hoặc bảo dưỡng.
Việc mua bán, giấy tờ và thanh toán được thực hiện tại showroom trong phạm vi bài.

**Nhánh tích hợp đã nối catalog, tư vấn/chat và lịch hẹn; vẫn cần nhóm review và nghiệm thu P1.**

Trong local/testing: mở `/` xem trang chủ Camry; `/xe` và `/xe/{car}` đọc xe/ảnh/giá
từ DB, có slider giới thiệu, tìm/lọc/sắp xếp và 12 xe/trang. `/collection-preview`
và `/xe-preview/{id}` giữ fixture thiết kế riêng. Chạy `npm run dev` khi sửa hoặc
`npm run build` để xem bản build. Giá seed là minh họa, chưa phải giá lăn bánh.
Xem docs/development/HOME_PREVIEW.md và CATALOG_CARDS.md.
Đã có Laravel, cấu hình Reverb, schema nền, model, middleware và tài liệu.
H01 đã có đăng ký/đăng nhập/đăng xuất và màn tài khoản tối thiểu.
H02 có quản lý user phía admin ở /admin/users (tạo sales, khóa/mở customer/sales).
Admin quản lý xe/dòng/ảnh tại `/admin/cars`; tư vấn gửi JSON, lịch đi qua TV5.
Customer dùng `/tai-khoan/tin-nhan` sau khi hồ sơ được phân công; chat cần Reverb/worker.

## Đọc trước khi làm

1. [AGENTS.md](AGENTS.md) — quy tắc cho AI và người sửa mã.
2. [SRS 1.4 theo phạm vi môn học](SRS.md) và [chỉ mục đặc tả](docs/requirements/SPECIFICATION.md).
3. [Kiến trúc hệ thống](ARCHITECTURE.md).
4. [Phân công và vị trí thư mục](docs/team/OWNERSHIP.md).
5. [Trạng thái thực tế](docs/architecture/FEATURE_STATUS.md).
6. [Cách góp mã](CONTRIBUTING.md).
7. [Task list của Hoàng](docs/team/HOANG_TASKS.md) — thứ tự phần repo/auth/tích hợp.
8. [Quy ước làm song song](docs/team/PARALLEL_WORK.md) và [kết quả rà soát](docs/requirements/READINESS_REVIEW.md).
9. [Gói review PR](docs/team/PR_REVIEW_GUIDE.md) và [ma trận quyền H03](docs/architecture/AUTHORIZATION_CONTRACT.md).

## Công nghệ đã chọn

PHP 8.3, Laravel 12, MySQL 8, Blade, Tailwind CSS 4, Vite, JavaScript,
Laravel Reverb và Echo. Cài bằng lockfile, không tự nâng phiên bản chính.
Laravel 12 khớp bài thực hành hiện có; các quyết định ở [ADR](docs/decisions/0001-foundation.md).

## Cài trên máy của thành viên

Cần PHP 8.3 với các extension Laravel yêu cầu, `pdo_mysql`, `pdo_sqlite`, `zip`,
Composer 2, Node 22.12 trở lên trong dòng 22, npm và MySQL 8 đang chạy.
Có thể mở terminal của Laragon để PHP và Composer nằm trong PATH.

```sh
git clone https://github.com/HoangNguyen00/toyota-showroom.git
cd toyota-showroom
composer install
npm ci
```

Sao chép `.env.example` thành `.env` (`Copy-Item .env.example .env` trên PowerShell).
Tạo một database MySQL mới tên `toyota_showroom`; chỉnh DB_HOST, DB_PORT,
DB_USERNAME và DB_PASSWORD theo máy của bạn. Điền DEMO_PASSWORD và
REVERB_APP_SECRET riêng trên máy, không đưa các giá trị này lên Git.

```sh
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Mở `http://127.0.0.1:8000`. Local/testing hiện hiển thị giao diện trang chủ; production vẫn giữ welcome. Seeder tạo 1 admin, 2 sales, 3 customer
khi DEMO_PASSWORD có giá trị. CatalogSeeder bổ sung 15 chiếc/7 dòng Toyota với ảnh
repo và giá minh họa; DemoContentSeeder giữ dịch vụ/xe demo TV5. Không dùng dữ liệu
này làm thông tin tồn kho hoặc báo giá Toyota thật. CatalogSeeder không ghi đè xe đã có.
Danh sách email mẫu ở [hướng dẫn môi trường](docs/development/SETUP.md).

Mở /dang-ky hoặc /dang-nhap để thử H01; sau xác thực vào /tai-khoan.
Customer có thông tin cá nhân, lịch sắp tới và điều hướng chat/lịch. Màn admin/sales
giữ điều hướng quản lý theo quyền. Xem [kiểm tra H01](docs/testing/AUTH_VALIDATION.md).

## Khi phát triển chat

Mỗi tiến trình chạy ở một terminal. HTTP gửi/history dùng `/yeu-cau/{inquiry}/tin-nhan`;
admin phân công hồ sơ trước khi customer/sales có thể gửi chat.

```sh
php artisan serve
php artisan reverb:start --host=127.0.0.1 --port=8080
php artisan queue:work --tries=3
npm run dev
```

Chat dùng WebSocket, không dùng polling thay cho thời gian thực.
HTTP dùng để gửi/lưu tin, WebSocket dùng để nhận sự kiện.
Đọc [hợp đồng chat](docs/architecture/CONTRACTS.md) trước khi viết mã.

## Kiểm tra và cập nhật kiến trúc

```sh
php scripts/architecture-inventory.php --write
composer check
npm run build
git diff --stat
```

Danh mục [INVENTORY.json](docs/architecture/INVENTORY.json) ghi đường dẫn và hash
các tệp được kiểm soát. Nếu thêm, sửa hoặc xóa tệp, bước kiểm tra chỉ ra khác biệt.
Git lưu lịch sử; [nhật ký](docs/changes/CHANGELOG.md) giải thích lý do thay đổi.
Quy trình này hỗ trợ kiểm soát, không tự bảo đảm mọi công cụ AI đều tuân thủ.


## Tài liệu ngày 16 tháng 10 năm 2026

Xem [rà soát v6/SRS](docs/requirements/REVIEW_V6_SRS.md) và ADR 0002.
Theo ADR 0003, P1 giữ 9 bảng nền/internal_note; 10 bảng của v6 là phương án cũ. PR tài liệu không thay DB/model/route/nghiệp vụ.
Word giữ nguyên; SRS gửi ban đầu lưu ở docs/source.

Upload 10 ảnh x 5MB: kiểm tra PHP phục vụ web, đề xuất upload_max_filesize >= 5M,
post_max_size >= 64M, max_file_uploads >= 10, chừa multipart và kiểm tra giới hạn proxy.
Server vẫn validate count/size/MIME; chưa tự sửa php.ini trên máy thành viên.

## Phạm vi trước khi chia việc

Đọc [COURSE_SCOPE](docs/requirements/COURSE_SCOPE.md): rubric thật, đối chiếu thực hành,
ràng buộc P1 và điều kiện mới được mở rộng P2. Chat WebSocket văn bản vẫn P1.
Một chiếc xe có mã riêng; giá công bố tham khảo; không làm công cụ tính lăn bánh.
SRS 1.4/ADR 0003 thay phạm vi trái chúng trong tài liệu v6 cũ; Word giữ nguyên.
