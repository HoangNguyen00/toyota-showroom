<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Toyota Showroom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <a class="skip-link" href="#auth-main">Đi đến biểu mẫu</a>
    <div class="auth-shell">
        <div class="auth-backdrop" aria-hidden="true">
            @php
                $authVideoPath = 'storage/site-media/camry-driving.mp4';
                $authPosterPath = 'storage/site-media/camry-driving-poster.jpg';
                $hasAuthVideo = is_file(public_path($authVideoPath));
                $hasAuthPoster = is_file(public_path($authPosterPath));
            @endphp
            <img src="{{ asset($hasAuthPoster ? $authPosterPath : 'assets/home/toyota-driving.jpg') }}" alt="" width="{{ $hasAuthPoster ? 1920 : 6000 }}" height="{{ $hasAuthPoster ? 1080 : 3487 }}" fetchpriority="high">
            @if($hasAuthVideo)
                <video class="auth-driving-video" data-auth-video autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1" disablepictureinpicture disableremoteplayback><source src="{{ asset($authVideoPath) }}" type="video/mp4"></video>
            @endif
        </div>
        <header class="auth-header">
            <a class="auth-brand" href="{{ route('home') }}" aria-label="Toyota — Trang chủ">TOYOTA<span>SHOWROOM</span></a>
            <a class="auth-back" href="{{ route('home') }}"><span aria-hidden="true">←</span> Về trang chủ</a>
        </header>
        <div class="auth-story"><p>CHUYỂN ĐỘNG CÙNG TOYOTA</p><h2>Khởi đầu<br> hành trình của bạn.</h2><span>Mỗi hành trình, một dấu ấn riêng.</span></div>
        <main class="auth-panel" id="auth-main">
            <div class="auth-form-wrap">
                <p class="auth-kicker">CHÀO MỪNG ĐẾN VỚI TOYOTA</p>
                @if (session('status'))<div role="status" class="auth-notice">{{ session('status') }}</div>@endif
                @if (session('account_error'))<div role="alert" class="auth-notice auth-notice--error">{{ session('account_error') }}</div>@endif
                @yield('content')
            </div>
        </main>
        <footer class="auth-footer"><span>TOYOTA / ON THE MOVE</span><span>Đồ án môn học · Không phải website đại lý Toyota chính thức.</span></footer>
    </div>
</body>
</html>
