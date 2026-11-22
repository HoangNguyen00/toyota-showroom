# Customer workspace integration — 28/10/2026

19/11/2026: PR #4 đồng bộ main 96aa13c sau khi TV4/TV5 đã gộp. Giữ phân trang lịch;
account sales/admin có liên kết tư vấn/chat và quản lý xe theo role. Nút gửi chat mobile
có aria-label. Browser 390px kiểm account/chat/lịch và chat customer/sales hai chiều thật;
không có tràn ngang trang ở các màn này. Chưa kiểm điện thoại thật hoặc toàn bộ P1.

Nhánh feature/customer-realtime-integration ghép main ed06179/lịch TV5, PR #11 FE và TV4 tại 1438af2. Chưa merge main; H02/PublicationGate vẫn cần Hoàng review, không tự nhận toàn bộ module đã nghiệm thu.

- /tai-khoan: thông tin từ session và tối đa 3 lịch pending/confirmed sắp tới của đúng customer.
- /lich-hen: danh sách phân trang own appointments theo controller TV5; layout FE, hủy kèm lý do qua endpoint cũ. Không đổi schema/service xác nhận hoặc đổi lịch.
- /tai-khoan/tin-nhan (customer.chat.index): own inquiry, q và conversation được validate; không serialize internal_note/contact người khác; tối đa 15 hội thoại/trang. Không dùng fixture cũ.
- Tạo yêu cầu dùng AJAX POST /tu-van của TV4, CSRF, hiển thị validation/throttle; chỉ chuyển tới thread sau 201 có ID thật.
- Dùng lại transport TV4 với mount lazy mỗi thread và một Echo connection dùng chung. Bubbles render bằng textContent; cursor, dedupe ID, UUID retry, revision bảo vệ draft vẫn giữ. Nội dung soạn giữ trong DOM khi đổi hội thoại cùng trang; trước reload/chuyển trang có bản nháp thì cảnh báo.
- New/closed chỉ history; assigned/in_progress có composer tối đa 1000 ký tự. Lưu tin HTTP thành công không là bằng chứng phía đối diện đã nhận.
- History vẫn tải được khi WebSocket chưa sẵn sàng; reconnect subscribed catch-up, không polling. Private authorization/state/version do TV4 kiểm phía server, nút disabled không thay quyền.

## Chạy chat local

Cần DB sẵn có, Reverb credentials đúng trong .env (không commit secret), BROADCAST_CONNECTION=reverb và database queue cùng Connection với message. Chạy Laravel HTTP, php artisan reverb:start và php artisan queue:work --queue=default --tries=3, npm run build sau khi chỉnh VITE_REVERB_*; không migrate:fresh hoặc reset dữ liệu để thử chat. Chỉ restart worker của môi trường đang sửa, không tự dừng worker của người khác.

## Kiểm tra

Composer check + build; node --test tests/JavaScript/inquiry-chat.test.js kiểm revision/UUID. Kiểm browser/customer và staff độc lập, một thread đã phân công, gửi hai chiều; new/closed chỉ đọc; thử mất kết nối/reconnect và giữ draft. Kiểm QA tách biệt, không chạy harness MySQL trên DB thật của nhóm. Ghi bằng chứng thực chạy trong PR; UI pass không thay review PublicationGate hoặc chứng minh MySQL concurrency.


## Bằng chứng QA tích hợp — 28/10/2026

- Composer check: 86 tests / 700 assertions, Pint/encoding/syntax/inventory đạt. Build Vite đạt; 4 cảnh báo font static có từ trước.
- JavaScript transport: 5 tests đạt, gồm UUID retry, bảo vệ bản nháp khi response muộn và HTTP submit khi thiếu cấu hình WebSocket.
- Browser trên SQLite QA riêng, HTTP khách :8040 và nhân viên :8041, Reverb :8080 + database worker: gửi khách → staff và staff → khách hiện ở phía nhận không reload.
- Tắt riêng Reverb QA: khách hiện không kết nối, draft giữ nguyên; staff gửi một tin khi gián đoạn. Bật lại Reverb, khách tự catch-up nhận tin đó không reload, draft vẫn nguyên.
- Form tạo yêu cầu tạo ID #2 thật trong QA, tự chuyển thread trạng thái chờ phân công, không hiện composer. Thread #1 đã phân công có composer. Owner/role/closed/internal_note kiểm bằng feature tests.
- Chưa chạy MySQL concurrency/PublicationGate harness trong phiên này; browser viewport override không thay kích thước thực, chưa xác minh responsive mới ở 390px. Không coi các test này là nghiệm thu toàn bộ TV4/H02. Không thay .env hoặc DB MySQL của người dùng.
