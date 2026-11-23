{{-- Dùng cho /staff/inquiries/{id}, /admin/inquiries/{id} và bản HTML /yeu-cau/{id} của khách (giữ layout tài khoản cũ cho khách).
     Thông tin liên hệ và ghi chú nội bộ chỉ nằm trong @can('manage'); Policy vẫn kiểm tra lại ở server khi gửi form. --}}
@extends(auth()->user()->role === \App\Enums\UserRole::Customer ? 'layouts.account' : 'layouts.staff')
@section('title', 'Hồ sơ tư vấn #'.$inquiry->id)
@section('eyebrow', 'Hồ sơ tư vấn')
@section('heading', 'Hồ sơ #'.$inquiry->id)
@section('back')
    @php($listPrefix = auth()->user()->role === \App\Enums\UserRole::Customer ? 'customer' : (request()->routeIs('admin.*') ? 'admin' : 'staff'))
    <a class="staff-back" href="{{ route($listPrefix.'.inquiries.index') }}"><span aria-hidden="true">←</span> Danh sách tư vấn</a>
@endsection
@section('content')
    @php($prefix = request()->routeIs('admin.*') ? 'admin' : 'staff')
    @php($closeLabels = ['da_tu_van' => 'Đã tư vấn', 'khach_khong_tiep_tuc' => 'Khách không tiếp tục', 'khach_giao_dich_truc_tiep' => 'Khách giao dịch trực tiếp', 'khong_lien_he_duoc' => 'Không liên hệ được'])
    @php($canManage = auth()->user()->can('manage', $inquiry))
    <div class="staff-scope">
        @if (auth()->user()->role === \App\Enums\UserRole::Customer)
            <h1 class="staff-title mb-4">Hồ sơ #{{ $inquiry->id }}</h1>
        @endif
        @if ($errors->any())
            <div role="alert" class="staff-alert staff-alert--error">{{ $errors->first() }}</div>
        @endif

        <section class="staff-card mb-5" aria-label="Tóm tắt hồ sơ">
            <dl class="staff-facts">
                <div><dt>Mã hồ sơ</dt><dd class="staff-strong">#{{ $inquiry->id }}</dd></div>
                <div><dt>Trạng thái</dt><dd><x-staff.status :status="$inquiry->status" /></dd></div>
                <div><dt>Người phụ trách</dt><dd @class(['is-empty' => ! $inquiry->assignedStaff])>{{ $inquiry->assignedStaff?->name ?? 'Chưa phân công' }}</dd></div>
                <div><dt>Mã xe</dt><dd>@if ($inquiry->car)<span class="staff-car-code">{{ $inquiry->car->code }}</span>@else<span class="staff-sub italic">Chưa chọn xe</span>@endif</dd></div>
                <div><dt>Kết quả</dt><dd @class(['is-empty' => ! $inquiry->close_result])>{{ $inquiry->close_result ? ($closeLabels[$inquiry->close_result] ?? $inquiry->close_result) : 'Chưa đóng' }}</dd></div>
            </dl>
        </section>

        <div @class(['staff-detail' => $canManage, 'staff-stack' => ! $canManage])>
            <div>
                <section class="staff-card" aria-labelledby="inquiry-car-title">
                    <h2 id="inquiry-car-title" class="staff-card-title">Xe khách chọn</h2>
                    <x-staff.car :car="$inquiry->car" />
                </section>

                <section class="staff-card" aria-labelledby="inquiry-content-title">
                    <h2 id="inquiry-content-title" class="staff-card-title">Nội dung yêu cầu</h2>
                    <p class="staff-quote">{{ $inquiry->content }}</p>
                </section>

                @if ((new \App\Policies\MessagePolicy)->viewHistory(auth()->user(), $inquiry))
                    <section class="staff-card staff-chat" data-inquiry-chat data-id="{{ $inquiry->id }}" data-version="{{ $inquiry->access_version }}" aria-labelledby="inquiry-chat-title">
                        <div class="staff-chat-head">
                            <h2 id="inquiry-chat-title">Tin nhắn với khách</h2>
                            <p role="status" class="staff-chat-status" data-chat-status>Đang kết nối…</p>
                        </div>
                        <button type="button" data-chat-older class="staff-btn staff-btn--secondary staff-btn--sm self-start">Tải tin cũ hơn</button>
                        <div class="staff-chat-scroll" data-chat-scroll>
                            <ol data-chat-messages aria-label="Tin nhắn hồ sơ #{{ $inquiry->id }}"></ol>
                        </div>
                        @if ((new \App\Policies\MessagePolicy)->send(auth()->user(), $inquiry))
                            <form data-chat-form>
                                <label for="chat-content-{{ $inquiry->id }}" class="staff-label">Tin nhắn</label>
                                <textarea id="chat-content-{{ $inquiry->id }}" name="content" required maxlength="1000"></textarea>
                                <button class="staff-btn staff-btn--primary self-start">Gửi / thử lại</button>
                            </form>
                        @else
                            <p class="staff-hint">Hồ sơ này chỉ đọc.</p>
                        @endif
                    </section>
                @else
                    <p class="staff-card staff-hint">Hồ sơ khách vãng lai không có chat đăng nhập.</p>
                @endif
            </div>

            @can('manage', $inquiry)
                <div>
                    <section class="staff-card" aria-labelledby="inquiry-contact-title">
                        <h2 id="inquiry-contact-title" class="staff-card-title">Thông tin liên hệ</h2>
                        <dl class="staff-facts">
                            <div><dt>Họ tên</dt><dd>{{ $inquiry->contact_name }}</dd></div>
                            <div><dt>Điện thoại</dt><dd>{{ $inquiry->contact_phone }}</dd></div>
                            <div><dt>Email</dt><dd @class(['is-empty' => ! $inquiry->contact_email])>{{ $inquiry->contact_email ?: 'Không có' }}</dd></div>
                        </dl>
                    </section>

                    @if ($inquiry->status !== \App\Enums\InquiryStatus::Closed)
                        <section class="staff-card" aria-labelledby="inquiry-actions-title">
                            <h2 id="inquiry-actions-title" class="staff-card-title">Xử lý hồ sơ</h2>
                            <div class="staff-stack">
                                @can('assign', $inquiry)
                                    <form method="POST" action="{{ route('admin.inquiries.assign', $inquiry) }}" class="staff-op staff-op--assign">
                                        @csrf @method('PATCH')
                                        <label for="inquiry-assign" class="staff-label">Phân công sales</label>
                                        <select id="inquiry-assign" name="assigned_to" required>
                                            @foreach ($sales as $person)
                                                <option value="{{ $person->id }}" @selected($inquiry->assigned_to === $person->id)>{{ $person->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="staff-btn staff-btn--secondary self-start">Lưu phân công</button>
                                    </form>
                                @endcan
                                @if ($inquiry->status === \App\Enums\InquiryStatus::Assigned)
                                    <form method="POST" action="{{ route($prefix.'.inquiries.start', $inquiry) }}" class="staff-op staff-op--confirm">
                                        @csrf @method('PATCH')
                                        <p class="staff-hint">Nhận hồ sơ và bắt đầu tư vấn với khách.</p>
                                        <button class="staff-btn staff-btn--primary self-start">Bắt đầu xử lý</button>
                                    </form>
                                @elseif ($inquiry->status === \App\Enums\InquiryStatus::InProgress)
                                    <form method="POST" action="{{ route($prefix.'.inquiries.close', $inquiry) }}" class="staff-op staff-op--conclude">
                                        @csrf @method('PATCH')
                                        <label for="inquiry-close-result" class="staff-label">Kết quả khi đóng hồ sơ</label>
                                        <select id="inquiry-close-result" name="close_result" required>
                                            @foreach (\App\Enums\InquiryCloseResult::cases() as $result)
                                                <option value="{{ $result->value }}">{{ $closeLabels[$result->value] ?? $result->value }}</option>
                                            @endforeach
                                        </select>
                                        <button class="staff-btn staff-btn--dark self-start">Đóng hồ sơ</button>
                                    </form>
                                @endif
                            </div>
                        </section>
                    @endif

                    <section class="staff-card staff-private" aria-labelledby="inquiry-note-title">
                        <h2 id="inquiry-note-title" class="staff-card-title">Ghi chú nội bộ <span class="staff-private-tag">Chỉ nhân viên</span></h2>
                        <p class="staff-card-note">Khách không thấy ghi chú này.</p>
                        @if ($inquiry->status !== \App\Enums\InquiryStatus::Closed)
                            <form method="POST" action="{{ route($prefix.'.inquiries.note', $inquiry) }}" class="flex flex-col gap-2">
                                @csrf @method('PATCH')
                                <label for="inquiry-note" class="staff-label">Ghi chú</label>
                                <textarea id="inquiry-note" name="internal_note" maxlength="10000" rows="4">{{ old('internal_note', $inquiry->internal_note) }}</textarea>
                                <button class="staff-btn staff-btn--secondary self-start">Lưu ghi chú</button>
                            </form>
                        @else
                            <p class="staff-quote">{{ $inquiry->internal_note ?: 'Không có ghi chú.' }}</p>
                        @endif
                    </section>
                </div>
            @endcan
        </div>
    </div>
@endsection
