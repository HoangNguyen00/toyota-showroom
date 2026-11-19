<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tài khoản của tôi') | Toyota Showroom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="customer-page">
    <a class="skip-link" href="#customer-main">Đi đến nội dung</a>
    <header class="customer-header">
        <a href="{{ route('home') }}" class="customer-brand" aria-label="Toyota — Trang chủ">TOYOTA <span>SHOWROOM</span></a>
        <div class="customer-header-actions"><a class="customer-home-link" href="{{ route('home') }}">Về trang chủ <x-customer-icon name="arrow"/></a><form class="customer-mobile-logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" aria-label="Đăng xuất"><x-customer-icon name="logout"/></button></form></div>
    </header>
    <div class="customer-shell">
        <aside class="customer-sidebar" aria-label="Khu vực khách hàng">
            <div class="customer-identity"><span class="customer-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><span>Tài khoản khách hàng</span></div></div>
            <p class="customer-nav-label">KHÔNG GIAN CỦA BẠN</p>
            <nav class="customer-nav" aria-label="Điều hướng tài khoản">
                <a href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif><x-customer-icon name="grid"/> Tổng quan</a>
                @if(Route::has('customer.chat.index'))
                    <a href="{{ route('customer.chat.index') }}" @if(request()->routeIs('customer.chat.index')) aria-current="page" @endif><x-customer-icon name="chat"/> Tin nhắn</a>
                @endif
                <a href="{{ route('customer.appointments.index') }}" @if(request()->routeIs('customer.appointments.*')) aria-current="page" @endif><x-customer-icon name="calendar"/> Lịch hẹn</a>
                <a href="{{ url('/xe') }}"><x-customer-icon name="car"/> Khám phá xe</a>
            </nav>
            <div class="customer-sidebar-bottom"><x-customer-icon name="shield"/><p>Không gian riêng cho<br>hành trình của bạn.</p><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-customer-icon name="logout"/> Đăng xuất</button></form></div>
        </aside>
        <main id="customer-main" class="customer-main" tabindex="-1">
            @if(session('status'))<p class="customer-flash" role="status">{{ session('status') }}</p>@endif
            @yield('content')
        </main>
    </div>
    <footer class="customer-footer">TOYOTA SHOWROOM <span>Đồ án môn học — không phải website đại lý Toyota chính thức.</span></footer>
</body>
</html>
