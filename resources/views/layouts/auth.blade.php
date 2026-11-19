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
        <aside class="auth-visual" aria-label="Toyota trên hành trình">
            @php
                $authVideoPath = 'storage/site-media/camry-driving.mp4';
                $authPosterPath = 'storage/site-media/camry-driving-poster.jpg';
                $hasAuthVideo = is_file(public_path($authVideoPath));
                $hasAuthPoster = is_file(public_path($authPosterPath));
            @endphp
            <img src="{{ asset($hasAuthPoster ? $authPosterPath : 'assets/home/toyota-driving.jpg') }}" alt="Xe Toyota Camry đang chạy trên đường" width="{{ $hasAuthPoster ? 1920 : 6000 }}" height="{{ $hasAuthPoster ? 1080 : 3487 }}" fetchpriority="high">
            @if($hasAuthVideo)
                <video class="auth-driving-video" data-auth-video muted loop playsinline preload="none" aria-hidden="true" tabindex="-1" disablepictureinpicture><source src="{{ asset($authVideoPath) }}" type="video/mp4"></video>
                <button type="button" class="auth-video-toggle" data-auth-video-toggle hidden aria-label="Phát video xe chạy">Phát video</button>
            @endif
            <a class="auth-brand" href="{{ route('home') }}" aria-label="Toyota — Trang chủ">TOYOTA<span>SHOWROOM</span></a>
            <div class="auth-story"><p>CHUYỂN ĐỘNG CÙNG TOYOTA</p><h2>Khởi đầu<br> hành trình của bạn.</h2><span>Mỗi hành trình, một dấu ấn riêng.</span></div>
            <span class="auth-image-caption">TOYOTA / ON THE MOVE</span>
        </aside>
        <main class="auth-panel" id="auth-main">
            <a class="auth-back" href="{{ route('home') }}"><span aria-hidden="true">←</span> Về trang chủ</a>
            <div class="auth-form-wrap">
                <p class="auth-kicker">CHÀO MỪNG ĐẾN VỚI TOYOTA</p>
                @if (session('status'))<div role="status" class="auth-notice">{{ session('status') }}</div>@endif
                @if (session('account_error'))<div role="alert" class="auth-notice auth-notice--error">{{ session('account_error') }}</div>@endif
                @yield('content')
            </div>
            <footer class="auth-footer">Đồ án môn học · Không phải website đại lý Toyota chính thức.</footer>
        </main>
    </div>
</body>
</html>
