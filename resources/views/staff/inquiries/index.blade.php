{{-- Dùng cho /staff/inquiries, /admin/inquiries và bản HTML /yeu-cau của khách (giữ layout tài khoản cũ cho khách). --}}
@extends(auth()->user()->role === \App\Enums\UserRole::Customer ? 'layouts.account' : 'layouts.staff')
@section('title', 'Yêu cầu tư vấn')
@section('heading', 'Yêu cầu tư vấn')
@section('lead', request()->routeIs('admin.*') ? 'Tất cả hồ sơ tư vấn; mở hồ sơ để phân công sales.' : 'Hồ sơ tư vấn được phân công cho bạn.')
@section('content')
    @php($prefix = auth()->user()->role === \App\Enums\UserRole::Customer ? 'customer' : (request()->routeIs('admin.*') ? 'admin' : 'staff'))
    @php($statusLabels = ['new' => 'Chờ phân công', 'assigned' => 'Đã phân công', 'in_progress' => 'Đang tư vấn', 'closed' => 'Đã kết thúc'])
    <div class="staff-scope">
        @if ($prefix === 'customer')
            <h1 class="staff-title mb-5">Yêu cầu tư vấn</h1>
        @endif
        <form method="GET" class="staff-filter" role="search" aria-label="Lọc hồ sơ tư vấn">
            <div class="staff-field">
                <label for="inquiry-status">Trạng thái</label>
                <select id="inquiry-status" name="status">
                    <option value="">Tất cả</option>
                    @foreach (\App\Enums\InquiryStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $statusLabels[$status->value] }}</option>
                    @endforeach
                </select>
            </div>
            <button class="staff-btn staff-btn--dark">Lọc</button>
            @if (request()->filled('status'))
                <a href="{{ url()->current() }}" class="staff-btn staff-btn--link">Xóa lọc</a>
            @endif
        </form>
        <div class="staff-table-wrap">
            <table class="staff-table staff-table--stack">
                <caption class="sr-only">Danh sách hồ sơ tư vấn</caption>
                <thead><tr><th scope="col">Hồ sơ</th><th scope="col">Xe khách chọn</th><th scope="col">Trạng thái</th><th scope="col">Phụ trách</th><th scope="col">Cập nhật</th><th scope="col"><span class="sr-only">Thao tác</span></th></tr></thead>
                <tbody>
                    @forelse ($inquiries as $inquiry)
                        <tr>
                            <td data-label="Hồ sơ"><a class="staff-strong text-red-700 underline" href="{{ route($prefix.'.inquiries.show', $inquiry) }}">Hồ sơ #{{ $inquiry->id }}</a></td>
                            <td data-label="Xe khách chọn"><x-staff.car :car="$inquiry->car" compact /></td>
                            <td data-label="Trạng thái"><x-staff.status :status="$inquiry->status" /></td>
                            <td data-label="Phụ trách">@if ($inquiry->assignedStaff){{ $inquiry->assignedStaff->name }}@else<span class="staff-sub">Chưa phân công</span>@endif</td>
                            <td data-label="Cập nhật" class="whitespace-nowrap">{{ $inquiry->updated_at?->format('H:i d/m/Y') }}</td>
                            <td><a href="{{ route($prefix.'.inquiries.show', $inquiry) }}" class="staff-btn staff-btn--secondary staff-btn--sm">Mở hồ sơ<span class="sr-only"> #{{ $inquiry->id }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="staff-empty">Chưa có yêu cầu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $inquiries->links() }}</div>
    </div>
@endsection
