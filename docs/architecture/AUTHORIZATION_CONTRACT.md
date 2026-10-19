# H03 — Hợp đồng phân quyền theo bản ghi

17/10/2026, đề xuất cho owner review theo SRS 1.4/CONTRACTS.
Không thêm endpoint/Policy giả hoàn thành. Đã có UserPolicy H02 và Car/CarCategoryPolicy (TV3,
chờ review). InquiryPolicy, AppointmentPolicy, kiểm kênh chat vẫn chờ module owner.

## 1. Ba lớp cần kiểm

1. auth/active/role kiểm tài khoản và nhóm route.
2. Policy/scope kiểm bản ghi thuộc ai, giao cho ai; list cũng phải scope.
3. Service kiểm trạng thái/giờ/xe/dịch vụ/transaction. Admin không bỏ qua các
   invariant này. Không thêm Gate::before trả true cho admin để vượt mọi luật.
ID, mã yêu cầu và số điện thoại không cấp quyền. CSRF bắt buộc cho web mutation.

## 2. Ma trận quyền P1

| Hành động | Guest | Customer active | Sales active | Admin active |
| --- | --- | --- | --- | --- |
| GET xe/dịch vụ public | Có | Có | Có | Có, vẫn chỉ public tại URL public |
| Gửi form tư vấn/lịch public | Có | Có, owner từ session | Không dùng để giả khách | Không dùng để giả khách |
| List/detail inquiry cá nhân | Không | Chỉ customer_id = self | Qua staff route, assigned_to = self | Qua admin/staff route |
| List/detail lịch cá nhân | Không | Chỉ customer_id = self | Qua staff route, assigned_to = self | Qua admin/staff route |
| Đọc lịch sử chat | Không | Own inquiry có customer_id | Inquiry được giao, có customer_id | Inquiry có customer_id |
| Gửi chat | Không | Own + assigned/in_progress | Assigned + assigned/in_progress | Assigned/in_progress, customer_id không null |
| Ghi internal_note/đóng inquiry | Không | Không | Chỉ inquiry được giao, chuyển trạng thái hợp lệ | Mọi inquiry, chuyển trạng thái hợp lệ |
| Phân công/chuyển sales | Không | Không | Không | Sales đích active, transaction/version |
| Hủy lịch khách | Không | Own pending/confirmed trước giờ | Theo staff action, lịch được giao và chưa final | Theo admin action, chưa final |
| Confirm/complete/no_show | Không | Không | Assigned, đúng chuyển trạng thái và giờ | Đúng trạng thái/giờ, có sales active khi confirm |
| CRUD dòng/xe/ảnh/dịch vụ | Không | Không | Không | Có, FK/history/ảnh/public constraints vẫn áp dụng |

Catalog đã triển khai (TV3) theo đúng dòng CRUD này: route admin.* + CarPolicy/
CarCategoryPolicy từng bản ghi, brand Toyota và visibility do server kiểm. Xem TV3_TASKS.
| List/create sales/khóa user | Không | Không | Không | UserPolicy H02; không tạo/khóa admin hoặc xóa user |

“Mọi inquiry/lịch” chỉ trong màn quản trị được bảo vệ, không cho response public
serialize toàn model. Guest inquiry không có chat dù staff/admin đang xử lý;
liên hệ bằng phone, ghi chú nội bộ chỉ staff/admin đọc.

## 3. Điểm giao route chat — cần TV4 giữ đúng

routes/customer.php có role:customer nên không đặt shared chat HTTP trong file
này: sales/admin sẽ bị role middleware chặn trước Policy.
TV4 đặt GET/POST /yeu-cau/{inquiry}/tin-nhan trong auth group của routes/inquiries.php
(web/auth/active, cho customer/sales/admin) rồi dùng Inquiry/Message Policy.
File đó được require một lần từ web.php trong cùng PR triển khai.
Customer list/detail /yeu-cau ở customer.php; staff/admin đọc hồ sơ ở route riêng.
Không tạo route trùng và không bỏ role bảo vệ cả nhóm customer để mở chat.
Private channel kiểm active/current version/owner/assigned; không tự suy quyền từ URL.

