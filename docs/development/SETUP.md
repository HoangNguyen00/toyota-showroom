# Môi trường phát triển

Dùng README để cài từ lockfile. Không chạy composer update/npm update chỉ để cài.
PHP 8.3, Composer 2, Node 22.12+, MySQL 8; DB timezone ứng dụng Asia/Ho_Chi_Minh.
API ngày giờ dùng ISO 8601 có offset +07:00 hoặc chuyển rõ sang timezone ứng dụng;
không coi chuỗi nhập từ datetime-local là UTC.

`.env.example` là template, `.env` riêng từng máy. APP_KEY tạo bằng artisan.
REVERB_APP_KEY là public client key; REVERB_APP_SECRET chỉ máy chủ, không đưa vào VITE.
Tạo secret riêng trên máy, ví dụ bằng PHP random_bytes. DEMO_PASSWORD chỉ dùng local/testing.
UserSeeder không chạy để tạo tài khoản demo trong production.


## Windows với Laragon

1. Mở Laragon Terminal trong thư mục repo và chạy riêng từng lệnh dưới đây.
   Máy kiểm chứng dùng Node 22.23.4 trong Laragon Terminal và Node 24.22.1 trong
   PowerShell. Dùng terminal có Node trong dải `>=22.12 <23` để cài và build.

   ```cmd
   php -v
   composer --version
   node -v
   php -m | findstr /I "pdo_mysql pdo_sqlite zip"
   ```

2. Nếu thiếu ZIP, chạy `php --ini` và mở tệp ở dòng
   `Loaded Configuration File`. Bỏ dấu chấm phẩy trước `extension=zip`, lưu
   rồi chạy `php --ri zip`. Kết quả mong đợi là `Zip => enabled`.
3. Chạy `composer install`, `npm ci` và `npm run build` theo lockfile.
4. Khởi động MySQL trong Laragon. Với cổng mặc định 3306, kiểm tra kết nối
   bằng `mysql -h 127.0.0.1 -P 3306 -u root -p`. Nhập mật khẩu tại dấu nhắc.
   Tạo database local `toyota_showroom` nếu chưa có, rồi cấu hình `DB_CONNECTION=mysql`,
   `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=toyota_showroom` và
   thông tin tài khoản MySQL trong `.env`. Giữ `.env` đã có và chỉnh các dòng
   liên quan. Nếu chưa có thì sao chép từ `.env.example`.
5. Nếu `APP_KEY` đang trống, chạy `php artisan key:generate`. Sau khi sửa
   cấu hình, chạy `php artisan config:clear` rồi `php artisan migrate:status`.
   Trên database mới, `Migration table not found` cho biết kết nối đã thành công
   và chưa có bảng migration. Chạy `php artisan migrate`.
6. Đặt `DEMO_PASSWORD` ít nhất 8 ký tự, chạy `php artisan config:clear` rồi
   `php artisan db:seed`. Nếu seeder cảnh báo về mật khẩu, kiểm tra lại giá trị
   trong `.env` trước khi chạy lại. Chạy `php artisan serve`, đăng nhập tại
   `/dang-nhap` bằng `admin@example.test` rồi mở `/admin/users`. Sau khi seed
   trên database mới, danh sách có 1 admin, 2 sales và 3 customer.

## Kết quả kiểm chứng trên máy Windows

| Mã nguồn | Môi trường | Kết quả |
| --- | --- | --- |
| `main` tại `ed06179` | PHP 8.3.36, SQLite `:memory:` | `composer check` đạt 56 test, 456 assertions, inventory, cú pháp, encoding và Pint. |
| [PR #12](https://github.com/grumhu124/toyota-showroom/pull/12) tại `1438af2` | MySQL 8.4.3 riêng, Reverb và queue worker | `tv4-mysql.php` và `tv4-realtime.js` đều exit 0. Hai phiên nhận tin, job cũ bị loại sau đổi sales hoặc khóa tài khoản, và phiên mất quyền bị từ chối khi gửi hoặc đăng ký kênh. |

Kết quả Reverb áp dụng cho SHA đã kiểm thử. Chạy lại hai harness trên SHA mới
sau khi PR #12 đồng bộ với main. Khi chạy test trong worktree mới, cấp
`APP_KEY` riêng cho phiên test và giữ database SQLite `:memory:`.
| Role | Email mẫu |
| --- | --- |
| admin | admin@example.test |
| sales | sales1@example.test, sales2@example.test |
| customer | customer1@example.test, customer2@example.test, customer3@example.test |

H01 có /dang-ky, /dang-nhap, /tai-khoan; H02 có /admin/users dành cho admin
để tạo sales và khóa/mở customer/sales (không tạo/khóa admin).
Auth dùng session/cookie, SESSION_DRIVER=database trong env mẫu, không JWT.
Khi đăng ký public, chỉ truyền trường khách được sửa; User.role/is_active không mass assignable.

`php artisan test` mặc định dùng SQLite in-memory, không đụng database demo.
Kiểm tra MySQL riêng: tạo DB test riêng, dùng biến môi trường testing chỉ tới DB đó.
CI có MySQL service riêng để chạy lại cùng bộ test. Tránh migrate:fresh trên database dùng chung.
Khi bổ sung test cạnh tranh lịch, dùng hai kết nối thật MySQL và xác nhận chỉ một lịch thành công.

`.gitignore` giữ dependency, env, uploads, cache và database local ngoài Git.
Đưa migration, seed dữ liệu minh họa, cấu hình template và lockfile vào repo.
Ảnh công khai dùng trong demo phải có nguồn/quyền phù hợp; chưa lấy ảnh hay giá thật trong setup.

Schema quản lý bằng migration, không sửa DB tay thay cho migration.
Mỗi máy tạo database riêng; pull bản mới rồi migrate, không reset DB chung.
Chủ module dùng factory/fixture test riêng; seed demo tổng do Hoàng tổng hợp.
Xem docs/team/PARALLEL_WORK.md về thứ tự nhánh/PR và bàn giao module.
