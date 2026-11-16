@extends('layouts.app')
@section('title', $car['name'].' | Toyota Showroom')
@section('content')
<div class="detail-page">
    <nav class="breadcrumbs" aria-label="Đường dẫn"><a href="{{ route('cars.preview') }}">Dòng xe</a><span aria-hidden="true">/</span><span aria-current="page">{{ $car['name'] }}</span></nav>
    <section class="vehicle-overview" aria-labelledby="vehicle-title">
        <div class="vehicle-gallery" data-gallery>
            <div class="gallery-stage">
                <span class="gallery-label">TOYOTA / {{ strtoupper(str_replace('Toyota ', '', $car['name'])) }}</span>
                <button type="button" class="gallery-open" data-gallery-open aria-label="Phóng to ảnh {{ $car['name'] }}">
                    <img src="{{ asset($car['image_path'] ?? 'assets/collection/'.$car['image']) }}" alt="{{ $car['name'] }} — ảnh minh họa" width="1100" height="600" fetchpriority="high">
                    <span class="gallery-zoom">Phóng to ↗</span>
                </button>
            </div>
            <div class="gallery-caption"><span>01 / 01 · Ảnh minh họa</span><span>Chạm ảnh để xem lớn hơn</span></div>
            <button class="gallery-thumb" type="button" data-gallery-open aria-label="Xem ảnh toàn cảnh {{ $car['name'] }}"><img src="{{ asset($car['image_path'] ?? 'assets/collection/'.$car['image']) }}" alt="" width="110" height="65"><span>Toàn cảnh</span></button>
        </div>
        <div class="vehicle-summary">
            <p class="eyebrow">KHÁM PHÁ TOYOTA</p>
            <h1 id="vehicle-title">{{ $car['name'] }}</h1>
            <p class="vehicle-version">{{ $car['version'] }} · {{ $car['year'] }}</p>
            <span class="status-badge status-{{ $car['status'] }}">{{ $car['status_label'] }}</span>
            <div class="vehicle-price"><span>Giá công bố tham khảo</span><p>{{ number_format($car['price'], 0, ',', '.') }} <small>₫</small></p><small>Giá minh họa cho giao diện, chưa phải giá lăn bánh.</small></div>
            <dl class="vehicle-facts"><div><dt>Mã chiếc xe</dt><dd>{{ $car['code'] }}</dd></div><div><dt>Màu ngoại thất</dt><dd>{{ $car['color'] }} <span class="color-swatch {{ $car['color'] === 'Xám' ? 'swatch-gray' : '' }}" aria-hidden="true"></span></dd></div><div><dt>Năm sản xuất</dt><dd>{{ $car['year'] }} <small>(minh họa)</small></dd></div></dl>
            @if ($car['status'] === 'available')
                <div class="detail-actions"><a class="button button-red" href="{{ route('appointments.create', ['car' => $car['id'] ?? null]) }}">Đặt lịch xem xe</a><a class="button" href="{{ route('cars.preview.show', $car['id']) }}">Khám phá thêm</a></div>
                <p class="availability-note">Gửi yêu cầu đặt lịch không đồng nghĩa xe đã được giữ chỗ.</p>
            @elseif ($car['status'] === 'reserved')
                <div class="detail-actions"><button class="button button-red" type="button" data-preview="Tư vấn {{ $car['name'] }}">Yêu cầu tư vấn</button><button class="button" type="button" disabled>Chưa nhận lịch xem</button></div>
                <p class="availability-note">Chiếc xe này đang được giữ chỗ. Bạn vẫn có thể liên hệ tư vấn.</p>
            @else
                <div class="detail-actions"><a href="{{ route('cars.preview') }}" class="button button-dark">Khám phá xe khác ↗</a></div>
                <p class="availability-note">Chiếc xe này đã bán. Các xe khác cùng dòng có thể còn khả dụng.</p>
            @endif
        </div>
    </section>
    <nav class="detail-section-nav" aria-label="Nội dung chi tiết"><a href="#tong-quan">Tổng quan</a><a href="#thong-so">Thông số</a><a href="#xe-lien-quan">Xe liên quan</a></nav>
    <section class="detail-intro" id="tong-quan"><p class="eyebrow">MỖI HÀNH TRÌNH, MỘT LỰA CHỌN</p><h2>Tìm hiểu chiếc xe<br>đồng hành cùng bạn.</h2><div><p>Khám phá hình ảnh và thông tin của {{ $car['name'] }} trước khi đến showroom. Nhân viên sẽ hỗ trợ kiểm tra phiên bản, trang bị và tình trạng thực tế khi tiếp nhận yêu cầu.</p><p class="demo-note">Đây là bản mẫu giao diện. Tên phiên bản, giá, năm và tình trạng là dữ liệu minh họa; chưa phải thông tin sản phẩm được showroom xác nhận.</p></div></section>
    <section class="detail-specifications" id="thong-so" aria-labelledby="spec-title"><div><p class="eyebrow">THÔNG TIN XE</p><h2 id="spec-title">Chi tiết, rõ ràng.</h2><p>Thông số chính thức sẽ được bổ sung từ dữ liệu danh mục xe.</p></div><dl><div><dt>Dòng xe</dt><dd>{{ $car['name'] }}</dd></div><div><dt>Phiên bản</dt><dd>{{ $car['version'] }}</dd></div><div><dt>Động cơ</dt><dd>Đang cập nhật</dd></div><div><dt>Hộp số</dt><dd>Đang cập nhật</dd></div><div><dt>Kích thước</dt><dd>Đang cập nhật</dd></div><div><dt>Trang bị và bảo hành</dt><dd>Liên hệ showroom để xác nhận</dd></div></dl></section>
    <section class="related-cars" id="xe-lien-quan" aria-labelledby="related-title"><div class="related-heading"><div><p class="eyebrow">THÊM NHỮNG LỰA CHỌN</p><h2 id="related-title">Khám phá xe khác.</h2></div><a href="{{ route('cars.preview') }}">Tất cả dòng xe ↗</a></div><div class="car-grid">@foreach ($related as $relatedCar)<x-car-card :car="$relatedCar" />@endforeach</div></section>
</div>
<dialog class="image-dialog" aria-labelledby="image-dialog-title" data-image-dialog><form method="dialog"><button class="image-close button" aria-label="Đóng ảnh">Đóng ×</button></form><h2 class="sr-only" id="image-dialog-title">{{ $car['name'] }} — ảnh toàn cảnh</h2><img src="{{ asset($car['image_path'] ?? 'assets/collection/'.$car['image']) }}" alt="Ảnh minh họa {{ $car['name'] }}" width="1100" height="600"><p>{{ $car['name'] }} · Ảnh minh họa</p></dialog>
@endsection
