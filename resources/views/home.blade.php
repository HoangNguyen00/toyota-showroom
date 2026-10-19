@extends('layouts.app')
@section('title', 'Toyota Showroom | Tinh tế trong từng chuyển động')
@section('content')
<section class="camry-story" data-camry-story aria-labelledby="home-title">
    <div class="camry-sticky">
        <div class="camry-airflow" aria-hidden="true">
            <svg class="camry-wind-field" viewBox="0 0 1440 812" fill="none" preserveAspectRatio="none" width="1440" height="812">
                <defs>
                    <linearGradient id="wind-color" x1="0" y1="0" x2="1440" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#89def4" stop-opacity="0"/><stop offset=".18" stop-color="#c3f1ff"/><stop offset=".72" stop-color="#82d9f0"/><stop offset="1" stop-color="#89def4" stop-opacity="0"/></linearGradient>
                </defs>
                <g class="wind-flow wind-flow-upper">
                    <path d="M-100 475 C110 475 200 456 320 390 S525 258 710 258 S1030 299 1160 318 S1350 336 1540 310"/>
                    <path d="M-100 510 C120 510 225 477 345 414 S535 288 720 288 S1030 329 1170 346 S1370 361 1540 338"/>
                    <path d="M-100 546 C135 546 240 510 370 448 S545 319 730 319 S1045 359 1180 375 S1390 389 1540 365"/>
                </g>
                <g class="wind-travel">
                    <path d="M-100 475 C110 475 200 456 320 390 S525 258 710 258 S1030 299 1160 318 S1350 336 1540 310"/>
                    <path d="M-100 510 C120 510 225 477 345 414 S535 288 720 288 S1030 329 1170 346 S1370 361 1540 338"/>
                    <path d="M-100 546 C135 546 240 510 370 448 S545 319 730 319 S1045 359 1180 375 S1390 389 1540 365"/>
                </g>
            </svg>
        </div>
        <div class="camry-road" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="camry-scanner" aria-hidden="true"><span></span></div>
        <div class="camry-display-floor" aria-hidden="true"></div>
        <div class="stage-eyebrow"><p>MỘT GÓC NHÌN MỚI VỀ TOYOTA</p><span>DESIGN IN EVERY DETAIL</span></div>
        <h1 id="home-title" class="camry-word">TOYOTA</h1>
        <div class="camry-visual">
            <img class="camry-poster" data-camry-poster src="{{ asset('assets/collection/camry.png') }}" alt="Toyota Camry, góc nhìn phía trước" width="1672" height="941" fetchpriority="high">
            <canvas class="camry-canvas" data-camry-canvas width="960" height="540" aria-hidden="true"></canvas>
        </div>
        <video class="camry-source" data-camry-video muted playsinline preload="auto" aria-hidden="true" tabindex="-1"><source src="{{ asset('assets/home/camry-exploded.mp4') }}" type="video/mp4"></video>
        <div class="camry-caption"><h2>Toyota Camry</h2><p>Tinh tế trong từng chuyển động.</p></div>
        <div class="camry-actions"><button class="button button-dark" type="button" data-preview="Đặt lịch xem Toyota Camry">Đặt lịch xem xe</button><a class="button" href="{{ route('cars.preview.show', 1) }}">Khám phá Camry</a></div>
        <aside class="camry-detail-copy" data-camry-copy aria-hidden="true"><p class="eyebrow">TỪNG CHI TIẾT, MỘT GÓC NHÌN</p><h2>Khám phá<br>từ bên trong.</h2><p>Cuộn để xem các bộ phận tách mở.<br>Cuộn ngược để trở về hình dáng ban đầu.</p><small>Hình ảnh mô phỏng, không dùng để xác nhận cấu tạo hoặc thông số xe.</small></aside>
        <div class="camry-bottom"><a class="scroll-cue" href="#dich-vu">Cuộn để khám phá <span aria-hidden="true">↓</span></a><span data-camry-phase>01 — NGOẠI THẤT</span></div>
        <div class="camry-progress" aria-hidden="true"><span data-camry-progress></span></div>
        <p class="camry-fallback" data-camry-fallback hidden>Đang hiển thị ảnh thay thế. Bạn vẫn có thể khám phá xe và các dịch vụ bên dưới.</p>
        <noscript><style>.camry-story{height:auto}.camry-sticky{position:relative}.camry-canvas{display:none}</style></noscript>
    </div>
