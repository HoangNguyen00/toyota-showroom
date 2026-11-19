### Cập nhật định dạng code và kiến trúc

- Chuẩn hóa định dạng 14 file PHP bằng Laravel Pint.
- Cập nhật các thay đổi định dạng trong Architecture Inventory.
# Nhật ký thay đổi hệ thống

## 2026-11-19 Đồng bộ PR #4 với main đã gộp TV4/TV5

- Ghép main 96aa13c theo review của Hoàng; giữ route Catalog, tư vấn/chat, lịch/dịch vụ
  và giao diện account/customer. Giữ phân trang lịch khách khi hợp nhất Blade.
- Giữ migration hỗ trợ index FK của main; không sửa schema đã merge, dữ liệu demo,
  Policy hoặc PublicationGate. PR #9 tiếp tục giữ thay đổi catalog riêng.
- Bổ sung điều hướng tư vấn/chat cho sales, tư vấn và quản lý xe cho admin từ account;
  không đổi role/Policy. Nút gửi chat có tên truy cập khi chỉ hiện biểu tượng trên mobile.
- Kiểm tra: SQLite 98 passed/1 skipped (802 assertions), MySQL 99 passed (807 assertions);
  5 JS tests và build đạt. Browser 390px: account/chat/lịch không tràn ngang, chuyển danh sách
  và hội thoại; customer/sales gửi nhận thật hai chiều không reload. Chưa kiểm điện thoại thật,
  tải lớn/failover; CI của SHA mới theo dõi trên GitHub.

## 2026-11-17 TV4 — đồng bộ main mới và xử lý conflict PR #1

- Merge main `0b01c6442596255f5e5b7ec3acaebc02f2b8b160` vào nhánh TV4, giữ đồng thời
  backend tư vấn/chat, TV3 Catalog và TV5 lịch hẹn/dịch vụ; không thay schema TV4.
- Hợp nhất route admin theo namespace module; cập nhật contracts, authorization và
  feature status theo mã thật; tái tạo inventory bằng script chính thức.
- Ảnh hưởng quyền: giữ nguyên Policy/invariant từng module và PublicationGate H02/TV4;
  không mở quyền mới, không đưa UI khách ở nhánh tích hợp riêng vào backend TV4.
- Kiểm tra: `composer check` đạt 90 tests / 737 assertions trên SQLite, 1 test biên số
  được skip có chủ đích cho MySQL; inventory, syntax, tracked-path và Pint đạt. `npm run build`
  đạt (Vite chỉ cảnh báo font tĩnh được resolve lúc runtime); 4 JavaScript tests đạt. Chưa chạy
  lại MySQL, Reverb hoặc kiểm thử trình duyệt trong đợt xử lý conflict này; giữ nguyên các kết quả
  lịch sử bên dưới.

## 2026-11-17 TV3 — sửa rollback index Catalog trên MySQL

- CI sau khi ghép TV4 với main phát hiện MySQL không cho xóa index
  `cars(category_id,is_visible)` vì foreign key category đang dùng index đó.
- Thêm migration nối tiếp tạo index hỗ trợ đơn `cars(category_id)`; `down()` chủ đích giữ index
  đến khi migration nền xóa bảng, tránh sửa ngược migration TV3 đã merge hoặc tắt foreign key.
- Không đổi cột, dữ liệu hay quyền. `composer check` local đạt 90 tests / 737 assertions;
  GitHub Actions sau sửa đạt cả PHP SQLite, PHP MySQL và frontend. JavaScript local đạt 4 tests.

## 2026-11-16 TV4 — xử lý xung đột PR #1 trên repository mới

- Ghép main `ed06179` vào backend TV4 `1438af2`, giữ cả route/import chat và lịch hẹn;
  giữ toàn bộ lịch sử hai module và tạo lại inventory thay vì chọn một phía.
- Không đổi schema, dữ liệu demo, quyền hoặc nội dung giao diện khách. UI khách được
  giữ trên nhánh tích hợp riêng của Đạt; PR #1 vẫn dành cho backend TV4.
- Kiểm tra: composer check đạt 78 tests / 635 assertions trên SQLite, Pint/syntax/inventory;
  npm run build và 4 test JavaScript đạt. MySQL 8.4.3: UUID cạnh tranh, send/reassign,
  crash trước/sau commit, gate khi reassign/khóa sales/khóa customer, session loss và
  xe/dòng ẩn đã đạt trên DB thử riêng. Hai phiên Reverb thật nhận tin trong 1303 ms;
  retry/reconnect/CSRF/throttle và stale socket/job sau thu hồi quyền đều đạt.
  Chưa kiểm responsive/thiết bị thật, tải lớn hoặc failover; giữ giới hạn ADR 0006.

## 2026-11-16 Tích hợp lại frontend và TV4 trên repository mới

- Ghép backend sửa xung đột PR #1 (1151428) với giao diện 3dc88d8; giữ cả chat,
  account, lịch TV5, hiệu ứng trang chủ và video Auth. Không đổi schema hoặc dữ liệu demo.
- Giữ shared HTTP chat cho ba role, route customer riêng và cả hai module JS chat/lịch;
  tạo lại inventory theo mã hợp nhất. Backend đã kiểm 78 tests/635 assertions trên
  SQLite và MySQL 8.4.3; 4 test JS, publication boundary, MySQL race/crash/locking đạt;
  hai phiên Reverb thật 1303 ms, retry/reconnect/revoke/stale job đều đạt.
