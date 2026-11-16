<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Toyota Showroom')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['home-page' => request()->routeIs('home')])>
    <a class="skip-link" href="#main-content">Đi đến nội dung</a>
    <header class="site-header">
        <a class="wordmark" href="{{ url('/') }}" aria-label="Toyota — Trang chủ">TOYOTA</a>
        <button class="menu-toggle button" type="button" aria-controls="main-nav" aria-expanded="false">Menu <span aria-hidden="true">☰</span></button>
        <nav class="main-nav" id="main-nav" aria-label="Điều hướng chính">
            <a href="{{ url('/xe') }}" @if(request()->is('xe', 'xe/*')) aria-current="page" @endif>Dòng xe</a>
            <a href="{{ url('/').'#dich-vu' }}">Dịch vụ</a>
            <a href="{{ url('/').'#showroom' }}">Showroom</a>
            <a href="#lien-he">Liên hệ</a>
            @auth
                @if (auth()->user()->role === \App\Enums\UserRole::Customer)
                    <a href="{{ route('customer.appointments.index') }}" @if(request()->is('lich-hen')) aria-current="page" @endif>Lịch hẹn của tôi</a>
                @else
                    <a href="{{ route('staff.appointments.index') }}" @if(request()->is('staff/appointments')) aria-current="page" @endif>Lịch hẹn</a>
                @endif
            @endauth
        </nav>
        <div class="header-account">
            @auth
                <a class="login-link" href="{{ route('account') }}">Tài khoản</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="login-link">Đăng xuất</button>
                </form>
            @else
                <a class="login-link" href="{{ route('login') }}">Đăng nhập</a>
            @endauth
            <button type="button" class="button button-red" data-preview="Tư vấn">Tư vấn</button>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="site-footer" id="lien-he">
        <a class="wordmark" href="{{ url('/') }}" aria-label="Toyota — Trang chủ">TOYOTA</a>
        <nav aria-label="Điều hướng cuối trang">
            <a href="{{ url('/xe') }}">Dòng xe</a>
            <a href="{{ url('/').'#dich-vu' }}">Dịch vụ</a>
            <a href="{{ url('/').'#showroom' }}">Showroom</a>
        </nav>
        <div><p>Toyota Showroom</p><small>Đồ án môn học · Không phải website đại lý chính thức</small></div>
    </footer>
    <dialog class="preview-dialog" aria-labelledby="preview-title" aria-describedby="preview-description">
        <form method="dialog"><button class="dialog-close" aria-label="Đóng thông báo">×</button></form>
        <p class="eyebrow">TOYOTA SHOWROOM</p>
        <h2 id="preview-title">Bản xem trước giao diện</h2>
        <p id="preview-description"></p>
        <form method="dialog"><button class="button button-dark">Đã hiểu</button></form>
    </dialog>
</body>
</html>
