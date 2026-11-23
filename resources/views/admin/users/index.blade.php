@extends('layouts.staff')
@section('title', 'Quản lý tài khoản')
@section('lead', 'Tạo nhân viên, khóa hoặc mở tài khoản. Không xóa tài khoản.')
@section('actions')
    <a href="{{ route('admin.users.create') }}" class="staff-btn staff-btn--primary">Tạo nhân viên</a>
@endsection
@section('content')
    @if ($errors->any())
        <div role="alert" class="staff-alert staff-alert--error"><ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
    @endif
    <form method="GET" action="{{ route('admin.users.index') }}" class="staff-filter" role="search" aria-label="Lọc tài khoản">
        <div class="staff-field staff-field--grow">
            <label for="q">Tên hoặc email</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
        </div>
        <div class="staff-field">
            <label for="role">Vai trò</label>
            <select id="role" name="role">
                <option value="">Tất cả</option>
                @foreach (['customer'=>'Khách hàng', 'sales'=>'Nhân viên', 'admin'=>'Admin'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="staff-btn staff-btn--dark" type="submit">Tìm</button>
        <a href="{{ route('admin.users.index') }}" class="staff-btn staff-btn--link">Xóa lọc</a>
    </form>
    <div class="staff-table-wrap">
        <table class="staff-table staff-table--stack">
            <caption class="sr-only">Danh sách tài khoản trong hệ thống</caption>
            <thead><tr><th scope="col">Người dùng</th><th scope="col">Vai trò</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td data-label="Người dùng"><p class="staff-strong">{{ $user->name }}</p><p class="break-all">{{ $user->email }}</p><p class="staff-sub">{{ $user->phone }}</p></td>
                        <td data-label="Vai trò">{{ match($user->role) { \App\Enums\UserRole::Admin => 'Admin', \App\Enums\UserRole::Sales => 'Nhân viên', default => 'Khách hàng' } }}</td>
                        <td data-label="Trạng thái"><x-staff.status :tone="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}</x-staff.status></td>
                        <td data-label="Thao tác">
                            @can('updateStatus', $user)
                                <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                    <button type="submit" @class(['staff-btn staff-btn--sm', 'staff-btn--danger' => $user->is_active, 'staff-btn--secondary' => ! $user->is_active])>{{ $user->is_active ? 'Khóa tài khoản' : 'Mở tài khoản' }}</button>
                                </form>
                            @else
                                <span class="staff-sub">Không thay đổi admin</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="staff-empty">Không có tài khoản phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
    <p class="staff-footnote">Khóa tài khoản giữ lại lịch sử và người phụ trách hồ sơ. Không xóa tài khoản.</p>
@endsection