- Nhánh tích hợp: composer check đạt 86 tests/700 assertions, Pint/syntax/inventory;
  5 test JavaScript và Vite build đạt. Browser gửi tin từ customer, phiên sales đọc và
  trả lời; phản hồi hiện ngay trong bubble khách mà không reload. Chưa kiểm mobile mới,
  tải lớn hoặc failover; không tự merge main, CI repository mới được theo dõi riêng.

## 2026-11-05 Video Camry cho đăng nhập / đăng ký

- Layout Auth hỗ trợ clip Camry thật 9 giây, tự phát im lặng/lặp với nút tạm dừng; tôn trọng giảm chuyển động và dừng khi tab/khung không hiển thị.
- Giữ ảnh dự phòng khi thiếu file hoặc phát lỗi. Clip Toyota lưu local tại public storage, không phân phối qua Git; nguồn/đường dẫn/giới hạn sử dụng ở AUTH_VIDEO.md.
- Chỉ thay trình bày Auth, không thay dữ liệu hoặc nghiệp vụ tài khoản. Build/composer check đạt (86 tests / 700 assertions); browser phát 1080p/9s, lặp/pause đạt, đăng ký dùng cùng media. Browser đang giảm chuyển động nên khởi đầu bằng ảnh; chưa kiểm preference false/điện thoại thật.

## 2026-11-05 Sửa chữ trang chủ chồng lên mô tả

- Thu gọn và đặt chữ TOYOTA trong vùng phía trên xe, chừa cột mô tả bên phải; mobile dùng cỡ chữ riêng, giữ chữ bạc sắc nét.
- Không đổi thời gian clip tách xe hoặc nghiệp vụ. Ảnh đăng nhập giữ nguyên trong khi chọn nguồn clip xe chạy phù hợp.
- Kiểm tra: Vite build và composer check đạt (86 tests / 700 assertions, Pint/inventory/syntax/encoding). Browser desktop 1280px ở đoạn tách xe: hai vùng chữ không giao nhau, cách nhau hơn 63px; chưa kiểm lại điện thoại thật.

## 2026-10-28 Nhánh tích hợp chat khách hàng với TV4

- Ghép backend TV4 trên nhánh riêng; giữ lịch TV5 và account FE.
- QA: 86 tests/700 assertions + 5 JS tests + Vite build đạt; browser hai chiều không reload, reconnect catch-up và giữ draft đạt, tạo yêu cầu thật ở DB QA riêng. Chưa xác minh viewport mobile mới/MySQL concurrency.
- GET workspace chỉ truy vấn own inquiry, danh sách và thread thật; tái sử dụng transport Echo/history/UUID của TV4 với bubble customer, mount lazy và draft theo thread.
- Không sửa schema/seed/PublicationGate/HTTP send. Cần review H02 của Hoàng trước merge main; kiểm thử riêng không chứng nhận race/failover.


## 2026-10-28 Đồng bộ lịch hẹn với tài khoản khách

- Ghép main ed06179 vào nhánh giao diện, giữ appointment TV5 và account/chat preview.
- AccountController chỉ đọc lịch sắp tới của đúng customer; điều hướng và danh sách lịch dùng layout customer, hủy theo endpoint hiện có với lý do/CSRF.
- Không đổi migration, seed, trạng thái hoặc nghiệp vụ xác nhận/đổi lịch; chưa ghép TV4 trong mốc này.
- Kiểm tra bằng suite chung và browser với DB QA riêng; không reset DB người dùng.


## 2026-10-23 Giao diện tài khoản và chat khách hàng

- Customer có layout riêng, hồ sơ từ session, hero ảnh xe chạy, điều hướng tài khoản/chat/catalog và logout POST CSRF.
- Chat preview local/testing có danh sách/search/thread/new/closed/composer/feedback chưa kết nối, bản nháp DOM; không gửi request hoặc giả tin thành công.
- Giữ màn account admin/sales, backend Auth/H02 và toàn bộ schema. Không seed hoặc sửa database người dùng.
- Route preview customer-only, không thay shared HTTP/private channel TV4. Lịch hẹn ghi sắp có; không giả số liệu.
- Kiểm tra composer check/build và browser responsive; xem hướng dẫn CUSTOMER_ACCOUNT_CHAT_UI.md về phạm vi chưa nối backend.


## 2026-10-20 Lịch hẹn và dịch vụ (TV5 - feature/appointment-schedule)
- Thêm: routes/appointments.php, AppointmentController, StoreAppointmentRequest/
  UpdateStatusRequest, AppointmentService, AppointmentPolicy/ServicePolicy,
  views appointments (create/index/manage), admin/services, customer/staff
  appointments, tests Feature/Appointment (AppointmentTest, ServiceCrudTest).
- Sửa: routes/admin.php, routes/staff.php, routes/customer.php, bootstrap/app.php
  đăng ký nhóm route appointments; enum AppointmentStatus/AppointmentType/ServiceType
  bổ sung giá trị no_show và loại bảo dưỡng.
- Quyền: guest/customer đặt lịch viewing/maintenance; customer chỉ xem/hủy lịch của
  mình trước giờ; sales/staff xem scoped; admin xác nhận (khóa slot + kiểm tra trùng giờ
  trong transaction), hủy với lý do, kết thúc sau giờ xác nhận, đánh dấu no_show.
