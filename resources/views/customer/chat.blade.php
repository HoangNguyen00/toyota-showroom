@extends('layouts.customer')
@section('title', 'Tin nhắn tư vấn')
@section('content')
@php($labels = ['new' => 'Chờ phân công', 'assigned' => 'Đã phân công', 'in_progress' => 'Đang tư vấn', 'closed' => 'Đã kết thúc'])
<div class="customer-page-heading"><div><p class="customer-eyebrow">MY TOYOTA / KẾT NỐI</p><h1>Tin nhắn tư vấn</h1></div><button type="button" class="customer-button customer-button-red" data-open-inquiry>Yêu cầu tư vấn mới <x-customer-icon name="chat"/></button></div>
<p class="customer-chat-disclaimer">Tin nhắn gắn với yêu cầu tư vấn của bạn. Khi đổi người phụ trách, hãy tải lại trang để cập nhật cuộc tư vấn.</p>
@if($errors->any())<p class="workspace-errors" role="alert">{{ $errors->first() }}</p>@endif
<section class="customer-chat {{ request('conversation') ? 'is-thread-open' : '' }}" data-customer-chat aria-label="Không gian tin nhắn">
    <aside class="chat-conversations" aria-label="Danh sách cuộc tư vấn">
        <div class="chat-list-heading"><h2>Cuộc tư vấn</h2><span>{{ $conversations->total() }} yêu cầu</span></div>
        <form method="GET" action="{{ route('customer.chat.index') }}"><label class="chat-search"><x-customer-icon name="search"/><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Tìm cuộc tư vấn…" aria-label="Tìm cuộc tư vấn"></label><button type="submit" class="workspace-search-button">Tìm kiếm</button></form>
        <div class="chat-conversation-list">
            @forelse($conversations as $conversation)
            <a class="chat-conversation {{ $selected?->id === $conversation->id ? 'is-selected' : '' }}" href="{{ route('customer.chat.index', array_filter(['conversation' => $conversation->id, 'q' => request('q'), 'page' => request('page')])) }}" data-conversation="{{ $conversation->id }}" aria-current="{{ $selected?->id === $conversation->id ? 'true' : 'false' }}" aria-controls="thread-{{ $conversation->id }}"><span class="chat-conversation-avatar">T</span><span class="chat-conversation-copy"><strong>{{ $conversation->car?->name ?? 'Tư vấn Toyota' }}</strong><span>{{ $conversation->content }}</span><small class="chat-status chat-status-{{ $conversation->status->value }}">{{ $labels[$conversation->status->value] }}</small></span></a>
            @empty
                <p class="chat-search-empty">{{ request('q') ? 'Không có cuộc tư vấn phù hợp.' : 'Bạn chưa có yêu cầu tư vấn nào.' }}</p>
            @endforelse
        </div>
        <div class="workspace-conversation-pagination">{{ $conversations->links('pagination::simple-tailwind') }}</div>
        <div class="chat-list-note"><x-customer-icon name="shield"/><p>Chỉ bạn và nhân viên<br>có quyền xem cuộc tư vấn.</p></div>
    </aside>
    <div class="chat-thread-area">
        @forelse($threads as $conversation)
        @php($canSend = in_array($conversation->status, [\App\Enums\InquiryStatus::Assigned, \App\Enums\InquiryStatus::InProgress], true))
        <section class="chat-thread" id="thread-{{ $conversation->id }}" data-chat-thread="{{ $conversation->id }}" data-inquiry-chat data-id="{{ $conversation->id }}" data-version="{{ $conversation->access_version }}" data-user-id="{{ auth()->id() }}" data-skin="customer" @if($selected?->id !== $conversation->id) hidden @endif aria-labelledby="title-{{ $conversation->id }}">
            <header class="chat-thread-header"><a href="{{ route('customer.chat.index', array_filter(['q' => request('q'), 'page' => request('page')])) }}" class="chat-mobile-back" data-chat-back aria-label="Quay lại danh sách cuộc tư vấn"><x-customer-icon name="back"/></a><span class="chat-advisor-avatar">T</span><div><h2 id="title-{{ $conversation->id }}">{{ $conversation->assignedStaff?->name ?? 'Chờ nhân viên phụ trách' }}</h2><p>{{ $conversation->car?->name ?? 'Tư vấn Toyota' }} <span>· #{{ $conversation->id }}</span></p></div></header>
            <div class="chat-vehicle"><div><span>YÊU CẦU TƯ VẤN #{{ $conversation->id }}</span><strong>{{ $conversation->car?->name ?? 'Trao đổi với showroom' }}</strong></div><span class="chat-status chat-status-{{ $conversation->status->value }}">{{ $labels[$conversation->status->value] }}</span></div>
            <p class="workspace-connection" role="status" data-chat-status>Đang tải lịch sử và kết nối…</p>
            <div class="chat-messages" data-chat-scroll><button type="button" data-chat-older class="workspace-load-older">Tải tin cũ hơn</button><ol data-chat-messages aria-label="Tin nhắn trong cuộc tư vấn" aria-live="polite" aria-relevant="additions"></ol></div>
            @if($canSend)
            <form class="chat-composer" data-chat-form><div class="chat-compose-row"><label class="sr-only" for="draft-{{ $conversation->id }}">Nội dung tin nhắn</label><textarea id="draft-{{ $conversation->id }}" name="content" rows="2" maxlength="1000" required placeholder="Viết lời nhắn của bạn…" aria-describedby="hint-{{ $conversation->id }}"></textarea><button type="submit" class="chat-send-button" aria-label="Gửi tin nhắn hoặc thử lại"><x-customer-icon name="send"/><span>Gửi / thử lại</span></button></div><div class="chat-compose-hint" id="hint-{{ $conversation->id }}"><span>Nội dung tối đa 1000 ký tự.</span><span data-chat-count>0 / 1000</span></div></form>
            @else
                <p class="chat-readonly-note workspace-readonly">{{ $conversation->status === \App\Enums\InquiryStatus::New ? 'Đang chờ phân công nhân viên. Bạn có thể xem lại lịch sử, chưa thể gửi tin.' : 'Cuộc tư vấn đã kết thúc. Bạn có thể xem lại nội dung trao đổi.' }}</p>
            @endif
        </section>
        @empty
            <div class="chat-waiting"><x-customer-icon name="chat"/><h3>Khởi đầu một cuộc trò chuyện</h3><p>Gửi yêu cầu tư vấn để showroom phân công nhân viên hỗ trợ bạn.</p><button type="button" class="customer-button customer-button-red" data-open-inquiry>Gửi yêu cầu tư vấn <x-customer-icon name="arrow"/></button></div>
        @endforelse
    </div>
