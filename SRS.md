# SRS — Website showroom Toyota

**Phiên bản 1.4 · 16/10/2026 · Nhóm 5 người · Phạm vi đồ án môn học**

PHP 8.3 · Laravel 12 · MySQL 8 · Blade · Tailwind 4 · Reverb/Echo.
Đây là yêu cầu cần triển khai, không phải danh sách chức năng đã chạy.
Xem [trạng thái thật](docs/architecture/FEATURE_STATUS.md).
Bản này thay SRS 1.3 về phạm vi; [bản 1.3](docs/source/SRS-1.3-reviewed.md)
được lưu nguyên để truy vết. Word v6 giữ nguyên làm nguồn tham khảo.
Đọc [đối chiếu môn học](docs/requirements/COURSE_SCOPE.md) trước khi chia việc.

## 1. Mục tiêu và ranh giới

Website của **một showroom xe mới Toyota**: xem xe và giá công bố, gửi nhu cầu,
tư vấn qua chat thời gian thực, đặt lịch xem xe hoặc bảo dưỡng.
Khách đến showroom để thương lượng, ký giấy tờ, thanh toán và nhận xe.
Đây là mô hình nhóm chọn; không khẳng định là toàn bộ quy trình nội bộ Toyota.

Không có giỏ hàng, đơn mua xe online, thanh toán/đặt cọc, hợp đồng, hóa đơn,
trả góp, quản lý xưởng/phụ tùng, nhiều chi nhánh hoặc tích hợp dữ liệu Toyota.
Không làm riêng đăng ký lái thử. Nhân viên trao đổi trực tiếp nếu khách có nhu cầu.

**Hai mức phạm vi:**
- **P1 — phải hoàn thành:** toàn bộ mục 2–8, gồm chat thời gian thực đã thống nhất.
  Bảo mật, tính đúng dữ liệu và báo cáo thuộc P1.
- **P2 — đề xuất:** mục 9; chỉ lập kế hoạch sau khi P1 đạt checklist nghiệm thu.
  Không tạo bảng/route/dependency cho P2 trước chỉ để đủ số lượng.
WebSocket là lựa chọn của nhóm, không phải tiêu chí riêng bắt buộc trong rubric.

## 2. Actor và quyền

| Actor | Quyền |
| --- | --- |
| Guest | Xem/tìm/lọc xe, xem dịch vụ, gửi tư vấn và đặt lịch không cần tài khoản |
| Customer | Như guest; đăng nhập để chat, xem hồ sơ/lịch của mình và hủy lịch hợp lệ |
| Sales | Xem/xử lý hồ sơ và lịch được giao; chat, sửa ghi chú nội bộ |
| Admin | CRUD dòng xe/xe/ảnh/dịch vụ; quản lý tài khoản; phân công hồ sơ/lịch |

Guest vẫn là actor dù không có tài khoản. Không ép đăng ký chỉ để xem xe.
Guest không tra lịch sử hay chat bằng số điện thoại/mã yêu cầu công khai.
Không tự ghép hồ sơ guest vào tài khoản vì trùng số điện thoại.
Customer chỉ thấy dữ liệu của mình; sales chỉ thấy dữ liệu được giao.
Admin không tự tạo đơn mua xe thay khách.

## 3. Chức năng P1

### FR-AUTH — Tài khoản

Đăng ký customer, đăng nhập và đăng xuất bằng session Laravel.
Server tự gán role customer, không tin role/is_active từ form.
Tên tối đa 100; email hợp lệ/unique/tối đa 255; mật khẩu tối thiểu 8 ký tự,
tối đa 72 byte, không chứa NUL (bcrypt hiện tại), có xác nhận,
lưu hash. Phone demo 10 số theo `/^(0[3-9])\d{8}$/`, không coi là xác minh số có thật.
Admin tạo sales và khóa/mở customer/sales; không cho tự khóa admin duy nhất.
Khóa tài khoản phải chặn request và nhận tin mới, không chỉ chặn lần đăng nhập sau.
Giới hạn đăng nhập 5 lần thất bại/phút/IP+email; đăng nhập thành công xóa bộ đếm.
Quên mật khẩu/email verification/OTP là P2.

### FR-CAT — Xe và giao diện công khai

Trang chủ, danh sách xe, chi tiết xe, dịch vụ và thông tin liên hệ showroom.
Tìm theo tên/mã, lọc dòng xe và khoảng giá, sắp xếp mới nhất/giá tăng/giảm,
phân trang 12 bản ghi và giữ filter. Min > max báo lỗi.
Dòng xe là Vios, Camry...; mỗi `cars` là **một chiếc xe showroom**, có code unique,
phiên bản, màu, năm, thông số và ảnh. Nhiều chiếc cùng phiên bản có code khác.
Không đồng nhất một chiếc đã bán với cả dòng/phiên bản hết xe.