- Kiểm tra: php artisan test 50 test/418 assertions pass; pint pass;
  architecture-inventory --check pass. Chưa kiểm tra trực quan trình duyệt và MySQL thật.
## 2026-10-24 TV4 — đồng bộ tài liệu sau commit/push

- Sửa trạng thái bàn giao theo commit chức năng `a7bf4059764fc506f1ee3e1aa10a8ea2b61199d6`
  đã push lên `feature/inquiry-chat-websocket`; chưa tạo Pull Request TV4, chưa merge main.
- Giữ kết quả kiểm thử lịch sử, quyết định ADR, giới hạn bảo mật và yêu cầu owner review;
  không sửa mã ứng dụng, schema, dữ liệu hoặc quyền. Regenerate inventory bằng script chính thức.
- Kiểm tra: inventory --check, git diff --check và đối chiếu diff từng file; không chạy
  lại MySQL/Reverb trong đợt đồng bộ tài liệu. Các mục dưới là lịch sử các đợt triển khai.
- Composer check theo quy định repo đạt: SQLite 57 tests / 507 assertions, syntax/encoding/Pint;
  không thay các số liệu MySQL/Reverb lịch sử bằng kết quả chạy mới.

## 2026-10-24 F01 — tích hợp publication/revocation theo phê duyệt giới hạn

- Thêm PublicationGate chung theo DB: MySQL GET_LOCK session PDO riêng không TTL/
  reconnect, SQLite flock test; bounded wait, ownership check, fail closed với nested/
  outer transaction. Publisher giữ gate qua synchronous dispatch, nhả row locks trước network.
- Bọc InquiryService::update và CHỈ transaction gốc UpdateUserStatus::handle bằng gate;
  không đổi Policy/active/version/idempotency/deadlock retry H02. Hoàng phải review trước merge.
- Thêm regression publication/outside-transaction/rollback-idempotency; H02 tests dùng
  DatabaseMigrations + DB guard thay implicit test transaction. Giữ toàn bộ ca cũ.
- Security gate từ FAIL trước sửa sang PASS; thêm MySQL independent revoker, process
  death/session-loss trước dispatch, Reverb thật hai socket cho reassign/lock sales/customer.
- Không đổi schema/config/dependency/Auth khác H02/Catalog/Appointment/Frontend của Đạt,
  không merge PR #11; tại đợt khắc phục này chưa stage/commit/push. F02/F03/F04 regression chạy lại.
- Dữ liệu demo nguyên fingerprint. Giới hạn single-primary/gate ownership/in-flight,
  head-of-line blocking và acceptance không đồng nhất receipt ở ADR 0006; kết quả thật TV4_VALIDATION.

## 2026-10-24 Khắc phục findings TV4 trong phạm vi owner

- Sửa F02: message và database job ghi cùng connection/transaction, beforeCommit chỉ
  cho INSERT intent; driver/connection sai fail closed. Không thay bảng/dependency;
  rollback/enqueue failure không để message thiếu intent, worker chỉ thấy sau commit.
- Sửa F03 phía TV4: locking reads user → category shared → car shared; từ chối xe
  chuyển category, không khóa thêm ngược thứ tự. TV3 cần review khi tích hợp CRUD.
- Sửa F04: composer input revision/raw value bảo vệ draft mới khi response chậm;
  giữ UUID retry/error. Không đổi UI/layout/CSS khách hay contract response/event.
- Giữ F01 OPEN: chưa sửa H02 khi chưa được phép. Thêm security regression chính thức
  exit 1 cho reassign và H02-lock commit trước dispatch; không che bằng mock pass.
  Đề xuất fence/phối hợp Hoàng ở TV4_INTEGRATION. Không network trong DB transaction.
- Thêm PHPUnit enqueue/rollback/retry/config guard, Node DOM regression, MySQL
  process crash trước/sau commit và Catalog UPDATE locking độc lập. Không xóa test cũ.
- Không sửa Auth/H02/Catalog/Appointment/schema/lockfile hoặc merge PR #11; không
  reset/seed demo. Kết quả thực tế/giới hạn ở TV4_VALIDATION; chưa READY vì F01.

## 2026-10-24 Backend TV4 tư vấn/chat trên nhánh riêng (lịch sử trước commit)

- Thêm InquiryCloseResult, InquiryPolicy/MessagePolicy, FormRequests/controllers,
  InquiryLock/InquiryService/MessageService/MessageData, BroadcastInquiryMessage job
  và InquiryMessageCreated event; thêm tests và harness MySQL/Reverb độc lập.
- Thêm POST /tu-van, customer/staff/admin scoped list/detail và mutation theo quyền;
  chat HTTP shared auth/active, private channel version thật thay deny-by-default.
- Thêm Blade staff tối thiểu + Echo JS: history, retry UUID, catch-up, lỗi/kết nối,
  textContent chống XSS. Không chép frontend customer PR #11 (vẫn draft/unmerged).
- Shared files: routes/web/customer/staff/admin/channels, app.js; giữ middleware,
  H01/H02/TV3/TV5 và layout/CSS. Không đổi model/migration/schema/dependency/lockfile.
