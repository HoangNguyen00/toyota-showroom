@extends('layouts.account')
@section('title', 'Hồ sơ tư vấn #'.$inquiry->id)
@section('content')
    <h1 class="mb-4 text-2xl font-bold">Hồ sơ #{{ $inquiry->id }}</h1>
    @if ($errors->any())
        <div role="alert" class="mb-4 text-red-700">{{ $errors->first() }}</div>
    @endif
    <p>Trạng thái: {{ $inquiry->status->value }}</p>
    <p>Nhân viên: {{ $inquiry->assignedStaff?->name ?? 'Chưa phân công' }}</p>
    <p class="my-4 whitespace-pre-wrap">{{ $inquiry->content }}</p>
    <p>Kết quả: {{ $inquiry->close_result ?? 'Chưa đóng' }}</p>
    @php($prefix = request()->routeIs('admin.*') ? 'admin' : 'staff')
    @can('manage', $inquiry)
        <p>{{ $inquiry->contact_name }} — {{ $inquiry->contact_phone }} — {{ $inquiry->contact_email }}</p>
        <h2 class="mt-5 font-bold">Ghi chú nội bộ</h2>
        <p class="whitespace-pre-wrap">{{ $inquiry->internal_note }}</p>
        @if ($inquiry->status !== \App\Enums\InquiryStatus::Closed)
            @can('assign', $inquiry)
                <form method="POST" action="{{ route('admin.inquiries.assign', $inquiry) }}" class="my-4">
                    @csrf @method('PATCH')
                    <label>Phân công sales
                        <select name="assigned_to" required class="border p-2">
                            @foreach ($sales as $person)
                                <option value="{{ $person->id }}" @selected($inquiry->assigned_to === $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="border p-2">Lưu phân công</button>
                </form>
            @endcan
            <form method="POST" action="{{ route($prefix.'.inquiries.note', $inquiry) }}" class="my-4">
                @csrf @method('PATCH')
                <label>Ghi chú<textarea name="internal_note" maxlength="10000" class="block w-full border p-2">{{ old('internal_note', $inquiry->internal_note) }}</textarea></label>
                <button class="border p-2">Lưu ghi chú</button>
            </form>
            @if ($inquiry->status === \App\Enums\InquiryStatus::Assigned)
                <form method="POST" action="{{ route($prefix.'.inquiries.start', $inquiry) }}">
                    @csrf @method('PATCH')
                    <button class="border p-2">Bắt đầu xử lý</button>
                </form>
            @elseif ($inquiry->status === \App\Enums\InquiryStatus::InProgress)
                <form method="POST" action="{{ route($prefix.'.inquiries.close', $inquiry) }}" class="my-4">
                    @csrf @method('PATCH')
                    <label>Kết quả
                        <select name="close_result" required class="border p-2">
                            @foreach (\App\Enums\InquiryCloseResult::cases() as $result)
                                <option value="{{ $result->value }}">{{ $result->value }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="border p-2">Đóng hồ sơ</button>
                </form>
            @endif
        @endif
    @endcan
    @if ((new \App\Policies\MessagePolicy)->viewHistory(auth()->user(), $inquiry))
        <section class="mt-6" data-inquiry-chat data-id="{{ $inquiry->id }}" data-version="{{ $inquiry->access_version }}">
            <h2 class="font-bold">Tin nhắn</h2>
            <p role="status" data-chat-status>Đang kết nối…</p>
            <button type="button" data-chat-older class="border p-2">Tải tin cũ hơn</button>
            <ol data-chat-messages class="my-4"></ol>
            @if ((new \App\Policies\MessagePolicy)->send(auth()->user(), $inquiry))
                <form data-chat-form>
                    <label>Tin nhắn<textarea name="content" required maxlength="1000" class="block w-full border p-2"></textarea></label>
                    <button class="border p-2">Gửi / thử lại</button>
                </form>
            @else
                <p>Hồ sơ này chỉ đọc.</p>
            @endif
        </section>
    @else
        <p class="mt-5">Hồ sơ khách vãng lai không có chat đăng nhập.</p>
    @endif
@endsection