Giá VND số nguyên, dương, tối thiểu 1.000.000 theo quy ước demo; DB decimal(15,0).
Nhãn “Giá công bố tham khảo, chưa phải giá lăn bánh; liên hệ showroom”.
Dữ liệu mẫu ghi nguồn và ngày tham khảo, hoặc ghi rõ minh họa; không tự cập nhật giá.
Không tự tính phí/thuế, giảm giá hay hứa giá thanh toán cuối cùng.

Công khai chỉ khi cả xe và dòng xe bật hiển thị; URL xe ẩn trả 404.
Xe available nhận tư vấn/đặt lịch; reserved nhận tư vấn, không nhận lịch xem;
sold chỉ xem thông tin/xe khác, không tạo tư vấn/lịch mới cho chiếc đó.
Admin cập nhật reserved/sold theo thông tin ngoài đời; đặt lịch không tự giữ xe.
Giữ lịch sử tư vấn/lịch đã có khi tình trạng xe thay đổi.

### FR-ADM — CRUD

Admin thêm/xem/sửa/xóa dòng xe, xe và dịch vụ; upload/sắp xếp/chọn ảnh chính.
Bản ghi chưa được tham chiếu được xóa theo FK; đã có hồ sơ thì chỉ ẩn.
Không xóa user/hồ sơ/tin/lịch để làm mất lịch sử.
Tạo xe mặc định nháp/ẩn; công khai cần thông tin hợp lệ và đúng một ảnh chính.
Tối đa 10 ảnh/xe, mỗi ảnh 5 MB; chỉ JPEG/PNG/WebP được kiểm MIME phía server,
tên file do server sinh, không chấp nhận SVG/script/file thực thi.
Năm xe từ 2000 đến năm hiện tại + 1 (quy ước demo).
Giữ slug/source và brand đang có trong schema; P1 cố định brand Toyota,
không có chọn hãng trên UI. Không cần migration bỏ brand để đạt phạm vi một hãng.
Services giữ type warranty/maintenance hiện có: bảo hành là nội dung giới thiệu/
liên hệ, không xét duyệt yêu cầu bảo hành; maintenance được bật nhận lịch.

### FR-INQ — Tư vấn

Guest/customer gửi họ tên, phone, email tùy chọn, nhu cầu 10–2000 ký tự;
car_id tùy chọn nhưng nếu chọn phải là xe công khai được phép tư vấn.
Customer_id lấy từ phiên, không từ payload. Email ánh xạ contact_email.
Trả thông báo tiếp nhận và mã tham chiếu (có thể dùng ID hiện có);
mã không cấp quyền đọc hồ sơ. Form tư vấn và mỗi form lịch giới hạn
3 lần/10 phút/IP/endpoint, lỗi rõ ràng; UI khóa nút trong khi gửi.

Admin giao cho sales active; sales xử lý/ghi một internal_note không lộ cho khách.
Trạng thái: new → assigned → in_progress → closed.
in_progress khi sales bắt đầu xử lý hoặc gửi tin đầu.
new chưa có người phụ trách, chưa gửi chat; closed chỉ đọc.
Đóng ghi close_result theo enum hiện có; đây là kết quả tư vấn, không là đơn bán xe.
Mở lại hồ sơ và lịch sử nhiều ghi chú chuyển P2.
Phân công lại là P1; kiểm quyền và thu hồi quyền nhận tin của người cũ bắt buộc.

### FR-CHAT — Chat thời gian thực

Customer đã đăng nhập chat với sales được giao/admin trên inquiry của mình;
chỉ gửi khi assigned/in_progress. Guest nhận liên hệ qua phone.
Tin văn bản 1–1000 ký tự, lưu MySQL trước rồi phát sau commit qua Reverb/Echo.
Không thay WebSocket bằng polling. Kiểm role/active/owner/assigned ở HTTP và
private channel; không phát nội dung lên public channel.
Người gửi do server lấy từ phiên, không cho giả sender.

Client gửi client_message_id UUID; unique(sender_id,client_message_id).
Retry cùng inquiry/nội dung trả tin cũ; payload khác trả 409; không phát lại.
Lịch sử phân trang tối đa 50 tin/lần; dedupe theo ID; khi nối lại đồng bộ lịch sử,
hiển thị trạng thái mất kết nối/gửi lỗi và cho retry, không báo thành công giả.
Mục tiêu demo hai phiên mạng ổn định: nhận tin trong 2 giây, không reload.
Đổi người/khóa tài khoản tăng access_version, không phát tin mới về kênh cũ.
Kiểm lại quyền khi gửi/phát, kể cả job đang chờ; không tuyên bố xóa được tin đã nhận.
Có thể thông báo cần tải lại khi đổi quyền; kênh điều khiển riêng users.{id}
và tự chuyển kênh liền mạch là P2, không buộc xây thêm cho demo môn học.