- Sửa scripts/project-files.php loại trừ public/hot (Vite marker vốn bị Git ignore)
  khỏi inventory; thêm regression InventoryHashTest để CI không phụ thuộc Vite local.
- Dữ liệu/quyền: không hard-delete; owner/sender từ session; internal_note không
  trả customer/events; users theo ID → inquiry và recheck trong transaction;
  unique sender/UUID + khóa sender; enqueue sau commit; job recheck version/active.
- DB demo toyota_showroom không migrate/reset/seed; kiểm MySQL trên DB local test
  mới, user riêng chỉ có quyền DB test. Root password chỉ nhập hộp thoại cục bộ.
- Đã thử MySQL concurrent UUID/send+reassign; hai phiên Reverb thật 1731 ms;
  retry/catch-up, job chờ và socket kênh cũ sau chuyển sales/khóa user: đạt.
  Full regression/build và composer check ghi kết quả ở docs/testing/TV4_VALIDATION.md.
- Chưa nối public form/UI customer PR #11, chưa review trực quan responsive;
  không nhận hoàn thành các module khác. Hợp đồng ở docs/development/TV4_INTEGRATION.md.

## 2026-11-16 Sửa test frontend phụ thuộc ID cứng /xe/1 trên MySQL

- Sửa: `tests/Feature/Auth/FrontendIntegrationTest` dùng cứng `/xe/1` cho mọi role; trên MySQL
  (bộ test sạch, không seeder) xe đầu tiên không có ID 1 nên nhận 404. Giờ dùng ID của xe
  visible tạo riêng trong test (`/xe/{car->id}`).
- Ảnh hưởng: chỉ test, không đổi code chạy thật. Trên SQLite pass cả bản cũ.
- Kiểm tra: chạy đủ suite với DB_CONNECTION=mysql và DB riêng `toyota_showroom_test`:
  69 passed, 0 failed.

## 2026-11-16 Hiện nút Xóa dòng xe dạng disabled khi dòng xe còn xe

- Sửa: `resources/views/admin/car-categories/index.blade.php` — trước đây nút Xóa bị ẩn hẳn
  khi `@can('delete', ...)` thất bại (dòng xe còn xe), khiến quản trị viên tưởng chức năng xóa
  chưa tồn tại. Giờ hiển thị chữ "Xóa" màu xám, không bấm được, tooltip giải thích lý do.
- Ảnh hưởng dữ liệu/quyền: không đổi; Policy `CarCategoryPolicy::delete` và controller
  `destroy` vẫn từ chối xóa dòng xe còn xe (server-side giữ nguyên).
- Kiểm tra: CatalogTest 12 pass/1 skip.

## 2026-10-20 Sửa lỗi mở form thêm xe và cho chọn ảnh ngay khi thêm xe

- Sửa: `AdminCarController@create` trước đó không truyền biến `$car` cho form dùng chung
  `admin/cars/_form.blade.php`, gây lỗi "Undefined variable $car" khi mở /admin/cars/create.
  Giờ truyền `new Car` (form vẫn phân biệt thêm/sửa bằng `$car->exists`).
- Thêm: chọn ảnh ngay khi thêm xe — ô `images[]` nhiều file trong `_form` (chỉ ở chế độ tạo,
  enctype multipart), validate `images`/`images.*` trong `CarRequest` (tối đa 8 ảnh, JPG/PNG/WEBP,
  ≤4 MB, ≥320x180), `store()` lưu từng ảnh qua `StoreCarImage::handle` sau khi tạo xe.
- Ảnh hưởng dữ liệu/quyền: không đổi schema/route; ảnh vẫn tên UUID do server đặt, quyền upload
  vẫn qua Policy trong service/kênh hiện có; form sửa xe không đổi.
- Kiểm tra: CatalogTest 12 pass/1 skip; không chạy thử upload nhiều ảnh qua trình duyệt thật,
  chưa kiểm MySQL riêng cho các rule mới (validate ảnh phụ thuộc extension).

## 2026-10-19 TV3: CRUD xe, dòng xe, ảnh và catalog public (nhánh feature/car-crud-search)

- Thêm: migration index `cars(category_id,is_visible)`, `cars(name)`, `car_images(car_id,is_primary)`;
  factory Car/CarCategory; scope `Car::publiclyVisible`, `CarCategory::visible`; `CarImage::url()`.
- Thêm: `Controllers/Catalog` (AdminCarController, AdminCarImageController, AdminCarCategoryController,
  CarsController), `Requests/Catalog`, `Services/Catalog` (SaveCar, StoreCarImage),
  `Policies/CarPolicy`, `Policies/CarCategoryPolicy`, `Support/Catalog/CarPresenter`.
- Thêm view: `admin/cars/{index,create,edit,_form}`, `admin/car-categories/{index,form}`;
  public `cars/index`, `cars/show` dùng DB; `components/car-card` nhận shape của CarPresenter.
- Đổi route: `/xe` và `/xe/{car}` giờ là catalog DB (`cars.index`, `cars.show`); fixture UI
  chuyển sang `/collection-preview` và `/xe-preview/{id}` (cars.collection-preview/cars.preview.show,
  chỉ local/testing). `routes/catalog.php` require một lần từ web.php. Admin thêm
  `admin.car-categories.*`, `admin.cars.*`.
