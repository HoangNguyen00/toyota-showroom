# Quy ước làm song song và review chéo

Chốt kỹ thuật 16/10/2026 theo SRS 1.4; không thêm phạm vi P2.
Đây là quy ước nội bộ. Tên người, deadline và GitHub username chưa có thì không đoán.

## 1. Nền chung và nhánh

Main là nền tích hợp. PR #1 tài liệu/nền, PR #2 H01 dựa trên #1.
Thứ tự: người khác review #1 → merge #1 → retarget #2 về main, cập nhật và chạy CI
→ người khác review #2 → merge #2. Không merge Auth ngược vào nhánh tài liệu.
Sau đó mọi thành viên tạo nhánh tính năng từ main mới nhất.
Trong lúc chờ, các bạn thiết kế/viết module trên nhánh riêng với schema đã thống nhất;
muốn dùng Auth chưa merge phải ghi phụ thuộc #2, không tự chép controller/Auth khác.
Chưa có CODEOWNERS/branch protection được xác nhận; review là quy tắc nhóm,
không tuyên bố GitHub đã cưỡng chế.

## 2. Trách nhiệm và những phần làm ngay

| Người | Làm độc lập | Bàn giao/kiểm tra chéo |
| --- | --- | --- |
| Hoàng repo/auth | H02 user admin, Policy/user session, setup/CI | Với TV4: khóa tài khoản/version; review thay đổi shared |
| Đạt frontend | Layout/components/style, màn khách, form states | Nhận contract từ từng owner; review UI mobile/admin/staff |
| Võ Nguyễn Thành Đạt TV3 | Catalog/CRUD xe/dòng/ảnh, search/filter/pagination | Với TV5: chọn xe và điều kiện availability; với Đạt: data view |
| Nguyễn Huy Hoàng TV4 | Inquiry/phân công/trạng thái/chat/private channel | Với Hoàng: quyền/khóa user; với Đạt: gửi/nhận/lỗi chat |
| Phan Võ Chí Bảo TV5 | Dịch vụ, lịch, confirm/cancel/complete/no_show | Với TV3: xe public; với TV4: liên kết inquiry tùy chọn |

Mỗi module owner làm controller/request/policy/service/test và màn staff/admin
trên layout chung. Không đợi Đạt viết toàn bộ màn quản trị.
Đạt không viết backend thứ hai; mock chỉ phục vụ phát triển, không còn trong demo.
Mỗi người dùng DB riêng và test fixture riêng, không đợi CRUD của người khác.
Không chạy seed/reset vào DB của thành viên khác.
Hoàng review shared/quyền, không là người duy nhất được review mọi PR.
Một người khác review; phần có tác động nhiều module cần người bị ảnh hưởng xem thêm.

## 3. Vị trí file và tránh sửa đè

| Vị trí | Chủ phối hợp | Cách sửa |
| --- | --- | --- |
| routes/web.php, bootstrap/app.php | Hoàng | Auth/root/đăng ký route chung; đổi cấu hình chỉ một PR nhỏ |
| routes/customer.php | TV4 + TV5 | TV4 thêm list/detail /yeu-cau; TV5 thêm /lich-hen; không /customer; shared chat ở auth group riêng routes/inquiries.php |
| routes/staff.php | TV4 + TV5 | /staff/inquiries và /staff/appointments, không dùng tên route chung |
| routes/admin.php | Hoàng + TV3/4/5 | /admin/users, car-categories/cars, inquiries, services/appointments theo module |
| Route public mới | TV3/4/5 + Hoàng | Mỗi module thêm file routes/catalog.php, inquiries.php, appointments.php khi có code; require một lần từ web.php trong cùng PR |
| resources/views/layouts, components; CSS chung | Đạt | Owner dùng layout/component; đề xuất đổi API component trong PR |
| Model/FK/state/shared config/lockfile | Chủ module + Hoàng | Không tự đổi tên field/enum; nêu ảnh hưởng và review người dùng chung |
| DatabaseSeeder | Hoàng | Owner thêm seeder riêng; tích hợp thứ tự user → catalog/services → hồ sơ/lịch |
| CHANGELOG, FEATURE_STATUS, INVENTORY | Tất cả | Update phần mình, xử lý conflict bằng nội dung thật rồi regenerate inventory |

Không tạo file route rỗng/endpoint giả chỉ để nói đã có chức năng.
Tên route có namespace: customer.inquiries.*, customer.appointments.*,
staff.inquiries.*, staff.appointments.*, admin.users.*, admin.cars.*,
admin.car-categories.*, admin.services.*, admin.appointments.*.
URL công khai theo SRS/CONTRACTS, không đổi sang route tiếng Anh tùy ý.
Auth giữ name login/register/account/logout như H01.

