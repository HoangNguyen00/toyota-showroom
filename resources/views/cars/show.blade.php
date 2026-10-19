@extends('layouts.app')
@section('title', $car->name.' | Toyota Showroom')
@section('content')
<div class="detail-page">
    <nav class="breadcrumbs" aria-label="Đường dẫn"><a href="{{ route('cars.index') }}">Dòng xe</a><span aria-hidden="true">/</span><span aria-current="page">{{ $car->name }}</span></nav>
    <section class="vehicle-overview" aria-labelledby="vehicle-title">
        <div class="vehicle-gallery" data-gallery>
            @php($images = $car->images->sortByDesc('is_primary')->sortBy('sort_order')->values())
            @php($primary = $images->first())
            <div class="gallery-stage">
                <span class="gallery-label">TOYOTA / {{ strtoupper($car->category?->name ?? $car->name) }}</span>
                <button type="button" class="gallery-open" data-gallery-open aria-label="Phóng to ảnh {{ $car->name }}">
                    @if ($primary)
                        <img src="{{ $primary->url() }}" alt="{{ $car->name }}" width="1100" height="600" fetchpriority="high">
                        <span class="gallery-zoom">Phóng to ↗</span>
                    @else
                        <span class="gallery-placeholder">Ảnh xe đang được cập nhật</span>
                    @endif
                </button>
            </div>
            <div class="gallery-caption"><span>{{ str_pad((string) max($images->count(), 1), 2, '0', STR_PAD_LEFT) }} ảnh</span><span>{{ $images->isEmpty() ? 'Showroom sẽ bổ sung ảnh' : 'Chạm ảnh để xem lớn hơn' }}</span></div>
            @foreach ($images->skip(1) as $image)
                <button class="gallery-thumb" type="button" data-gallery-open aria-label="Xem ảnh {{ $loop->iteration + 1 }} của {{ $car->name }}"><img src="{{ $image->url() }}" alt="" width="110" height="65"><span>Ảnh {{ $loop->iteration + 1 }}</span></button>
            @endforeach
        </div>
        <div class="vehicle-summary">
            <p class="eyebrow">KHÁM PHÁ TOYOTA</p>
            <h1 id="vehicle-title">{{ $car->name }}</h1>
            <p class="vehicle-version">{{ $car->category?->name }} · {{ $car->year }}</p>
            <span class="status-badge status-{{ $car->status->value }}">{{ \App\Support\Catalog\CarPresenter::STATUS_LABELS[$car->status->value] }}</span>
            <div class="vehicle-price"><span>Giá công bố tham khảo</span><p>{{ number_format((int) $car->price, 0, ',', '.') }} <small>₫</small></p><small>Giá tham khảo cho chiếc xe này, chưa phải giá lăn bánh.</small></div>
            <dl class="vehicle-facts">
                <div><dt>Mã chiếc xe</dt><dd>{{ $car->code }}</dd></div>
                <div><dt>Màu ngoại thất</dt><dd>{{ $car->color }}</dd></div>
                <div><dt>Năm sản xuất</dt><dd>{{ $car->year }}</dd></div>
            </dl>
            @if ($car->status === \App\Enums\CarStatus::Available)
                <div class="detail-actions"><button class="button button-red" type="button" data-preview="Tư vấn {{ $car->name }}">Yêu cầu tư vấn</button><button class="button" type="button" data-preview="Đặt lịch xem {{ $car->name }}">Đặt lịch xem xe</button></div>
                <p class="availability-note">Gửi yêu cầu đặt lịch không đồng nghĩa xe đã được giữ chỗ.</p>
            @elseif ($car->status === \App\Enums\CarStatus::Reserved)
                <div class="detail-actions"><button class="button button-red" type="button" data-preview="Tư vấn {{ $car->name }}">Yêu cầu tư vấn</button><button class="button" type="button" disabled>Chưa nhận lịch xem</button></div>
                <p class="availability-note">Chiếc xe này đang được giữ chỗ. Bạn vẫn có thể liên hệ tư vấn.</p>
            @else
                <div class="detail-actions"><a href="{{ route('cars.index') }}" class="button button-dark">Khám phá xe khác ↗</a></div>
                <p class="availability-note">Chiếc xe này đã bán. Các xe khác cùng dòng có thể còn khả dụng.</p>
            @endif
        </div>
    </section>
    <nav class="detail-section-nav" aria-label="Nội dung chi tiết"><a href="#tong-quan">Tổng quan</a><a href="#thong-so">Thông số</a></nav>
    <section class="detail-intro" id="tong-quan"><p class="eyebrow">MỖI HÀNH TRÌNH, MỘT LỰA CHỌN</p><h2>Tìm hiểu chiếc xe<br>đồng hành cùng bạn.</h2><div><p>Khám phá hình ảnh và thông tin của {{ $car->name }} trước khi đến showroom. Nhân viên sẽ hỗ trợ kiểm tra trang bị và tình trạng thực tế khi tiếp nhận yêu cầu.</p><p class="demo-note">Giá và tình trạng trên trang được cập nhật bởi showroom; thông số chưa niêm yết hiển thị “Đang cập nhật”.</p></div></section>
    <section class="detail-specifications" id="thong-so" aria-labelledby="spec-title">
        <div><p class="eyebrow">THÔNG TIN XE</p><h2 id="spec-title">Chi tiết, rõ ràng.</h2></div>
        <dl>
            <div><dt>Dòng xe</dt><dd>{{ $car->category?->name ?? 'Toyota' }}</dd></div>
            @forelse (($car->specs ?? []) as $spec)
                <div><dt>{{ $spec['label'] ?? '' }}</dt><dd>{{ $spec['value'] ?? '' }}</dd></div>
            @empty
                <div><dt>Thông số</dt><dd>Đang cập nhật — liên hệ showroom để xác nhận</dd></div>
            @endforelse
        </dl>
    </section>
</div>
@if ($primary)
    <dialog class="image-dialog" aria-labelledby="image-dialog-title" data-image-dialog><form method="dialog"><button class="image-close button" aria-label="Đóng ảnh">Đóng ×</button></form><h2 class="sr-only" id="image-dialog-title">{{ $car->name }}</h2><img src="{{ $primary->url() }}" alt="{{ $car->name }}" width="1100" height="600"><p>{{ $car->name }} · {{ $car->code }}</p></dialog>
@endif
@endsection
