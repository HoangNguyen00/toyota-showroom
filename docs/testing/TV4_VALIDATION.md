# TV4 — kết quả kiểm chứng local 24/10/2026

## Kiểm tra lại trên repository mới — 16/11/2026

Backend PR #1 `1438af2` được ghép main `ed06179` để xử lý sáu xung đột, giữ chat
và lịch TV5. Kiểm tra trên worktree riêng; không reset/seed database demo.

- composer check: 78 tests / 635 assertions (SQLite), Pint, syntax/encoding/inventory đạt.
- npm run build đạt, vẫn có 4 cảnh báo font baseline; Node regression: 4/4 đạt.
- MySQL 8.4.3 trên `toyota_showroom_tv4_test_20261116`: hai process gửi cùng UUID
  chỉ tạo một tin/job; send/reassign tuần tự; crash trước/sau commit giữ nguyên tử.
- Publication gate: revoker chờ tại SELECT/dispatch, job stale bị bỏ sau commit ở cả
  reassign/lock-sales/lock-customer; process chết nhả gate; mất session chặn publish.
- Category/car locking: sold/hidden/category-hidden bị chặn đến commit, request sau bị từ chối.
- Reverb thật, hai phiên HTTP/session/CSRF và hai socket: nhận tin trong 1303 ms;
  retry không phát trùng, payload UUID khác 409, reconnect bù lịch sử; kênh/job cũ
  không nhận tin sau đổi sales/khóa tài khoản. Ba scenario publication fence đều đạt.
- Môi trường HTTP thử dùng cấu hình test cho cả `.env` và `.env.tv4-testing`, cache
  file để limiter tồn tại giữa request. Chưa kiểm responsive, tải lớn hoặc failover.
  Review không thay phê duyệt của chủ module; PR vẫn cần review nhóm trước merge main.

Phần bên dưới giữ nguyên bằng chứng lịch sử tại repository cũ.

Nhánh `feature/inquiry-chat-websocket`, base
`983b12b22a27eb4eaaf3da3a701c7f78bc1bcdb9`. Mã TV4 đã commit và push tại
`a7bf4059764fc506f1ee3e1aa10a8ea2b61199d6` lên nhánh riêng này.
Chưa tạo Pull Request TV4, chưa merge vào main; không chứng nhận main/PR #11 đã tích hợp.

Đồng bộ tài liệu sau push: giữ nguyên các kết quả kiểm thử lịch sử bên dưới.
Không chạy lại MySQL/Reverb trong đợt đồng bộ này; không coi sửa tài liệu là bằng chứng test mới.

## Kết quả mới nhất — F01 được phê duyệt tích hợp H02, 24/10/2026

F01 **Fixed and verified trong phạm vi ADR 0006**: cùng MySQL primary, mọi đường
publish/revoke qua gate và session gate còn sở hữu. Không hứa tuyệt đối qua failover,
partition hoặc dữ liệu/request đã in-flight. Đã push nhánh riêng, chưa merge; H02 cần Hoàng review.
F02/F03 phía TV4/F04 giữ Fixed and verified; chưa nghiệm thu UI khách PR #11/toàn P1.

| Kiểm tra sau sửa F01 | Kết quả |
| --- | --- |
| Security gate trước sửa | Exit 1: reassign và H02 lock commit trước dispatch vẫn phát event stale |
| Security gate sau sửa | Exit 0: original injection bị chặn reentrant; revoke sau publication; replay stale job không phát |
| Full SQLite | 57 tests / 507 assertions pass, 8,85 giây |
| Full MySQL 8.0.46, DB riêng | 57 tests / 507 assertions pass, 65,39 giây |
| H01/H02/TV4 regression | Toàn bộ ca cũ giữ đạt; H02 9 tests, TV4 20 tests; thêm gate/no row transaction/outer transaction/rollback-idempotency |
| F02 crash/queue/UUID | Hai process MySQL cùng UUID và send/reassign; kill trước/sau commit; recovery một message/intent; pass |
| F03 locking | Sold/car hidden/category hidden UPDATE process độc lập bị chặn, commit xong mutation thành công và tạo mới bị từ chối; pass |
| F04 Node | 4 tests pass, chạy JS thật với DOM/transport giả; không là kiểm thử trực quan |
| MySQL publication gate | Reassign/lock sales/lock customer tại original final SELECT: revoker process chưa commit khi publisher giữ gate, commit sau gate nhả; stale job bị loại |
| Process/session loss | Kill process giữ gate rồi lấy lại được; kill CHỈ dedicated session của publisher fixture trước dispatch: fail closed, không event, message giữ nguyên |
| Reverb thật baseline | Hai HTTP session/socket nhận tin trong 642 ms; retry/catch-up/revoke queued intent/HTTP 419/422/429 đạt |
| Reverb thật F01 | Publisher PHP pause tại final SELECT, revoker process độc lập phải chờ; hai socket nhận tin; revoke commit rồi stale intent không phát; ca reassign/lock sales/lock customer đạt |
| Build/Pint | Build 70 modules đạt, 4 font warnings baseline; Pint --test đạt |