</section>
<section class="home-details" aria-label="Khám phá thiết kế Toyota">
    <article class="home-detail" data-home-reveal>
        <div class="home-detail-photo home-detail-photo--light">
            <img src="{{ asset('assets/home/toyota-headlight.jpg') }}" alt="Góc đầu xe Toyota với cụm đèn đang sáng" width="2832" height="4256" loading="lazy" decoding="async">
            <span class="home-detail-index" aria-hidden="true">01 / NGOẠI THẤT</span>
        </div>
        <div class="home-detail-copy">
            <p class="eyebrow">DẤU ẤN THIẾT KẾ</p>
            <h2>Ấn tượng từ<br>ánh nhìn đầu tiên.</h2>
            <p>Những đường nét, những góc nhìn. Khám phá chiếc Toyota mang dấu ấn của riêng bạn.</p>
            <a class="home-detail-link" href="{{ route('cars.index') }}">Khám phá các dòng xe <span aria-hidden="true">↗</span></a>
        </div>
    </article>
    <article class="home-detail home-detail--reverse" data-home-reveal>
        <div class="home-detail-photo home-detail-photo--wheel">
            <img src="{{ asset('assets/home/toyota-steering-wheel.jpg') }}" alt="Cận cảnh vô lăng và khoang lái Toyota" width="4000" height="5334" loading="lazy" decoding="async">
            <span class="home-detail-index" aria-hidden="true">02 / KHOANG LÁI</span>
        </div>
        <div class="home-detail-copy">
            <p class="eyebrow">CHẠM VÀO TRẢI NGHIỆM</p>
            <h2>Cảm hứng<br>sau tay lái.</h2>
            <p>Có những cảm nhận cần được trải nghiệm trực tiếp. Hẹn gặp bạn tại showroom để tìm hiểu chiếc xe mình yêu thích.</p>
            <button class="home-detail-link" type="button" data-preview="Đặt lịch đến showroom">Đặt lịch xem xe <span aria-hidden="true">↗</span></button>
            <small>Hình ảnh minh họa trải nghiệm Toyota.</small>
        </div>
    </article>
</section>
<section class="home-services" id="dich-vu" aria-labelledby="services-title"><div><p class="eyebrow">ĐỒNG HÀNH CÙNG BẠN</p><h2 id="services-title">An tâm.<br>Trên mọi hành trình.</h2><p>Từ lựa chọn xe đến chăm sóc sau bán hàng.</p></div><div class="home-service-links"><button type="button" data-preview="Bảo dưỡng định kỳ"><span>01</span>Bảo dưỡng định kỳ <i aria-hidden="true">→</i></button><button type="button" data-preview="Chính sách bảo hành"><span>02</span>Chính sách bảo hành <i aria-hidden="true">→</i></button><button type="button" data-preview="Tư vấn tại showroom"><span>03</span>Tư vấn tại showroom <i aria-hidden="true">→</i></button><a class="button button-dark" href="{{ route('cars.index') }}">Khám phá các dòng xe</a></div></section>
<section class="home-showroom" id="showroom" aria-labelledby="showroom-title"><div><p class="eyebrow">HẸN GẶP TẠI SHOWROOM</p><h2 id="showroom-title">Cảm nhận bằng<br>chính trải nghiệm của bạn.</h2></div><button type="button" class="button button-dark" data-preview="Đặt lịch đến showroom">Đặt lịch xem xe</button></section>
@endsection