### FR-APT — Lịch hẹn

Chọn xem xe hoặc bảo dưỡng; không tự tạo inquiry/chat khi đặt lịch.
Họ tên/phone bắt buộc; thời gian mong muốn sau hiện tại và trong 6 tháng.
Xem xe cần car_id available/công khai; bảo dưỡng cần service công khai,
allow_booking và thông tin xe khách (mẫu xe/biển số nhập một trường).
Không yêu cầu khách đã mua xe ở showroom. Thời gian là Asia/Ho_Chi_Minh.
Guest xem thông báo đã gửi, nhân viên gọi xác nhận; customer xem lịch của mình.

pending → confirmed → completed hoặc no_show; pending/confirmed → cancelled.
Admin phân công sales active trước khi xác nhận; không tự gán từ payload khách.
Nhân viên kiểm tra điều kiện và chốt confirmed_at sau trao đổi.
Khung tiếp nhận 60 phút/nhân viên là quy ước demo, **không phải thời lượng
bảo dưỡng hoặc chính sách Toyota**. Không nhận hai lịch confirmed cách < 60 phút;
cách đúng 60 phút hợp lệ. Xác nhận dùng transaction, khóa appointment rồi sales
và kiểm lại trạng thái/quyền/giờ; mọi đường xác nhận dùng cùng một service.
Customer chỉ hủy lịch của mình còn pending hoặc confirmed và chưa tới giờ
(requested_at nếu pending, confirmed_at nếu confirmed), lưu lý do.
Nhân viên/admin được hủy kèm lý do; completed/no_show/cancelled là trạng thái cuối.
completed thao tác thủ công sau giờ hẹn; no_show chỉ sau giờ đã xác nhận.
Nếu giờ không phù hợp, nhân viên hủy rồi hướng dẫn tạo yêu cầu mới.
Luồng khách tự đổi lịch riêng chuyển P2.

### FR-UI — Chất lượng giao diện

Phong cách Toyota: nền sáng, chữ dễ đọc, đỏ làm điểm nhấn; đồng bộ khách/staff/admin.
Responsive mobile/tablet/desktop; form có lỗi theo trường, success, loading,
empty và disabled phù hợp. Có điều hướng, thông tin liên hệ showroom demo.
Ít nhất một AJAX thực hành: form tư vấn trả JSON, hiển thị lỗi/kết quả không reload.
Chat không thay bài minh chứng AJAX. Lọc xe P1 được phép tải lại trang.
Footer chỉ hiển thị thương hiệu Toyota Showroom (chủ repo quyết định 23/11/2026 bỏ dòng ghi chú đồ án).

## 4. Dữ liệu và kiến trúc

Giữ **9 bảng hiện có**: users, car_categories, cars, car_images, inquiries,
messages, services, appointments, activity_logs; bảng kỹ thuật Laravel tính riêng.
Không có yêu cầu số bảng tối thiểu trong rubric đã cung cấp.
internal_note đủ P1; inquiry_notes/code/closed_at/ServiceType.other không bắt buộc.
activity_logs giữ nền, P1 không yêu cầu module tra cứu audit; dùng timestamps và
trạng thái/kết quả/lý do hiện có. Không đổi hoặc xóa dữ liệu nền vì giản lược tài liệu.

Laravel MVC đơn khối; FormRequest validation, Policy quyền từng bản ghi,
Eloquent relationships/eager loading; Service cho transaction và chat.
Không thêm microservices, SPA framework hoặc tầng repository bọc Eloquent.
Migration đã gộp không sửa ngược; thay schema cần migration mới có review.
[DATA_MODEL](docs/architecture/DATA_MODEL.md) ghi cột thật;
[CONTRACTS](docs/architecture/CONTRACTS.md) ghi endpoint đích.

## 5. Ràng buộc không được giảm

CSRF, password hash, validate server, giới hạn upload/throttle và kiểm quyền
là P1. Không chỉ ẩn nút trên giao diện. Khách A không đọc/sửa hồ sơ khách B.
Không trả internal_note, mật khẩu hoặc thông tin hồ sơ khác trong JSON/events.
Dùng allowlist response; không log dữ liệu liên hệ đầy đủ/secret.
Foreign key/unique và transaction bảo vệ quan hệ, chống trùng tin/lịch.
Không commit .env, secret, vendor, node_modules, upload thật.
Không reset DB chung; test dùng DB riêng. SQLite không chứng minh khóa MySQL.

## 6. Thứ tự làm và phân công