Phép thử SQLite cùng-process không tự chứng minh serialization MySQL: sau gate, nested
revocation bị từ chối thay vì mô phỏng hai request trong một stack. Đã bù bằng MySQL
process thực và Reverb thực tại cùng SQL boundary, không chỉ đổi fake để tests xanh.
MySQL harness còn kiểm message/job chưa commit không visible và process crash giữ đúng
intent. Worker retry/event rollback dùng injection tách khỏi các bằng chứng socket.

Gate không có TTL và không tự reconnect. PDO session loss trước dispatch được kiểm
fail-closed bằng test DB riêng; KHÔNG kiểm chứng mọi sự cố session mất sau ownership
check hoặc trong network. Client receipt có thể sau revocation commit nếu publication
attempt đã bắt đầu trước; HTTP timeout có acceptance chưa biết. Không hứa exactly-once.
Gate toàn DB gây head-of-line blocking, wait 10 giây rồi lỗi có thể retry, chưa load test.
Không có row transaction qua network và không gọi gate từ MessageService đang giữ row locks.

H02 source CHỈ import PublicationGate và bọc transaction cũ trong run(). Không đổi Policy,
role/active/version/idempotency/deadlock retries. Tests H02 đổi isolation sang
DatabaseMigrations + guard DB riêng vì gate yêu cầu outermost, không giảm assertions.
Đã restart worker demo để nạp job mới, không nhân đôi worker. Fingerprint cả 9 bảng
nghiệp vụ demo vẫn nguyên: users=6/inquiries=4/messages=4, các bảng còn lại=0.
Không sửa Auth khác UpdateUserStatus, Catalog, Appointment, Frontend Đạt, config,
schema/dependency/lockfile hoặc PR #11. Test 8001/8081 dừng sau kiểm chứng.

Nguồn tái hiện: scripts/testing/tv4-publication-boundary.php, tv4-publication-mysql.php,
tv4-mysql.php, tv4-catalog-locking.php, tv4-realtime.js, tests/JavaScript/inquiry-chat.test.js.
Inventory chính thức --write/--check: 268 controlled files đạt; composer check đạt
57 tests / 507 assertions, syntax/encoding/Pint đạt; git diff --check đạt. Bàn giao
nhánh riêng để review: READY WITH NON-BLOCKING ISSUES (font baseline, UI khách chưa
tích hợp, chưa load/failover test); không phải cho phép merge. Hoàng phải review H02.

## Lịch sử đợt sửa F02–F04 trước phê duyệt H02 — 24/10/2026

**NOT READY — FIX REQUIRED: F01 còn mở, cần phê duyệt phối hợp H02.**

| Finding | Kết quả mới | Trạng thái |
| --- | --- | --- |
| F01 | Security gate tái hiện reassign và H02-lock commit sau SELECT cuối nhưng trước dispatch; event vẫn nhắm version cũ | Open — coordination required |
| F02 | Message + jobs ghi cùng transaction/Connection; lỗi enqueue rollback cả message, intent, sales state; cấu hình driver/connection sai fail closed | Fixed and verified |
| F03 | Lock category shared → car shared đến commit; process UPDATE sold/car hidden/category hidden bị chặn; tạo sau mutation nhận validation error | Fixed and verified — phía TV4; TV3 review khi tích hợp |
| F04 | Response chậm không xóa draft mới; retry UUID/error/busy submit được kiểm bằng module JS thật với DOM/transport mô phỏng | Fixed and verified |