- Thêm `CatalogSeeder` (7 dòng, 15 chiếc, ảnh chính từ public/assets/products) và gọi trong
  DatabaseSeeder; idempotent, không reset giá/visibility đã sửa.
- Ảnh: JPG/PNG/WEBP ≤4 MB ≥320x180, tên file UUID do server đặt, một ảnh chính/xe.
- Ảnh hưởng dữ liệu/quyền: migration chỉ thêm index, chạy `migrate` + `storage:link`;
  catalog ghi chỉ admin (role + Policy), public chỉ đọc bản ghi visible.
- Kiểm tra: `php artisan test` 47 pass/1 skip (SQLite);
  `DB_CONNECTION=mysql DB_DATABASE=toyota_showroom_test php artisan test --filter=CatalogTest` 13/13 pass.
- Chưa kiểm: trình duyệt/thiết bị thật, upload nhiều admin đồng thời, ảnh HEIC/LFS, JSON API riêng.

## 2026-10-19 Gộp năm nhánh vào main

- Gộp theo thứ tự docs/review-v6-srs, feature/auth-middleware, feature/admin-users,
  feature/frontend-layout, feature/frontend-auth-integration; giữ toàn bộ lịch sử commit.
- Xung đột routes/web.php: giữ cả route chủ (home/preview) lẫn route auth/tài khoản.
- Xung đột CONTRACTS.md, FEATURE_STATUS.md, INVENTORY.json: hợp nhất mô tả và tạo lại inventory.
- FoundationTest dùng `withoutVite()` và chờ `ensure.active` thu hồi phiên khi user bị khóa
  (hành vi H01), thay vì giữ phiên và nhận 403.
- Kiểm tra: composer check pass, npm run build pass, php artisan test 35 test/328 assertions.
  Chưa kiểm MySQL thật và giao diện trên trình duyệt/thiết bị.

## 2026-10-18 Chuyển động nhẹ cho hai ảnh trang chủ

- Giữ hiệu ứng hiện dần; thêm vệt sáng chạy một lần trên ảnh đèn và zoom vô lăng theo cuộn.
- Hover phóng nhẹ, viền sáng; hỗ trợ reduced-motion, chỉ cập nhật ảnh trong vùng nhìn bằng requestAnimationFrame.
- Chỉ CSS/JS trang chủ, không đổi dữ liệu hoặc quyền. Kiểm tra composer check, build và trình duyệt.

## 2026-10-18 Giao diện đăng nhập và đăng ký

- Dùng ảnh xe chạy do người dùng cung cấp, ảnh trái/form xanh đen phải; mobile ảnh trên.
- Layout auth riêng, hiện/ẩn mật khẩu, loading submit và phục hồi khi quay lại trang.
- Giữ route/CSRF/validation/controller H01; không đổi trang tài khoản hay database.
- Kiểm tra composer check và build; kiểm trực quan theo kích thước trình duyệt khả dụng.


## 2026-10-18 Tích hợp thử H01 với frontend

- Nhánh riêng từ frontend, merge H01 668b65e/PR #2 và nền phụ thuộc #1; không merge main/#4.
- Giữ controller/request Auth của Hoàng, home/catalog/video của Đạt; nối header login/account/logout.
- Resolve routes/contracts/status theo cả hai module, tạo lại inventory format 2.
- Thêm test điều hướng guest/ba role/login/logout/CSRF và ghi review tại AUTH_FRONTEND_INTEGRATION.md.
- Không thay schema/dependency/MySQL demo. Form Auth giữ bản tối thiểu, chat/booking chưa kết nối.
- Kiểm tra local: 27 tests/263 assertions, Pint, build, route:list và diff check đạt; chưa kiểm local MySQL/đầy đủ responsive.


## 2026-10-18 Đồng bộ nhánh giao diện

- Giữ hai ảnh và nền tối đã lưu local; ghép commit sửa smoke test af2091f từ GitHub.
- Không đổi main, backend hoặc thời gian video; tạo lại inventory sau khi ghép.

## 2026-10-17 Chuẩn bị review — smoke test độc lập Vite build

- CI frontend build đạt nhưng PHP test không có bước tạo Vite manifest.
- Tái hiện điều kiện thiếu assets: ExampleTest trả 500 ViteManifestNotFoundException;
  HomePreviewTest/CollectionPreviewTest đã withoutVite, chỉ smoke test cũ chưa có.
- Sửa ExampleTest dùng withoutVite để kiểm render HTTP độc lập asset pipeline.
  Không sửa giao diện, route, fixture, dependency hoặc bỏ kiểm tra npm build riêng.
- Probe trước sửa fail, sau sửa pass; full composer check kiểm lại trước push.

## 2026-10-16 Hai góc nhìn Toyota dưới hero

- Thêm hai ảnh người dùng cung cấp: đèn xe và vô lăng, bố cục ảnh/chữ xen kẽ.
- Nền đen than đồng bộ dịch vụ/showroom/footer; chỉ áp dụng trang chủ.
- Reveal một lần và zoom ảnh chậm, tôn trọng reduced motion; không JS vẫn thấy nội dung.
- Giữ nguyên video, timeline, route và backend. CTA vẫn là preview, không tự nhận đặt lịch thật.
- Kiểm tra render có hai ảnh, composer check, build và inventory; chưa kiểm thử mọi thiết bị thực.

## 2026-10-16 Card sản phẩm và tên xe chuyển dọc

