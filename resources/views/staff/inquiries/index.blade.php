@extends('layouts.account')
@section('title', 'Yêu cầu tư vấn')
@section('content')
    <h1 class="mb-5 text-2xl font-bold">Yêu cầu tư vấn</h1>
    <form method="GET" class="mb-5">
        <label>Trạng thái
            <select name="status" class="border p-2">
                <option value="">Tất cả</option>
                @foreach (\App\Enums\InquiryStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value }}</option>
                @endforeach
            </select>
        </label>
        <button class="border p-2">Lọc</button>
    </form>
    @php($prefix = auth()->user()->role === \App\Enums\UserRole::Customer ? 'customer' : (request()->routeIs('admin.*') ? 'admin' : 'staff'))
    @forelse ($inquiries as $inquiry)
        <article class="mb-3 rounded border p-4">
            <a class="text-red-700 underline" href="{{ route($prefix.'.inquiries.show', $inquiry) }}">Hồ sơ #{{ $inquiry->id }}</a>
            <p>{{ $inquiry->status->value }} — {{ $inquiry->assignedStaff?->name ?? 'Chưa phân công' }}</p>
            <p>{{ $inquiry->car?->name }}</p>
        </article>
    @empty
        <p>Chưa có yêu cầu.</p>
    @endforelse
    {{ $inquiries->links() }}
@endsection