| Kiểm tra chạy lại | Kết quả |
| --- | --- |
| Full SQLite | 54 tests / 491 assertions pass, 8,28 giây |
| Full MySQL 8.0.46, DB test riêng | 54 tests / 491 assertions pass, 47,75 giây |
| TV4 riêng | 18 tests / 162 assertions; giữ 13 ca cũ, bổ sung 5 ca; assertions job đổi từ Queue fake sang rows thực |
| Node DOM regression | 4 tests pass; response chậm, mạng lỗi, UUID retry, draft đổi rồi quay lại và chống double submit |
| MySQL concurrency UUID/send+reassign | Hai PHP/PDO process; một message/created; version mới; pass |
| MySQL crash-before-commit | Kill process có transaction mở: connection/worker khác chưa thấy message/job; rollback rồi retry tạo đúng một cặp |
| MySQL crash-after-commit | Kill process sau commit: message/job còn đủ; retry không enqueue thêm |
| MySQL Catalog locking | Process UPDATE độc lập có lock_wait_timeout=1 thực sự bị 1205 cho sold/ẩn xe/ẩn dòng; sau Inquiry commit UPDATE thành công, tạo mới bị từ chối |
| Reverb/Queue/HTTP thật | Hai socket nhận cùng tin đã lưu trong 970 ms; retry 200/409 không phát thêm do HTTP retry; reconnect catch-up đạt |
| HTTP/CSRF/throttle/H02 | 419/201/422/429, assign/start/close; job chờ stale sau reassign/H02 lock bị loại; HTTP/channel cũ bị từ chối |
| Security publication boundary | `php scripts/testing/tv4-publication-boundary.php` **exit 1**, 2 ca F01 còn lỗi; KHÔNG tính là pass |
| Build | Pass, 70 modules; 4 cảnh báo font baseline còn nguyên |
| Pint | `--test` pass; chỉ format các file TV4 vừa sửa |

Chạy PHP full suite bằng SQLite memory hoặc MySQL test pattern, không demo. F02 dùng
database queue thật trong tests; injection sau INSERT jobs ném lỗi để chứng minh rollback;
worker retry test dùng pop/fire/release trên queue thật với lỗi dependency được inject.
Crash harness thật dừng PHP process tại marker trước/sau commit. Đây là các loại bằng
chứng khác nhau, không gọi fault injection/DOM simulation là kiểm thử socket hay trực quan.

F01 regression chính thức ở scripts/testing, luôn exit 1 khi còn vi phạm. Composer check
chạy PHPUnit thông thường, KHÔNG bao gồm security gate độc lập này; pass 54 tests không
chứng nhận F01 an toàn. Không sửa Broadcast job/Event/H02 để che khoảng đua; chưa có fence.
Đề xuất và file cần Hoàng phê duyệt ở TV4_INTEGRATION/ADR 0005. Chưa chạy socket race sát
dispatch với fence mới vì thiết kế chưa được duyệt/triển khai. Không hứa exactly-once delivery.

Không sửa H02/Auth/TV3/TV5/layout/CSS/lockfiles/schema; không merge PR #11. Không reset,
seed hoặc ghi nghiệp vụ demo; fingerprint cả 9 bảng nghiệp vụ giữ nguyên so với snapshot
trước sửa: users=6, inquiries=4, messages=4, các bảng nghiệp vụ khác=0. Test 8001/8081
đã dừng; giữ demo 8000/5173/8080 và worker. Secrets chỉ nằm trong env/fixture bị ignore.

Inventory chính thức --write/--check: 265 controlled files, đạt. Composer check chạy lại:
54 tests / 491 assertions pass (7,27 giây), encoding/syntax/Pint đạt; git diff --check đạt.
Các số liệu 49 tests/0 inquiry phía dưới là ảnh chụp TRƯỚC audit/demo, không phải hiện tại.

## Lịch sử kiểm chứng trước audit (không thay kết quả mới ở trên)

| Kiểm tra | Kết quả |
| --- | --- |
| Full suite trên MySQL 8.0.46 local test riêng | 49 tests / 459 assertions pass; lượt cuối 54,91 giây |
| TV4 PHPUnit | 13 test: public validation/throttle/car, scoped list/detail, phân công/trạng thái/note/close, message/UUID/history, rollback/job/channel/revocation |
| H01/H02/Auth frontend/catalog/home regression | Có trong full suite, pass |
| MySQL hai PHP/PDO process cùng UUID | Cùng một ID, chỉ một lần created, một row, pass |
| MySQL send đồng thời reassign | Hai process hoàn tất không deadlock; assignment mới/version=2, pass |
| HTTP login/session/CSRF thật | Customer/sales/admin bằng cookie jar độc lập trên DB test, pass |
| Reverb private hai socket thật | Hai phiên nhận message đã lưu; hai lượt đạt 1731 ms và 1409 ms với worker đang chạy |
| Public AJAX HTTP thật | Thiếu CSRF 419; guest/customer 201; sai phone 422; request thứ tư 429; admin phân công và sales start bằng HTTP thật |
| Retry/catch-up thật | Retry 200 cùng ID, khác payload 409, không phát lặp; reconnect subscribe trước GET after_id, lấy được tin offline |
| Queue thật sau reassign | Dừng worker test, lưu message/enqueue, reassign rồi chạy worker; job cũ không đến socket cũ |
| Queue thật sau H02 lock sales | Lưu/enqueue trước khóa, chạy worker sau khóa; không socket nào ở version cũ nhận message |
| Old-channel revoke | HTTP sales cũ 403, auth channel version cũ 403; message version mới chỉ đến hai socket mới |
| Đóng hồ sơ | close_result lưu, trạng thái closed; chat POST 403 và history giữ nguyên |
| Frontend build | Pass, 70 modules; bốn cảnh báo font static baseline vẫn còn |

