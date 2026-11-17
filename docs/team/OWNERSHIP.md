# Phân công và vị trí thư mục

Đọc [PARALLEL_WORK](PARALLEL_WORK.md) trước khi làm song song: route/layout/seed,
review chéo, dữ liệu fixture, điểm giao nhau và thứ tự merge PR #1/#2.

| Thành viên | Trách nhiệm | Vị trí chính | Nhánh gợi ý |
| --- | --- | --- | --- |
| Hoàng | Repo, nền tảng, auth, active/role, tích hợp và review; audit UI là P2 | Controllers/Auth, Controllers/Admin/User, Middleware, bootstrap, config, docs, CI | feature/auth-middleware |
| Đạt | Layout/component, UI khách, CSS, responsive, AJAX form và UI chat | resources/views/layouts, components, auth, cars, services, inquiries, appointments, resources/css, resources/js | feature/frontend-layout |
| Võ Nguyễn Thành Đạt (TV3) | Xe, dòng xe, ảnh, giá, search/filter/pagination, admin xe | Controllers/Catalog, Requests/Catalog, Services/Catalog, policies xe, views/admin/cars và categories, tests/Feature/Catalog | feature/car-crud-search |
| Nguyễn Huy Hoàng (TV4) | Tư vấn, phân công, chat, quyền kênh, event và tin nhắn | Controllers/Inquiry, Requests/Inquiry, Services/Inquiry, Events, policies Inquiry/Message, routes/channels.php, views/staff/inquiries, tests/Feature/Inquiry | feature/inquiry-chat-websocket |
| Phan Võ Chí Bảo (TV5) | Dịch vụ, lịch xem/bảo dưỡng, xác nhận/hủy/hoàn tất, xung đột giờ | Controllers/Appointment, Requests/Appointment, Services/Appointment, policies Appointment/Service, views/staff/appointments và admin/services, tests/Feature/Appointment | feature/appointment-schedule |

Đã đủ 5 thành viên theo xác nhận của nhóm. GitHub username chưa được cung cấp;
không đoán username để tạo CODEOWNERS. Hoàng phụ trách repo có thể bổ sung sau.
Tên gọi ngắn Đạt ở phần frontend là thành viên frontend đã phân công trước đó,
khác Võ Nguyễn Thành Đạt (TV3). Hoàng phụ trách repo/auth khác Nguyễn Huy Hoàng (TV4).
Dùng tên đầy đủ kèm TV3/TV4/TV5 khi giao issue để tránh nhầm.
Phân công là trách nhiệm chính, không phải chặn mọi cộng tác.
Đạt quản giao diện khách và layout chung; chủ module làm màn hình admin/staff
trên layout đó, phối hợp cùng Đạt để thống nhất màu/chữ/component.

Schema/model/enum đã có baseline chung. Chủ module tiếp tục hoàn thiện logic
và kiểm thử, tạo migration mới nếu cần; không xóa bảng hoặc sửa migration đã chạy.
DatabaseSeeder do Hoàng tổng hợp; mỗi module bổ sung seeder riêng.
UserSeeder cần mật khẩu demo trên máy; CatalogSeeder có 7 dòng và 15 chiếc Toyota
kèm ảnh chính từ public/assets/products (giá minh họa); chưa có ảnh thật nhiều góc.

Trước khi làm: đọc AGENTS, spec, contracts, trạng thái; chọn issue nhỏ đúng module.
Thay đổi shared files (User, route groups, bootstrap, config, package/lockfile,
migrations chung, layouts) phải mô tả người/module bị ảnh hưởng trong PR.
Mỗi người chịu kiểm thử và báo cáo phần mình; Hoàng không tự nhận sửa mọi lỗi.

## Công việc tiếp theo theo môn học

Đọc SRS 1.4 và COURSE_SCOPE. Không triển khai đích 10 bảng v6 trước P1.
- Hoàng: Auth/Policy/active, failure-only login limiter, seed tổng, review quyền và tích hợp.
- Võ Nguyễn Thành Đạt (TV3): CRUD xe/dòng/ảnh, catalog/search/filter/pagination, Toyota cố định, seed >12 xe.
  Đã triển khai P1 trên nhánh feature/car-crud-search; theo dõi ở [TV3_TASKS](TV3_TASKS.md), chờ review trước khi gộp main.
- Nguyễn Huy Hoàng (TV4): form tư vấn AJAX, phân công/trạng thái/internal_note; chat Reverb văn bản/lịch sử/retry/quyền.
- Phan Võ Chí Bảo (TV5): nội dung warranty/maintenance, form lịch/phân công/xác nhận có khóa và hủy/hoàn tất/no_show.
- Đạt: UI khách/layout, responsive, trạng thái form/chat; chủ module làm UI admin/staff chung layout.

Cả 5 người làm báo cáo, test, PR/review và tập demo; không dồn hết tài liệu cho Hoàng.
InquiryNote, other, audit UI, tự đổi lịch/mở lại và users control channel là P2.
Giữ an toàn HTTP/kênh/version/transaction trong P1; không tự thay chat bằng polling.