- Theo ảnh tham khảo: sidebar bộ lọc, card trắng bo góc, 3 cột desktop,
  ảnh từ bộ xe-san-pham đã bàn giao; thông tin minh họa, không bịa công suất/tốc độ.
- Tên dòng xe lớn tách khỏi slide ngang; tên vào từ dưới, tên cũ đi lên.
- Trở lại clip tách nền/canvas, giữ thời điểm dừng/tốc độ người dùng chỉnh.
- Không thêm so sánh/cấu hình xe của website tham khảo vào phạm vi đề tài.
- Kiểm tra build, render/404/trạng thái và browser slider/filter/cards.

## 2026-10-16 Thử clip gốc giữ nền studio

- Người dùng yêu cầu thử clip chưa xóa nền: native video 1280x720 nền xám sáng,
  đặt trong khung studio trên hero tối; không xóa nền/không xử lý pixel canvas.
- Giữ endTime 8 giây và tốc độ progress * 11 do người dùng chỉnh, Blob để seek local.
- Giữ file clip cũ để có thể so sánh/đổi lại, không ghi đè tài nguyên nguồn.
- Build/test và kiểm tra browser khung hình, cuộn thuận/ngược.

## 2026-10-16 Gió khí động học và chữ rõ nét

- Thay vệt ngang rất mờ bằng ba đường gió SVG cong ôm dáng xe, có vệt chạy
  theo path, stroke xanh băng rõ hơn; vẫn mờ đi khi tách xe và pause ngoài màn hình.
- Chữ CAMRY màu bạc đặc, bỏ text-shadow/glow; không đổi font hay hình xe.
- Không thay timeline người dùng, DB/route/dependency; kiểm tra build/browser.

## 2026-10-16 Chuyển động không gian quanh Camry

- Theo phương án được duyệt: 5 luồng gió, 3 vạch đường, ánh sáng quét chậm.
- Gió/đường mờ theo tiến trình tách, scanner hiện; CSS transform/opacity,
  tự pause khi ngoài màn hình/tab ẩn. Không sửa video/ảnh, endTime hoặc tốc độ tua.
- Kiểm tra build, inventory và browser; không thêm dependency/DB hay endpoint.

## 2026-10-16 Tăng độ rõ chữ Camry

- Chữ CAMRY có nền chữ xanh bạc, viền sáng hơn và glow nhẹ theo phản hồi.
- Chỉ sửa kiểu chữ hero, giữ chuyển động và thời gian người dùng tự chỉnh.
- Build và kiểm tra inventory; hiệu ứng gió/đường chạy mới chỉ là đề xuất.

## 2026-10-16 Giữ khung xe tách theo ảnh người dùng

- Giới hạn video Camry ở giây 7.5, giữ trong 25% cuối đoạn cuộn sticky.
- Cuộn ngược vẫn lắp xe; không thay clip, bố cục, DB hoặc route.
- Kiểm tra build và mốc currentTime trong trình duyệt.

## 2026-10-16 Phong cách công nghệ trang chủ

- Theo người dùng duyệt: nền than chì/xanh đen, ánh sáng xanh băng, vòng sàn,
  grid kỹ thuật nhẹ, chữ Camry viền; chữ sáng, CTA đỏ Toyota, dịch vụ nền sáng.
- CSS home-tech riêng và body class home; không thay ảnh/video/DB hoặc quyền.
- Kiểm tra build và trình duyệt ở hero nguyên xe/tách xe.

## 2026-10-16 Trang chủ Camry

- Hero theo Figma 22:43; sticky scroll video/canvas và ảnh dự phòng, responsive.
- Video bàn giao có nền đen: lọc vùng đen liên thông biên canvas, không giả alpha.
- Dịch vụ/showroom và CTA thông báo chưa nối backend; logo về home, link anchor thật.
- Chỉ thay frontend/route preview local; không đổi DB/schema/dependency/quyền.
- Kiểm tra build/test và browser; giới hạn clip mô phỏng trong HOME_PREVIEW.

## 2026-10-16 Thu nhỏ xe và luôn bật chuyển động

- Theo yêu cầu người dùng: giới hạn ảnh hero 620px desktop, 84% mobile,
  giữ tỷ lệ/căn chân bánh; giảm phóng ảnh nguồn 518px.
- Bỏ nút chọn hiệu ứng và logic localStorage; slider luôn chuyển ngang 1300ms.
- Không đổi ảnh, dữ liệu, DB hoặc dependency; build và kiểm tra trình duyệt.

## 2026-10-16 Trang chi tiết xe frontend

- Thêm /xe/{id} local/testing và fixture riêng; gallery ảnh/phóng to, thông tin,
  thông số chờ backend, CTA theo trạng thái và xe liên quan. Không đổi DB/quyền.
- Nối card và hero với chi tiết; Innova fixture sold không nhận đặt lịch.
- Dùng ảnh thật đã có trong bộ tài nguyên, không bịa thông số hay ảnh góc khác.
- Kiểm tra render các trạng thái, 404, build và browser nếu môi trường cho phép.

## 2026-10-16 Làm chậm chuyển xe

- Theo phản hồi người dùng: tăng transition từ 850ms lên 1300ms,
  dùng easing (.45,0,.2,1) để xe tăng tốc và dừng mềm hơn.