## 4. Quyền và dữ liệu không lấy từ payload

- customer_id, sender_id, user_id tác giả từ phiên; role/status/version/assigned_to
  chỉ set bằng action tin cậy, không mass assign request all().
- Query message luôn bắt đầu từ inquiry đã kiểm quyền; after_id chỉ là cursor.
- UUID retry kiểm quyền trước rồi so inquiry/content; khác payload 409.
- appointment.inquiry_id nếu được gắn phải cùng customer và car phù hợp; không tự
  claim guest inquiry chỉ vì phone trùng. Hai assigned_to là hai phân công riêng.
- Customer response không có internal_note; gửi allowlist thay toàn model.
- Public xe ẩn/dòng ẩn 404 kể cả admin đang xem public; admin edit dùng route admin.
- Không tồn tại 404; bản ghi tồn tại nhưng sai quyền 403; guest cần auth 401 JSON/
  redirect login; validation 422/redirect; 429 throttle; không tiết lộ lỗi DB.

## 5. Thu hồi quyền và trạng thái

Khóa user/phân công lại tăng inquiry.access_version trong transaction.
HTTP gửi và broadcast kiểm quyền/current version mới; không phát tin vào kênh cũ.
Job giữ version ban đầu phải kiểm lại: version không còn đúng thì không phát event
cũ; lịch sử tin vẫn trong DB để người còn quyền tải lại sau khi vào kênh mới.
Không tuyên bố xóa dữ liệu đã được gửi hoặc chỉ đóng UI là đã thu hồi socket.
Lock user → request sau thu hồi session hiện đã có; phía socket/job chưa có.
Chat new/closed chỉ đọc; không tự thêm reopen P2.
Lịch final không sửa, no_show/completed sau giờ; confirm kiểm slot/xe/service.

## 6. Kịch bản owner cần bổ sung khi có mã thật

| ID | Ca cần thử | Owner | Hiện trạng |
| --- | --- | --- | --- |
| H03-CAT | Đoán URL/POST ID xe hoặc dòng ẩn; xóa xe đã tham chiếu | TV3 | Có test: xe/dòng ẩn 404 (cả admin), xóa xe có inquiry trả validation, xóa dòng còn xe bị Policy+FK chặn; chờ review |
| H03-INQ | Customer A list/detail hồ sơ B; sales A đọc/sửa hồ sơ của B | TV4 | Chưa có endpoint |
| H03-CHAT | Customer/sales/admin dùng shared chat route; guest inquiry không chat | TV4 | Chưa có endpoint |
| H03-REVOKE | Hai session + job chờ khi khóa user/chuyển sales; kênh cũ không nhận tin mới | Hoàng + TV4 | HTTP/version H01/H02 có test; socket chưa có |
| H03-UUID | Retry sau mất quyền; UUID khác inquiry/nội dung không lộ tin | TV4 | Chưa triển khai |
| H03-APT | Customer hủy lịch B; sales thao tác lịch khác người; admin không vượt final/giờ | TV5 | Chưa có endpoint |
| H03-LINK | Gắn inquiry_id khác customer hoặc guest chỉ vì trùng phone | TV4 + TV5 | Chưa có endpoint |
| H03-STATE | Form/state/role bị gửi thêm từ DevTools vẫn bị server chặn | Từng owner | H01/H02 đã có test riêng, module khác chưa |

List bị lọt bản ghi là lỗi quyền ngay cả khi trang detail đã chặn.
Khi có mã, owner ghi test/PR/bằng chứng vào ACCEPTANCE; ma trận này không tự là
test pass. Scope/Policy chưa áp thì deny-by-default, không mở channel demo để qua UI.
