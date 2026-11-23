@extends('layouts.staff')
{{-- Chỉ sales/admin tới đây (AccountController trả customer.account cho khách). --}}
@php($user = auth()->user())
@php($isAdmin = $user->role === \App\Enums\UserRole::Admin)
@section('title', 'Tài khoản của tôi')
@section('heading', 'Xin chào, '.$user->name)
@section('lead', $isAdmin ? 'Theo dõi tư vấn, lịch hẹn và quản lý catalog, dịch vụ, tài khoản.' : 'Xử lý hồ sơ tư vấn và lịch hẹn được phân công cho bạn.')
@section('content')
    <div class="staff-overview">
        <section class="staff-card" aria-labelledby="profile-title">
            <h2 id="profile-title" class="staff-card-title">Thông tin tài khoản</h2>
            <dl class="staff-facts">
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Số điện thoại</dt><dd @class(['is-empty' => ! $user->phone])>{{ $user->phone ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Vai trò</dt><dd>{{ $isAdmin ? 'Quản trị viên' : 'Nhân viên tư vấn' }}</dd></div>
                <div><dt>Trạng thái</dt><dd><x-staff.status tone="success">Đang hoạt động</x-staff.status></dd></div>
            </dl>
        </section>
        <section class="staff-card" aria-labelledby="shortcut-title">
            <h2 id="shortcut-title" class="staff-card-title">Lối tắt công việc</h2>
            <div class="staff-shortcuts">
                @can('viewAny', \App\Models\Inquiry::class)
                    <a class="staff-shortcut" href="{{ route($isAdmin ? 'admin.inquiries.index' : 'staff.inquiries.index') }}"><x-customer-icon name="chat"/><span>Tư vấn và tin nhắn<small>{{ $isAdmin ? 'Phân công và theo dõi hồ sơ' : 'Hồ sơ bạn phụ trách' }}</small></span></a>
                @endcan
                <a class="staff-shortcut" href="{{ route('staff.appointments.index') }}"><x-customer-icon name="calendar"/><span>Lịch hẹn<small>{{ $isAdmin ? 'Phân công, xác nhận, hủy' : 'Xác nhận và kết thúc lịch' }}</small></span></a>
                @can('viewAny', \App\Models\Car::class)
                    <a class="staff-shortcut" href="{{ route('admin.cars.index') }}"><x-customer-icon name="car"/><span>Xe<small>Từng chiếc, giá, ảnh, hiển thị</small></span></a>
                @endcan
                @can('viewAny', \App\Models\CarCategory::class)
                    <a class="staff-shortcut" href="{{ route('admin.car-categories.index') }}"><x-customer-icon name="tag"/><span>Dòng xe<small>Dòng Toyota công khai</small></span></a>
                @endcan
                @can('viewAny', \App\Models\Service::class)
                    <a class="staff-shortcut" href="{{ route('admin.services.index') }}"><x-customer-icon name="tool"/><span>Dịch vụ<small>Bảo dưỡng, bảo hành</small></span></a>
                @endcan
                @can('viewAny', \App\Models\User::class)
                    <a class="staff-shortcut" href="{{ route('admin.users.index') }}"><x-customer-icon name="users"/><span>Tài khoản<small>Nhân viên, khóa/mở tài khoản</small></span></a>
                @endcan
            </div>
        </section>
    </div>
@endsection