- Giữ nút bật/tắt và reduced-motion; không đổi dữ liệu hay quyền.
- Kiểm tra build và inventory sau thay đổi.

## 2026-10-16 Sửa chuyển xe khi giảm chuyển động

- Tái hiện: trình duyệt báo prefers-reduced-motion: reduce nên transition là none.
- Thêm nút bật/tắt hiệu ứng slider, aria-pressed và lưu lựa chọn localStorage;
  lựa chọn rõ ràng của người dùng ưu tiên cài đặt giảm chuyển động của hệ thống.
- Giữ mặc định giảm chuyển động; bật chủ động cho transform 850ms hai chiều.
- Không thay DB, quyền, route hoặc dependency. Kiểm tra trình duyệt và build.

## 2026-10-16 Trang Dòng xe frontend

- Thêm layout app, car-card, trang cars/index theo Figma 22:44, ảnh/font local.
- Slider Corolla Cross → Vios → Innova dịch ngang, không quay vòng; keyboard,
  swipe, reduced-motion. Form lọc/sort fixture và empty state; responsive.
- GET /xe chỉ local/testing; dữ liệu minh họa tách resources/data, không ghi DB.
- CTA chưa tích hợp dùng dialog thông báo; chưa có auth/chi tiết/đặt lịch thật.
- Shared files: routes/web.php, app.css, app.js; không đổi schema/dependency/quyền.
- Kiểm tra build, test nền, browser và giới hạn ghi ở COLLECTION_PREVIEW.md.

Git lưu diff chính xác; file này giải thích thay đổi để người và AI hiểu.
Thêm mục mới lên đầu. Dùng CHANGE_TEMPLATE.md, không chỉ ghi “update code”.


## 2026-10-17 Chuẩn bị review PR và hợp đồng quyền H03

- Kiểm head CI #1/#2/#4 success và chưa có review submission; #3 PHP test fail
  dù frontend build pass. Tái hiện thiếu Vite manifest, sửa smoke test trên nhánh
  FE riêng (af2091f), không thay UI; probe fail trước sửa/pass sau sửa.
- Thêm PR_REVIEW_GUIDE: dependency/base/head CI/checklist/người review đề nghị,
  chưa gửi request hoặc giả nhận approval.
- Thêm AUTHORIZATION_CONTRACT: quyền theo owner/assigned/active/state, response,
  list scope/UUID/link inquiry và ca cần test khi module có code.
- Làm rõ shared HTTP chat ở auth group customer/sales/admin, không nằm trong
  customer-only group; cập nhật CONTRACTS/PARALLEL_WORK/AGENTS/task/status.
- Không thêm Policy/endpoint của module khác, không mở channel; H03 chưa nghiệm thu.

## 2026-10-17 H02 admin quản lý tài khoản

- Thêm UserPolicy, requests/admin controller, service khóa/mở, Blade danh sách/form
  và GET/POST/PATCH admin routes; thêm link quản lý tài khoản cho admin.
- Tạo sales cố định, validation kế thừa Auth, không đổi phiên admin; danh sách có
  search/role/pagination; không xóa/cấp quyền admin hoặc khóa admin.
- Khóa users theo ID rồi inquiry trong transaction, recheck Policy, bump version
  khi khóa, lặp idempotent và giữ assignment/history; rollback cả user khi lỗi version.
- Không đổi schema/dependency hoặc sửa module FE/TV3/TV4/TV5. Socket/job cần TV4 tích hợp.
- 8 test H02 mới; suite local 28 test/222 assertions pass và Pint pass.
  Chưa test hai kết nối MySQL/đánh giá responsive trực quan H02; không claim đã xong.

## 2026-10-16 Rà soát code-first và triển khai song song

- Sửa inventory LF/CRLF đã đưa vào PR #1, merge fix vào nhánh Auth; giải quyết conflict bằng giữ hai mục CHANGELOG và regenerate manifest.
- Bỏ URL prefix customer để khớp /yeu-cau, /lich-hen; giữ auth/active/role và route name.
- Chặn password NUL/>72 byte trước bcrypt; thêm test đầu vào ASCII/Unicode/NUL.
- Thêm PARALLEL_WORK, READINESS_REVIEW và ADR 0004: code-first/migration, shared
  files, route namespace, fixture/seed, review chéo, thứ tự khóa và tích hợp.
- Đồng bộ SRS/AGENTS/README/kiến trúc/hợp đồng/SETUP/CONTRIBUTING/PR template.
- Không sửa migration/DB hoặc triển khai thêm nghiệp vụ P2. Các service/chat/lịch
  chưa có không được nhận hoàn tất bằng việc thêm quy ước.
- Kiểm local: composer check pass 20 test/158 assertions, Pint/cú pháp/encoding.
  CI PR #1 đã success run 38884213819; PR #2 chạy lại sau push.
  Không dùng test H01/inventory làm bằng chứng toàn bộ P1.

## 2026-10-16 H01 tài khoản và task list Hoàng

- Thêm HOANG_TASKS: H01–H05, không làm thay các module của nhóm.
- Thêm FormRequest/controller/Blade/routes đăng ký, đăng nhập, đăng xuất, tài khoản.
- Login failure-only 5/phút/IP+email; xóa limiter login đếm request chưa dùng.
- Middleware active thu hồi session trên web/JSON của user đã khóa; cấu hình redirect.
- Không đổi schema, dependency hoặc mở private channel. Admin user/Policy và thu hồi
  socket/job chat vẫn ở H02/tích hợp TV4, chưa nhận hoàn thành.