</section>
<dialog class="workspace-inquiry-dialog" data-inquiry-dialog aria-labelledby="inquiry-dialog-title"><form method="dialog"><button class="workspace-dialog-close" aria-label="Đóng yêu cầu tư vấn">Đóng ×</button></form><p class="customer-eyebrow">TOYOTA / TƯ VẤN</p><h2 id="inquiry-dialog-title">Chúng tôi có thể giúp gì cho bạn?</h2><p>Showroom sẽ tiếp nhận và phân công tư vấn viên. Bạn có thể chat khi yêu cầu được phân công.</p><form data-inquiry-create action="{{ route('inquiries.store') }}" method="POST" data-chat-url="{{ route('customer.chat.index') }}">@csrf<label>Họ và tên<input name="name" maxlength="100" value="{{ auth()->user()->name }}" required autocomplete="name"></label><label>Số điện thoại<input name="phone" maxlength="10" value="{{ auth()->user()->phone }}" required autocomplete="tel" inputmode="tel"></label><label>Email<input name="email" type="email" maxlength="255" value="{{ auth()->user()->email }}" autocomplete="email"></label><label>Nhu cầu của bạn<textarea name="content" minlength="10" maxlength="2000" rows="4" required placeholder="Bạn đang quan tâm dòng xe hoặc cần tư vấn điều gì?"></textarea></label><p class="workspace-inquiry-feedback" data-inquiry-feedback role="alert" hidden></p><button type="submit" class="customer-button customer-button-red">Gửi yêu cầu tư vấn</button></form></dialog>
<noscript><p class="customer-chat-disclaimer">Bật JavaScript để tải lịch sử, gửi và nhận tin nhắn thời gian thực.</p></noscript>
@endsection
