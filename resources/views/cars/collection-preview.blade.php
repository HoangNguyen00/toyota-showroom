@extends('layouts.app')
@section('title', 'Dòng xe (bản mẫu) | Toyota Showroom')
@section('content')
<section class="collection-hero" aria-label="Khám phá các dòng xe" aria-roledescription="bộ trình chiếu" tabindex="0" data-slider>
    <div class="stage-eyebrow"><h1>KHÁM PHÁ CÁC DÒNG XE</h1><span>TOYOTA / COLLECTION</span></div>
    <div class="car-stage">
        <div class="model-title-window" aria-hidden="true">
            @foreach ($slides as $slide)
                <p class="model-backdrop {{ $loop->first ? 'model-long is-title-active' : '' }}" data-model-title>{{ $slide['headline'] }}</p>
            @endforeach
        </div>
        @foreach ($slides as $slide)
            <div class="car-slide {{ $loop->first ? 'is-active' : '' }}" data-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}" style="--slide-offset: {{ $loop->index * 115 }}%">
                <img src="{{ asset('assets/collection/'.$slide['image']) }}" alt="Toyota {{ $slide['name'] }}, góc ngang" width="1100" height="400" fetchpriority="{{ $loop->first ? 'high' : 'auto' }}" draggable="false">
            </div>
        @endforeach
    </div>
    <button class="button slider-arrow slider-prev" type="button" aria-label="Xe trước" data-prev disabled>←</button>
    <button class="button slider-arrow slider-next" type="button" aria-label="Xe tiếp theo" data-next>→</button>
    <div class="model-caption" aria-live="polite" aria-atomic="true">
        <h2 data-model-name>Toyota Corolla Cross</h2><p data-model-tagline>Dành cho những hành trình mới.</p>
    </div>
    <div class="hero-actions"><button class="button button-dark" type="button" data-hero-book data-preview="Đặt lịch xem Toyota Corolla Cross">Đặt lịch xem xe</button><a class="button" data-hero-detail href="{{ route('cars.preview.show', 3) }}">Xem chi tiết</a></div>
    <div class="slide-pagination"><span data-slide-count>01 / 03</span><span>COROLLA CROSS <i>/</i> VIOS <i>/</i> INNOVA</span></div>
    <script type="application/json" data-slide-data>@json($slides)</script>
</section>
<section class="inventory" aria-labelledby="inventory-title">
    <h2 id="inventory-title">Tìm chiếc Toyota của bạn.</h2>
    <p class="catalog-intro">Khám phá các mẫu xe và lựa chọn chiếc Toyota phù hợp với bạn.</p>
    <div class="catalog-layout">
    <form class="filters" role="search" aria-label="Tìm xe trong bản mẫu" data-filters>
        <h3>Bộ lọc xe</h3>
        <label class="search-field"><span class="sr-only">Tên hoặc mã xe</span><input type="search" name="q" maxlength="100" placeholder="Tìm tên hoặc mã xe…" autocomplete="off"></label>
        <label><span>Dòng xe</span><select name="category"><option value="">Tất cả dòng xe</option><option value="land-cruiser">Land Cruiser</option><option value="vios">Vios</option><option value="avanza">Avanza</option><option value="fortuner">Fortuner</option><option value="raize">Raize</option><option value="yaris-cross">Yaris Cross</option></select></label>
        <label><span>Khoảng giá minh họa</span><select name="price"><option value="">Tất cả mức giá</option><option value="under-2">Dưới 2 tỷ</option><option value="2-to-3">2 – 3 tỷ</option><option value="over-3">Trên 3 tỷ</option></select></label>
        <label><span>Tình trạng</span><select name="status"><option value="">Tất cả tình trạng</option><option value="available">Có sẵn</option><option value="reserved">Đã giữ chỗ</option><option value="sold">Đã bán</option></select></label>
        <label><span>Sắp xếp</span><select name="sort"><option value="newest">Mới nhất</option><option value="price_asc">Giá tăng dần</option><option value="price_desc">Giá giảm dần</option></select></label>
        <button class="button catalog-reset" type="button" data-reset>Xóa bộ lọc</button>
        <button class="sr-only" type="submit">Tìm xe</button>
    </form>
    <div class="catalog-results">
    <div class="filter-summary" data-filter-summary hidden><span data-filter-label></span><button type="button" data-reset>Xóa bộ lọc ×</button></div>
    <div class="car-grid" data-car-grid>@foreach ($cars as $car)<x-car-card :car="$car" />@endforeach</div>
    <div class="empty-state" data-empty hidden><h3>Không tìm thấy xe phù hợp</h3><p>Thử một dòng xe hoặc khoảng giá khác.</p><button class="button" type="button" data-reset>Xóa bộ lọc</button></div>
    <div class="results-footer"><p data-results aria-live="polite">{{ count($cars) }} xe trong bản mẫu</p><nav aria-label="Phân trang"><span aria-current="page">01</span></nav></div>
    </div>
    </div>
    <p class="demo-note">Bản xem trước · Xe, giá và tình trạng dùng để minh họa giao diện. Giá chưa phải giá lăn bánh.</p>
    <noscript><p>Bật JavaScript để chuyển xe và thử bộ lọc trong bản xem trước.</p></noscript>
</section>
@endsection