- 11 test Auth mới; tổng 17 test/135 assertions pass local SQLite, Pint/build pass.
- Browser lỗi môi trường nên chưa kiểm trực quan; MySQL xem CI, chưa claim local.

## 2026-10-16 Sửa inventory Windows/Linux

- CI PR tài liệu và Auth dừng ở inventory vì hash CRLF cục bộ khác LF Git checkout.
- Chuẩn hóa CRLF thành LF khi băm tệp text, giữ nguyên byte tệp binary; manifest format 2.
- Thêm test phát hiện đổi nội dung, tương đương LF/CRLF và giữ byte DOCX.
- Không sửa schema hoặc file nguồn Word. Kiểm lại CI trước khi nhận đủ điều kiện merge.

## 2026-10-16 Bổ sung tên thành viên

- Xác nhận TV3 Võ Nguyễn Thành Đạt, TV4 Nguyễn Huy Hoàng, TV5 Phan Võ Chí Bảo.
- Đồng bộ SRS, phân công và chủ module trong FEATURE_STATUS; phân biệt các tên Đạt/Hoàng.
- Giữ nguyên trách nhiệm và phạm vi; chưa thêm CODEOWNERS vì chưa có GitHub username.
- Chỉ sửa tài liệu và inventory; kiểm tra theo quy trình repo, không thay mã hoặc DB.

## 2026-10-16 Thu gọn phạm vi theo môn học và Toyota

- Thêm COURSE_SCOPE: nguồn đề cương trang 4–14, Rubric 2 đúng, đối chiếu thực hành và cổng P1/P2.
- SRS 1.4 viết lại phạm vi; lưu nguyên SRS 1.3, Word v6 không đổi.
- Giữ chat WebSocket văn bản/quyền/retry, CRUD, tìm/lọc/phân trang, AJAX form và lịch tiếp nhận.
- Hoãn InquiryNote/code/closed_at/other, audit UI, control channel tự chuyển, đổi lịch/mở lại và công cụ giá.
- Làm rõ từng chiếc xe/mã riêng, giá công bố tham khảo, bảo hành chỉ giới thiệu; không mô phỏng xưởng.
- Đồng bộ chỉ dẫn AI, kiến trúc/dữ liệu/contracts/trạng thái/phân công/nghiệm thu; không sửa mã nghiệp vụ, DB hoặc dependency.
- Kiểm tra: composer check pass (6 tests/19 assertions, cú pháp/encoding và Pint);
  inventory cập nhật 159 tệp. Không đổi mã nghiệp vụ, không nhận test nền là nghiệm thu P1.

## 2026-10-16 Rà soát v6 và SRS

- Thêm Word v6 nguyên byte, SRS 1.3, bản trích v6, SRS 1.2 gửi ban đầu, review và ADR 0002.
- Sửa metadata, enum/active, sau commit, UUID/payload, reconnect/version, khóa lịch,
  ảnh nháp/upload; tách schema đích khỏi baseline.
- Đồng bộ AGENTS/README/spec/contracts/kiến trúc/trạng thái/phân công/nghiệm thu.
- Công cụ inventory thêm hai tài liệu gốc; không đổi app/DB/migration/model/dependency.
- Đã chạy composer check: inventory 156 tệp, encoding/cú pháp, 6 test/19 assertions và Pint đều pass.
  Đã đối chiếu hash nguồn và đường dẫn tài liệu; CI xem trong PR. FR mới vẫn cần test khi làm module.

## 2026-10-14 Xác nhận kiểm tra CI của bản nền

- Sửa: FEATURE_STATUS và BASELINE_VALIDATION ghi kết quả GitHub Actions thực tế,
  thay trạng thái dự kiến kiểm tra MySQL bằng kết quả pass ở CI.
- Bằng chứng: run 38555128110 của commit 33cec56; frontend và PHP SQLite/MySQL đều success.
- Không thay mã nghiệp vụ/schema. Cập nhật inventory để phản ánh các file Markdown đã sửa.
- Hạn chế giữ nguyên: chưa có chat end-to-end hoặc service xác nhận lịch để thử cạnh tranh.

## 2026-10-14 Khởi tạo nền tảng

- Thêm: Laravel 12 và dependency Reverb/Echo, schema 8 core + audit, enums/models,
  active/role middleware, seed user local, cấu trúc module và tài liệu nhóm/AI.
- Sửa so với nguồn v5: thống nhất tên trường, giới hạn form, biến broadcasting,
  quy tắc overlap, reserved và số bảng; chi tiết ở SOURCE_REVIEW/ADR 0001.
- Thay: README/workflow/changelog mẫu upstream bằng hướng dẫn và CI cho nhóm.
- Xóa: workflow bot upstream cho issue/PR/update changelog; không xóa mã thành viên.
- Kiến trúc: monolith MVC, MySQL, Blade/Tailwind, Reverb; chưa có nghiệp vụ online bán hàng.
- Trạng thái: nền tảng; auth/CRUD/chat/lịch hẹn end-to-end chưa triển khai.
- Kiểm tra và hạn chế: xem BASELINE_VALIDATION.md; không coi CI pass là đạt điểm mục tiêu.
