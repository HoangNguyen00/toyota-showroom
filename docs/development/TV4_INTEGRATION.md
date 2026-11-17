# TV4 — hợp đồng tích hợp tư vấn/chat P1

Ngày 24/10/2026; nhánh `feature/inquiry-chat-websocket`, base
`983b12b22a27eb4eaaf3da3a701c7f78bc1bcdb9`. Mã TV4 đã commit và push tại
`a7bf4059764fc506f1ee3e1aa10a8ea2b61199d6` lên nhánh riêng này.
Chưa tạo Pull Request TV4, chưa merge vào main; vẫn cần review trước merge.
PR [#11](https://github.com/grumhu124/toyota-showroom/pull/11) được kiểm qua GitHub:
draft/open, chưa merge; head `2d63e337233ed38b9aaf8c7550a3db09559b7420`.
Chỉ đọc tài liệu bàn giao của PR, không merge/cherry-pick/sao chép UI khách.

## Tiếp nhận AJAX

`POST /tu-van` (`inquiries.store`), web + CSRF + `throttle:public-form` hiện có,
3 request/10 phút/IP/endpoint. Guest/customer active; sales/admin nhận 403.

```json
{"name":"Khách demo","phone":"0900000000","email":"demo@example.test","content":"Tôi cần tư vấn Toyota","car_id":null}
```

`name` <=100, phone theo SRS, email tùy chọn <=255, content 10–2000.
Tên field public thống nhất `name/phone/email`; service ánh xạ sang `contact_*`.
`customer_id` lấy từ session, `source=form`, `status=new`; payload quyền bị bỏ qua.
`car_id` phải là ID MySQL, xe/category public và available/reserved. Không chuyển ID
fixture /xe thành ID thật; chưa có public scope TV3 trên base đã revert catalog nên
điều kiện này được kiểm trong InquiryService, chờ TV3 hợp nhất scope khi tích hợp.

Thành công 201: `{"id":123,"message":"Đã tiếp nhận yêu cầu tư vấn."}`.
422 có `message/errors`, 429 throttle, 403 sai role. Frontend chỉ báo thành công sau
201, disable nút khi gửi và render errors bằng text. Public form UI chờ Frontend;
TV4 cung cấp endpoint đã test, không thay CTA preview chung.

## Danh sách, chi tiết, xử lý

| Method/URL | Route name | Quyền |
| --- | --- | --- |
| GET /yeu-cau, /yeu-cau/{inquiry} | customer.inquiries.index/show | Customer own |
| GET /staff/inquiries, /staff/inquiries/{inquiry} | staff.inquiries.index/show | Sales assigned hoặc admin |
| GET /admin/inquiries, /admin/inquiries/{inquiry} | admin.inquiries.index/show | Admin |
| PATCH /admin/inquiries/{inquiry}/assign | admin.inquiries.assign | Admin; `{assigned_to: salesId}` |
| PATCH /staff/inquiries/{inquiry}/start,note,close | staff.inquiries.start/note/close | Sales assigned/admin |
| PATCH /admin/inquiries/{inquiry}/start,note,close | admin.inquiries.start/note/close | Admin |

`note` nhận `{internal_note: string|null}` <=10000 (giới hạn ứng dụng cho một ô ghi chú).
`close` nhận `close_result` thuộc InquiryCloseResult: da_tu_van,
khach_khong_tiep_tuc, khach_giao_dich_truc_tiep, khong_lien_he_duoc.
Chỉ assigned → in_progress (start hoặc sales gửi tin đầu), in_progress → closed.
Không đóng trực tiếp new/assigned: bắt đầu xử lý trước; closed chặn mọi mutation.
Reassign giữ in_progress nếu đang xử lý, tăng version khi đổi người; gửi lại cùng
sales không tăng version. Không có DELETE/reopen/sửa owner.

GET list hỗ trợ `?status=new|assigned|in_progress|closed&page=1`, 15/trang,
database-scoped theo actor trước paginate. Accept JSON trả `{data,current_page,last_page}`.
Detail/mutation JSON trả allowlist: id/content/status/close_result/car(id,name)/
assigned_staff(id,name)/access_version/channel/created_at. Staff/admin được thêm
contact_name/contact_phone/contact_email/internal_note; customer không có các trường đó.
Web quản lý dùng Blade/layout account sẵn có, redirect + errors/flash khi mutation.

## HTTP chat và realtime

`GET/POST /yeu-cau/{inquiry}/tin-nhan` (`inquiries.messages.index/store`) nằm trong
auth/active group của routes/inquiries.php, không nằm customer-only group.
Owner/assigned/admin active, inquiry có customer_id. Guest inquiry không có chat.
New/closed chỉ history; POST chỉ assigned/in_progress. 401 JSON guest, 403 sai quyền
hoặc trạng thái, 404 ID không tồn tại, 422 validation, 409 UUID khác payload.

```json
{"content":"Xin chào","client_message_id":"89f2bf5e-2ad3-437a-b441-078fbfa8e1bb"}
```

POST content 1–1000; sender từ session. Tạo mới 201; retry cùng UUID/inquiry/content
200 cùng message. UUID khác payload 409; không enqueue/phát lại khi retry.
Response và event có cùng schema:

```json
{"id":45,"inquiry_id":123,"sender":{"id":7,"name":"Sales demo"},"content":"Xin chào","created_at":"2026-10-24T18:00:00+07:00"}
```

GET mặc định 50 tin mới nhất, trả thứ tự tăng dần. `before_id` lấy trang cũ;
`after_id` lấy tin mới tăng dần từ cursor, tối đa 50; không dùng hai cursor cùng lúc.
`limit` 1–50. Response:

```json
{"data":[],"has_more":false,"next_after_id":45,"next_before_id":12,"access_version":2,"channel":"inquiry.123.v2"}
```

Mọi cursor luôn gắn query inquiry đã authorize; không thể dùng cursor để đọc thread khác.
Lấy detail để biết version → `Echo.private(channel).listen('.message.created', ...)`
→ chờ `.subscribed()` → catch-up `after_id=lastSyncedId` cho đến has_more=false.
Không lấy ID của event mới đến làm cursor catch-up trước khi đồng bộ phần bị lỡ.
Dedupe theo message.id; retry giữ UUID và nội dung ban đầu. UUID mới khi nội dung đổi.
Chỉ history/event chứa tin đã lưu; gửi lỗi không xóa draft hay giả sent.

JS chức năng tối thiểu ở `resources/js/modules/chat/inquiry-chat.js` chỉ mount vào
`[data-inquiry-chat]` trên view TV4. Dùng textContent chống XSS, hỗ trợ history cũ,
reconnect/catch-up, sending/saved/failed/disconnected và UUID retry. Không polling.
Chưa tích hợp markup thread/composer của PR #11, chưa nhận đã nghiệm thu UI khách.

## Transaction, job và thu hồi quyền

InquiryLock lấy snapshot customer/assigned, khóa users liên quan theo ID tăng dần,
rồi lock inquiry và kiểm lại tập user. Tập đổi thì rollback/retry cả transaction,
không bổ sung lock ngược thứ tự. Recheck actor/role/active/Policy trong transaction.
Gửi tin khóa sender nên hai request cùng sender/UUID được tuần tự hóa, kèm unique
DB hiện có làm lớp bảo vệ cuối. Không thay migration/schema. H02 chỉ bọc transaction
bằng PublicationGate theo phê duyệt F01; Hoàng cần review diff trước merge.

Sau khắc phục F02, MessageService ghi BroadcastInquiryMessage chỉ với messageId/version
vào database queue `database` trong CÙNG transaction và CÙNG Connection với message.
Job dùng beforeCommit để không bị cấu hình after_commit trì hoãn INSERT; đây là lưu
ý định phát, KHÔNG phải phát WebSocket trước commit. Connection khác/driver không
phải DatabaseQueue bị từ chối và rollback. Không hỗ trợ tự chuyển sang Redis/sync.
Rollback/enqueue lỗi hủy cả message và intent; process chết trước commit không để
row mồ côi; chết sau commit vẫn có intent. Retry UUID thông thường không thêm job.
Worker kết nối khác chỉ nhìn thấy job sau commit, rồi kiểm quyền/version trước phát.
Delivery là at-least-once: crash sau publish trước acknowledge có thể giao lại cùng ID;
client phải dedupe. Không hứa exactly-once hoặc tự phát lại mọi message lịch sử.
Worker khóa/đọc lại quyền/version/participants, bỏ job version cũ,
sender mất quyền, participant bị khóa hoặc hồ sơ closed. Sau transaction kiểm version
lần nữa ngay trước phát event ShouldBroadcastNow lên private inquiry.{id}.v{version}.
Không giữ transaction trong lúc gọi Reverb. Kênh auth kiểm active/role/owner/assigned/
version hiện tại. H02 giữ nguyên logic tăng version khi khóa customer/sales.

F01 dùng PublicationGate CHUNG theo database cho publisher, InquiryService::update và
H02 handle. MySQL advisory lock trên session PDO riêng, không TTL/automatic reconnect;
SQLite flock chỉ phục vụ test. Gate lấy trước transaction, row lock vẫn users → inquiry.
Publisher nhả transaction trước network nhưng giữ gate qua ShouldBroadcastNow; kiểm
ownership bằng chính session trước dispatch. Revoker phải chờ publisher hoàn tất/throw;
nếu revocation đã commit trước publisher thì job stale bị loại. Timeout 10 giây/error
propagate; không lấy gate khi đã có outer transaction hoặc gọi reentrant. MessageService
vẫn dùng row locks, không lấy gate. Không đổi API/event/channel hoặc thêm P2.

Gate toàn DB tuần tự cả publication/mutation, phù hợp showroom demo, chưa benchmark
tải lớn. Phạm vi single-primary/cooperating service entrypoints/session gate còn sở hữu.
Không hứa tuyệt đối qua failover, partition hoặc mất session đúng lúc network. Request
đã in-flight/HTTP timeout có acceptance chưa biết, client có thể nhận sau commit thu hồi;
không thu lại tin đã giao. Xem ADR 0006. Security gate SQLite từ exit 1 trước sửa sang
exit 0; MySQL hai process và Reverb thật bổ sung bằng chứng, không chỉ mock pass.

Đổi quyền không xóa dữ liệu đã giao cho socket. Khi quyền đổi, người còn quyền tải lại trang để lấy version mới;
users control channel/tự chuyển kênh là P2.

Queue dùng database worker theo README. Worker phải restart sau khi cập nhật mã
(`php artisan queue:restart`); không giả nhận worker cũ đã nạp job class mới.
Tham khảo [Laravel after-commit jobs](https://laravel.com/framework/docs/12.x/queues#jobs-and-database-transactions).

### F03/F04 sau audit

TV4 create khóa user (nếu có) → category shared → car shared, dùng locking reads và
giữ đến commit Inquiry; không network trong transaction. category_id ban đầu chỉ là
snapshot chọn hàng khóa; nếu car đã chuyển category thì từ chối 422, không lấy thêm
khóa category ngược thứ tự. Ordinary UPDATE sold/visible bị InnoDB chặn trong đoạn này.
Không sửa CRUD Catalog. TV3 cần review thứ tự category → car cho thao tác nhiều hàng/
chuyển category và hợp nhất public scope khi tích hợp; chưa chứng nhận CRUD tương lai.
Điểm tuần tự hóa: tạo Inquiry commit trước thì Catalog được đổi sau và giữ lịch sử;
Catalog đã commit sold/hidden trước locking read thì không tạo Inquiry mới.

JS TV4 chỉ xóa composer khi response thành công, revision input và giá trị raw vẫn
khớp lúc gửi. Bản nháp mới/đã sửa rồi quay lại được giữ; failed request giữ draft/UUID
cho retry cùng nội dung. Không đổi layout/CSS/UI customer của Đạt.

## Demo và review

Danh sách bàn giao ban đầu dưới đây được giữ để truy vết; các tệp đã nằm trong
commit chức năng đã push nêu trên, không còn là danh sách chờ stage. Các bổ sung
sau audit được ghi trong ADR 0005/0006 và TV4_VALIDATION.

Các tệp mới trong đợt triển khai ban đầu:

- app/Enums/InquiryCloseResult.php; app/Events/InquiryMessageCreated.php;
  app/Jobs/BroadcastInquiryMessage.php.
- app/Http/Controllers/Inquiry/{InquiryController,MessageController}.php;
  app/Http/Requests/Inquiry/{StoreInquiryRequest,ManageInquiryRequest,StoreMessageRequest}.php.
- app/Policies/{InquiryPolicy,MessagePolicy}.php;
  app/Services/Inquiry/{InquiryLock,InquiryService,MessageData,MessageService}.php.
- routes/inquiries.php; resources/views/staff/inquiries/{index,show}.blade.php;
  resources/js/modules/chat/inquiry-chat.js.
- tests/Feature/Inquiry/InquiryTest.php; scripts/testing/{tv4-mysql.php,tv4-realtime.js};
  docs/development/TV4_INTEGRATION.md; docs/testing/TV4_VALIDATION.md.

Tệp tracked sửa: routes/{web,customer,staff,admin,channels}.php; resources/js/app.js;
scripts/project-files.php; tests/Unit/InventoryHashTest.php; docs/architecture/
{FEATURE_STATUS,CONTRACTS,AUTHORIZATION_CONTRACT}.md và INVENTORY.json;
docs/testing/ACCEPTANCE.md; docs/changes/CHANGELOG.md. Không có tệp bị xóa.
Commit message đã dùng: `feat(inquiry): implement P1 inquiry workflow and private Reverb chat`.
Review diff commit đã push; không stage secrets/artifacts và không coi push là phê duyệt merge.

1. Guest/customer POST /tu-van (frontend POST phải có CSRF); giữ ID đã nhận.
2. Admin mở /admin/inquiries/{id}, giao sales active.
3. Sales mở /staff/inquiries/{id}, bắt đầu, ghi internal_note.
4. Với inquiry customer, mở phiên customer /yeu-cau/{id} và sales, gửi hai chiều.
5. Admin chuyển sales, tải lại phiên còn quyền; sales cũ bị chặn HTTP/channel và
   không nhận tin mới trên socket cũ.
6. Admin/sales bắt đầu rồi đóng với close_result; history còn, POST chat bị 403.

Luồng HTTP/MySQL/Reverb đã chạy bằng harness độc lập; giao diện staff được render
qua tests. Không nhận đã review responsive/thiết kế hoặc tích hợp E2E PR #11.
Hoàng review khóa/quyền/job/shared route; Đạt review schema/cursor/event và nối UI;
TV3 review public scope sau merge. Appointment không thay assignment theo Inquiry.

## Chạy lại kiểm thử riêng

Inventory helper loại trừ public/hot giống build artifact: đây là marker Vite local
đã bị Git ignore, không phải source cần hash; có regression trong InventoryHashTest.

SQLite nhanh: `php artisan test --filter=InquiryTest`. Full: `composer check`.
MySQL: tạo DB local riêng dạng `toyota_showroom_tv4_test_<digits>` và user chỉ được
quyền DB đó; copy `.env` sang `.env.tv4-testing`, thay DB credentials, APP_ENV=testing,
APP_URL=http://127.0.0.1:8001, REVERB_PORT/REVERB_SERVER_PORT=8081. File này bị ignore.
Không dùng credentials demo để chạy migration/fresh trên demo.

```powershell
php scripts/testing/tv4-mysql.php
php artisan serve --env=tv4-testing --no-reload --host=127.0.0.1 --port=8001
php artisan reverb:start --env=tv4-testing --host=127.0.0.1 --port=8081
node scripts/testing/tv4-realtime.js
php scripts/testing/tv4-catalog-locking.php
node --test tests/JavaScript/inquiry-chat.test.js
```

Security gate riêng: `php scripts/testing/tv4-publication-boundary.php` (nay exit 0).
MySQL process/session-loss: `php scripts/testing/tv4-publication-mysql.php`, sau MySQL
harness tạo schema/fixture. Realtime harness thêm publisher pause tại SELECT cuối,
revoker PHP process riêng phải chờ; hai socket thật nhận và stale intent bị loại sau commit.
Harness MySQL còn thử kill process trước/sau commit, kiểm job chưa commit không visible
cho worker khác và retry UUID giữ đúng một message/intent. Catalog harness dùng process
UPDATE độc lập, timeout lock thật cho sold/car hidden/category hidden, rồi kiểm tạo sau
mutation nhận 422. Node DOM tests không phải kiểm thử trực quan trình duyệt.

Harness MySQL refuse ngoài DB test local, migrate không fresh, tạo fixture mới mỗi
lần và giữ dữ liệu test. Harness Node tự chạy worker test, dừng worker của chính nó
để kiểm job chờ; không chạy thêm worker test cùng lúc. Chạy lại MySQL harness trước
Node để có fixture mới (Node thay assignment/khóa user/đóng inquiry fixture).
Fixture chứa password test nằm `storage/app/tv4-integration-fixture.json` bị ignore,
không in ra console. Test server Windows cần --no-reload để env được truyền cho PHP con.
Không dùng Laravel app demo port 8000 để chạy harness này.