| Đợt | Việc | Người chính |
| --- | --- | --- |
| A | Chốt phạm vi, DB, layout, Auth/Policy; seed demo | Hoàng + cả nhóm |
| B | CRUD xe/ảnh/dòng xe, catalog/search/filter/pagination | Võ Nguyễn Thành Đạt (TV3) + Đạt |
| B | Tiếp nhận/phân công tư vấn, internal_note; dịch vụ/lịch | Nguyễn Huy Hoàng (TV4); Phan Võ Chí Bảo (TV5) |
| C | Chat WebSocket văn bản + lịch sử/quyền/retry; AJAX form | Nguyễn Huy Hoàng (TV4) + Đạt; Hoàng review quyền |
| D | Tích hợp, kiểm thử MySQL, responsive, báo cáo, tập demo | Cả 5 người |
| Sau P1 | Chọn đề xuất P2 theo thời gian còn lại | Chỉ sau checklist và quyết định nhóm |

Đạt phụ trách UI khách/layout; module owner làm màn admin/staff trên layout chung.
Mỗi người có PR/kiểm thử/báo cáo và giải thích được phần mình.
Đợt C vẫn là P1, không được bỏ chat rồi nhận sản phẩm đã hoàn thành.
Danh sách 5 người đã được nhóm xác nhận; không bảo đảm điểm 8+/9 từ số tính năng.

## 7. Tiêu chí nghiệm thu P1

Xem bảng [ACCEPTANCE](docs/testing/ACCEPTANCE.md) là danh sách thực thi.
Phải demo toàn bộ luồng hợp lệ, lỗi nhập liệu, sai quyền và trạng thái biên.
Seed đích: 1 admin, 2 sales, 3 customer, ít nhất 10 xe thuộc 3 dòng Toyota,
2 nội dung dịch vụ warranty/maintenance; đủ dữ liệu để chứng minh phân trang
(có thể tăng xe trên 12), và hồ sơ/lịch theo trạng thái.
Đây là dữ liệu đích, seeder nền hiện chưa đủ.
README giúp thành viên cài từ lockfile, cấu hình MySQL, migrate/seed/build,
chạy web/Reverb/worker bằng secret riêng; có hướng dẫn backup/restore DB demo.
Ảnh/giá demo ghi nguồn. Chạy kiểm tra nền và test nghiệp vụ phù hợp,
thử hai phiên chat và hai kết nối MySQL cho trùng lịch.
Ghi kết quả thật, lỗi còn tồn và cách tái hiện; không nhận test nền là test nghiệp vụ.

## 8. Báo cáo và thang điểm

Theo Rubric 2 đề cương v1.2: hình thức báo cáo 10%, nội dung 10%,
giao diện 30%, sản phẩm/chức năng 40%, tham gia nhóm 10%.
Không có mục độc lập “kỹ thuật 25%” hay “sáng tạo 10%”.
Báo cáo gồm bài toán/phạm vi, actor/use case, luồng chính, ERD, MVC,
màn hình và kết quả kiểm thử, cách chạy, phân công/Git, hạn chế/đề xuất,
nguồn tham khảo theo mẫu giảng viên.
Giảng viên quyết định đề tài/yêu cầu và điểm; cập nhật nếu có rubric mới.
Xem COURSE_SCOPE để đối chiếu nội dung thực hành, không nhầm đồ án với toàn học phần.

## 9. Đề xuất sau nghiệm thu P1

Chưa cam kết triển khai: dự toán lăn bánh; so sánh/yêu thích; PDF báo giá;
lọc không reload/API status riêng; badge chưa đọc/typing/attachment;
tự chuyển kênh qua users.{id}; lịch sử nhiều ghi chú và trang audit đầy đủ;
mở lại hồ sơ, khách tự đổi lịch; quên mật khẩu/OTP/email;
quản lý tồn kho nhiều chiếc theo phiên bản, VIN, lịch xưởng và kho phụ tùng.
Giá lăn bánh nếu làm sau phải có phạm vi, nguồn/thời điểm dữ liệu và nhãn ước tính;
không lấy bảng thuế/biểu phí vào P1.

## 10. Truy vết từ SRS 1.3

FR-AUTH/CAT/INQ/CHAT/APT/ADM giữ ý nghĩa module, không giữ mọi tiểu yêu cầu cũ.
FR-CAT-6 và FR-CHAT-9 cũ là P2; FR-CHAT về xác thực, lưu/phát tin,
thu hồi quyền và chống trùng vẫn P1. D-03 (inquiry_notes), D-12 (other),
mở lại và đổi lịch cũ chuyển P2; D-17 dùng Toyota cố định, chưa cần bỏ brand.
BR-9/audit toàn bộ không là tiêu chí P1; giữ bảng audit nền.
Giới hạn năm cố định cũ được thay bằng năm hiện tại+1.
Không dùng 26 test cũ làm danh sách buộc xây toàn bộ tính năng đã chuyển P2.
Đọc ADR 0003 thay cho chỉ dẫn đích trái bản này trong ADR/review cũ.