## 4. Bàn giao dữ liệu và quyền

- Cars là từng chiếc mã riêng; category là dòng Toyota, brand cố định Toyota.
  Có slug trong DB nhưng chi tiết dùng ID; không tự bỏ cột.
- Dữ liệu form hiển thị giá VND như chuỗi số nguyên, không dùng float để tính tiền.
- Chỉ public khi xe và category cùng visible; dùng query scope chung do TV3 cung cấp.
  TV4/TV5 dùng lại scope/điều kiện, không tự chép luật khác.
- Customer/actor/sender từ session; admin mới gán sales active. Model fillable không
  thay FormRequest/Policy: không dùng all() để create/update inquiry/appointment.
- Guest không được nối hồ sơ bằng phone. Internal_note và thông tin khách khác
  không đi vào response/event public. Owner trả allowlist, không serialize model nguyên.
- Đặt lịch không tạo inquiry và không giữ xe. Inquiry_id tùy chọn, nếu có phải đúng
  customer và phù hợp car; không thay assigned_to lịch khi đổi người inquiry một cách ngầm định.
- Warranty chỉ giới thiệu/liên hệ; allow_booking P1 chỉ bật cho maintenance.
- State cuối không chỉnh ngược: inquiry closed không gửi chat; lịch final không sửa.
- Login/session database 120 phút theo env mẫu; không JWT/Sanctum/Passport.
  Request Auth dùng CSRF; broadcast private channel dùng phiên hiện có.

## 5. Phụ thuộc quyền và khóa dữ liệu

Sau sửa F01 theo phê duyệt 24/10: publisher, InquiryService::update và H02
UpdateUserStatus::handle lấy PublicationGate theo database TRƯỚC transaction; caller
phải outermost, không reentrant. Bên trong giữ nguyên users ID tăng dần → inquiry.
Publisher commit/nhả row locks rồi mới network, giữ advisory gate đến khi lời gọi
phát kết thúc/throw. MessageService không lấy gate và không gọi network. Không có
đường đang giữ row locks rồi chờ gate. Xem ADR 0006 về timeout, session loss, giới hạn
single-primary/in-flight và yêu cầu Hoàng review H02; không đồng nhất gate với exactly-once.

Hoàng/TV4 thống nhất trước khi viết H02/phân công:
lock users liên quan theo ID tăng dần → lock inquiry; đọc lại active/role/quyền
và assignment. Thay assigned_to/status/version trong transaction, broadcast sau commit.
Nếu tập user thay đổi trong lúc chờ, rollback/retry với tập khóa đúng, không khóa bổ sung
ngược thứ tự. MessageService dùng cùng quy tắc khi phối hợp với lock user.
TV5: lock appointment → users liên quan theo ID; không khóa inquiry rồi quay lại user.
Không có đường đang giữ user/inquiry rồi khóa appointment; nghiệp vụ không tự đồng bộ
assignment hai module. Nếu cần đổi thứ tự phải ADR/test MySQL trước.
Không giữ transaction trong lúc đợi khách thao tác hoặc chờ WebSocket/network.
Không retry toàn bộ thao tác có tác dụng ngoài DB mà chưa chống phát lặp.

Đây là hợp đồng để triển khai, chưa có các service này trong baseline.
TV4 bắt buộc test khóa user/chuyển sales với job chờ/kết nối cũ; không coi
middleware HTTP hay đóng giao diện là đã thu hồi tin WebSocket.
TV5 test hai kết nối MySQL; CI unit/feature tuần tự không chứng minh race đã đúng.

## 6. Nhịp làm liên tục

Xem [gói review PR](PR_REVIEW_GUIDE.md) và [hợp đồng quyền H03](../architecture/AUTHORIZATION_CONTRACT.md).
Hợp đồng đã soạn không thay review/đồng ý của owner hoặc nghiệm thu endpoint chưa có.

1. Một task/PR nhỏ có kết quả chạy được: form tư vấn; CRUD xe; xác nhận lịch...
2. Ghi module, dữ liệu/route dùng chung, phụ thuộc, điều kiện xong và người review.
3. Chốt contract trước; mock fixture theo contract để người khác làm đồng thời.
4. Xong lát cắt thì test/PR/review sớm, không chờ cả module lớn.
5. Cập nhật từ main trước merge, xử lý conflict có ý thức, regenerate inventory.
6. Sau merge gắn màn thật với backend, bỏ mock, demo một luồng và một lỗi/sai quyền.
7. Nếu contract đổi, ghi trong PR và báo chủ module bị ảnh hưởng; không chỉ đổi code.
