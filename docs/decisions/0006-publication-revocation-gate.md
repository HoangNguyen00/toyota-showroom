# ADR 0006 — Tuần tự publication và thu hồi quyền TV4/H02

24/10/2026. Thực hiện trên nhánh TV4 theo phê duyệt mở phạm vi có giới hạn của người dùng.
Mã đã commit và push tại `a7bf4059764fc506f1ee3e1aa10a8ea2b61199d6` lên
`feature/inquiry-chat-websocket`; chưa tạo Pull Request TV4, chưa merge vào main.
Mọi diff H02 vẫn cần Hoàng phụ trách Repo/Auth review trước merge.

## Nguyên nhân và lựa chọn

SELECT version cuối chỉ là quan sát, không ngăn reassign/H02 commit trước dispatch.
Publisher-only mutex hoặc thêm SELECT vẫn để khoảng đua. Chọn MỘT gate theo database
cho publisher, InquiryService::update và UpdateUserStatus::handle. Một showroom/khối
lượng demo nhỏ: gate toàn DB đơn giản hơn nhiều khóa participants/snapshot đổi, không
thêm lock-order cycle. Đổi lại publication và các mutation này tuần tự, chấp nhận
head-of-line blocking; chưa benchmark tải lớn. MessageService không lấy gate, không
đợi network và không có đường đang giữ row lock quay lại lấy gate.

MySQL GET_LOCK trên session PDO riêng, không persistent, không automatic reconnect,
không TTL/lease. Scope tên hash database trên cùng MySQL primary. Chờ tối đa 10 giây;
timeout/error propagate, không bỏ gate rồi tiếp tục. finally đóng session giải phóng
khóa; process chết được MySQL giải phóng khi session đóng. Trước dispatch kiểm sở hữu
bằng chính PDO này, mất session được phát hiện thì không phát. Không thêm Redis/package/
table/migration/config. SQLite dùng flock chỉ cho unit tests, không chứng minh MySQL.

## Thứ tự và ranh giới bảo đảm

Gate → transaction users ID tăng dần → inquiry → commit/nhả row locks → network
(chỉ publisher) → nhả gate. Revocation commit TRƯỚC publisher vào gate: job đọc lại
version/quyền và bị loại. Publisher đã vào gate: revoker phải chờ publication attempt
hoàn tất/throw và gate nhả rồi mới bắt đầu transaction. Không có revocation commit
chen giữa SELECT cuối và dispatch khi gate session còn sở hữu trên cùng primary.
Chặn reentrant/nested operation hoặc caller đã mở transaction, tránh lấy gate sau row
locks và tránh nhả gate trước outer commit. HTTP controller hiện gọi ở outermost.
Test H02 dùng DatabaseMigrations thay RefreshDatabase transaction wrapper, có DB guard.

Không đồng nhất dispatch, Reverb HTTP acceptance và client receipt. Gate giữ qua lời
gọi đồng bộ ShouldBroadcastNow; nếu HTTP timeout, acceptance có thể chưa biết dù attempt
đã bắt đầu trước revocation. Dữ liệu/request đã in-flight trước commit có thể đến sau
commit, không thu hồi ngược. F02 vẫn at-least-once, client dedupe cùng message ID.

Bảo đảm yêu cầu tất cả đường revoke/publish dùng gate, cùng primary và session gate
không bị mất ngoài ý muốn trong critical section. Session loss trước dispatch đã có
test fail-closed; KHÔNG chứng minh partition/failover/DB restart giữa kiểm ownership và
network. Không tuyên bố fence token được Reverb kiểm ở broker hoặc bảo đảm tuyệt đối
trong các sự cố đó. Multi-primary/Octane async concurrent/mở đường ghi trực tiếp ngoài
service chưa được hỗ trợ; cần thiết kế/review mới, không âm thầm mở rộng kết luận.

## H02 diff tối thiểu và bằng chứng

Chỉ import PublicationGate và bọc transaction gốc bằng run(); không đổi Policy,
active/role, bump version, desired-state idempotency, retry deadlock, unlock/history.
Regression giữ ca cũ, thêm rollback/retry/idempotency; MySQL hai process tại final
SELECT/dispatch và Reverb thật kiểm reassign, khóa sales, khóa customer. Có test kill
process giữ gate và kill CHỈ session gate của fixture DB test. Chi tiết TV4_VALIDATION.
