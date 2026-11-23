{{-- Layout nghiệp vụ chung cho sales và admin. Menu chỉ là điều hướng: mỗi route vẫn được
     middleware role + Policy kiểm tra phía server; @can ở đây dùng đúng Policy đó để ẩn mục không có quyền. --}}
@php
    $staffUser = auth()->user();
    $isAdmin = $staffUser->role === \App\Enums\UserRole::Admin;
    $roleLabel = $isAdmin ? 'Quản trị viên' : 'Nhân viên tư vấn';
    $inquiryRoute = $isAdmin ? 'admin.inquiries' : 'staff.inquiries';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Toyota Showroom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="staff-page">
    <a class="staff-skip" href="#staff-main">Đi đến nội dung</a>
    <header class="staff-header">
        <a href="{{ route('account') }}" class="staff-brand" aria-label="Toyota Showroom — Tổng quan {{ $isAdmin ? 'quản trị' : 'tư vấn' }}">TOYOTA <span>{{ $isAdmin ? 'QUẢN TRỊ' : 'TƯ VẤN' }}</span></a>
        <div class="staff-user">
            <div class="staff-user-name"><strong>{{ $staffUser->name }}</strong><span>{{ $roleLabel }}</span></div>
            <span class="staff-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($staffUser->name, 0, 1)) }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="staff-logout"><x-customer-icon name="logout"/><span>Đăng xuất</span></button>
            </form>
        </div>
    </header>
    <div class="staff-shell">
        <nav class="staff-sidebar" aria-label="Điều hướng {{ $isAdmin ? 'quản trị' : 'nhân viên' }}">
            <div class="staff-nav-group staff-nav">
                <p class="staff-nav-label">VẬN HÀNH</p>
                <a href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif><x-customer-icon name="grid"/> Tổng quan</a>
                @can('viewAny', \App\Models\Inquiry::class)
                    <a href="{{ route($inquiryRoute.'.index') }}" @if(request()->routeIs($inquiryRoute.'.*')) aria-current="page" @endif><x-customer-icon name="chat"/> Tư vấn</a>
                @endcan
                <a href="{{ route('staff.appointments.index') }}" @if(request()->routeIs('staff.appointments.*')) aria-current="page" @endif><x-customer-icon name="calendar"/> Lịch hẹn</a>
            </div>
            @if ($isAdmin)
                <div class="staff-nav-group staff-nav">
                    <p class="staff-nav-label">CATALOG</p>
                    @can('viewAny', \App\Models\Car::class)
                        <a href="{{ route('admin.cars.index') }}" @if(request()->routeIs('admin.cars.*')) aria-current="page" @endif><x-customer-icon name="car"/> Xe</a>
                    @endcan
                    @can('viewAny', \App\Models\CarCategory::class)
                        <a href="{{ route('admin.car-categories.index') }}" @if(request()->routeIs('admin.car-categories.*')) aria-current="page" @endif><x-customer-icon name="tag"/> Dòng xe</a>
                    @endcan
                    @can('viewAny', \App\Models\Service::class)
                        <a href="{{ route('admin.services.index') }}" @if(request()->routeIs('admin.services.*')) aria-current="page" @endif><x-customer-icon name="tool"/> Dịch vụ</a>
                    @endcan
                </div>
                @can('viewAny', \App\Models\User::class)
                    <div class="staff-nav-group staff-nav">
                        <p class="staff-nav-label">HỆ THỐNG</p>
                        <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><x-customer-icon name="users"/> Tài khoản</a>
                    </div>
                @endcan
            @endif
            <div class="staff-sidebar-bottom">
                <a href="{{ route('home') }}"><x-customer-icon name="home"/> Xem trang khách</a>
            </div>
        </nav>
        <main id="staff-main" class="staff-main" tabindex="-1">
            <div class="staff-page-head">
                <div>
                    @yield('back')
                    <p class="staff-eyebrow">@yield('eyebrow', $isAdmin ? 'Quản trị showroom' : 'Khu vực tư vấn')</p>
                    <h1 class="staff-title">
                        @hasSection('heading')
                            @yield('heading')
                        @else
                            @yield('title')
                        @endif
                    </h1>
                    @hasSection('lead')
                        <p class="staff-lead">@yield('lead')</p>
                    @endif
                </div>
                @hasSection('actions')
                    <div class="staff-actions">@yield('actions')</div>
                @endif
            </div>
            @if (session('status'))
                <div role="status" class="staff-alert staff-alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div role="alert" class="staff-alert staff-alert--error">{{ session('error') }}</div>
            @endif
            @if (session('account_error'))
                <div role="alert" class="staff-alert staff-alert--error">{{ session('account_error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    <footer class="staff-footer"><span>TOYOTA SHOWROOM</span></footer>
</body>
</html>