TV4 riêng đạt 13 tests / 130 assertions, gồm ID chuỗi từ form HTML để bảo đảm
phân công lại cùng sales idempotent. Full SQLite qua composer check đạt
49 tests / 459 assertions, 11,40 giây. Inventory 261 source files, Pint, encoding,
PHP syntax, route:list (29 non-vendor routes), JS syntax và git diff --check đạt.
Frontend build đạt; helper loại public/hot khỏi source inventory.

Git QA lịch sử tại đợt trước audit (không phải trạng thái sau push):
23 tệp mới, 14 tệp tracked sửa, không xóa; staging rỗng. Main/origin/main/HEAD
vẫn base 983b12b, chỉ đang đứng nhánh TV4; không commit/push/merge/rebase/PR.
Không đổi app/Models, migrations, config/bootstrap, Auth/H02, CSS/layout hay lockfile.
Kiểm read-only DB demo cuối: 6 users, 0 inquiries, 0 messages. Test HTTP/Reverb
8001/8081 đã dừng; demo Laravel8000/Vite5173/Reverb8080 vẫn giữ listener ban đầu.
Không sử dụng test SQLite để kết luận locking MySQL.

## Cách tái hiện và giới hạn

Harness: `scripts/testing/tv4-mysql.php`, `scripts/testing/tv4-realtime.js`.
Hướng dẫn env/port và API: [TV4_INTEGRATION](../development/TV4_INTEGRATION.md).
MySQL test DB có tên `toyota_showroom_tv4_test_<timestamp>`, tài khoản test riêng;
đã kiểm host 127.0.0.1 và tên DB trước migration/fixtures. Demo `toyota_showroom`
không chạy migration/seed/reset trong nhiệm vụ này. Secrets chỉ trong file ignored
và process memory; root password nhập cửa sổ Windows, không xuất console/report.

13 bài PHPUnit TV4 dùng SQLite memory mặc định; khi chạy MySQL chỉ cho test DB
có tên pattern TV4 hoặc `toyota_showroom_test` theo CI hiện có, không cho DB demo.
MySQL full suite tạo/xóa bảng **chỉ trong DB test đã xác minh**, vì DatabaseMigrations/
RefreshDatabase là test isolation của Laravel. Harness concurrency không reset DB,
giữ fixture riêng mỗi lượt. File fixture password test ở storage/app bị ignore.

Reverb test không mock: RFC6455 client gửi đúng local Origin, handshake và subscribe
bằng authorization lấy từ hai phiên HTTP khác nhau; Queue database thật; dùng port
8001/8081. Worker/HTTP/Reverb của test tách khỏi demo port 8000/8080.
Unit Queue/Event fake dùng để kiểm rollback/dispatch và các nhánh lỗi, được tách
khỏi bằng chứng socket/queue thật ở trên. Số 1731 ms là lượt đo local, không là SLA.
Một lượt dùng worker khởi động lạnh sau từng request đo 2236 ms, chưa đạt mục tiêu;
đã chạy lại đúng setup worker thường trực theo README và đạt 1731 ms.

Không có khả năng thu hồi message đã giao hoặc request phát đã bắt đầu trước
commit thu hồi. Không giữ transaction qua network; job kiểm current version/quyền
và kiểm version lại trước phát. Các test xác minh tin/job chờ sau commit reassign/lock,
không chứng minh loại bỏ mọi khoảng đua ở ranh giới DB/network trên hệ thống phân tán.

Chưa tích hợp E2E UI khách trong PR #11 (vẫn draft/open khi kiểm).
Màn TV4 dùng layout account và đã render trong tests; chưa review trực quan
responsive/trình duyệt trên mọi kích thước. Public AJAX endpoint đã test nhưng
CTA/form preview chung chưa nối. Frontend cần review trạng thái/retry/catch-up theo contract.
Không nghiệm thu Catalog/Appointment, thiết bị thật hoặc production qua test này.
