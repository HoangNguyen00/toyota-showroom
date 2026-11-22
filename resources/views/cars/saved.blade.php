@extends('layouts.app')
@section('title', 'Xe quan tâm | Toyota Showroom')
@section('content')
<div class="detail-page saved-page" data-saved-cars-page data-saved-limit="{{ $limit }}" data-cards-url="{{ route('cars.saved.cards') }}" @if ($compareUrl) data-compare-url="{{ $compareUrl }}" @endif>
    <nav class="breadcrumbs" aria-label="Đường dẫn"><a href="{{ route('cars.index') }}">Dòng xe</a><span aria-hidden="true">/</span><span aria-current="page">Xe quan tâm</span></nav>
    <header class="saved-header">
        <p class="eyebrow">TOYOTA / XE QUAN TÂM</p>
        <h1 id="saved-title">Xe bạn quan tâm.</h1>
        <p class="catalog-intro">Danh sách lưu trên trình duyệt này, tối đa {{ $limit }} chiếc, không cần đăng nhập. Xe showroom đã ẩn hoặc gỡ sẽ tự rời khỏi danh sách.</p>
    </header>

    <p class="saved-status" data-saved-status role="status" aria-live="polite"></p>
    <noscript><p class="saved-alert">Cần bật JavaScript để xem danh sách xe đã lưu trên trình duyệt này.</p></noscript>
    <p class="saved-alert" data-saved-unavailable hidden>Trình duyệt đang chặn bộ nhớ cục bộ (chế độ riêng tư hoặc cài đặt bảo mật), nên chưa thể lưu hoặc đọc xe quan tâm. Bạn vẫn có thể xem <a href="{{ route('cars.index') }}">danh sách xe</a>.</p>

    @if ($compareUrl)
        <form class="saved-compare" data-saved-compare hidden aria-labelledby="saved-compare-title">
            <p id="saved-compare-title"><strong>So sánh 2 xe đã lưu.</strong> <span data-saved-compare-hint>Đánh dấu đúng hai chiếc bên dưới.</span></p>
            <button class="button button-dark" type="submit" data-saved-compare-submit disabled>So sánh 2 xe đã chọn</button>
        </form>
    @endif

    <section class="inventory saved-inventory" aria-labelledby="saved-title">
        <div class="car-grid" data-saved-grid aria-busy="true"></div>
        <div class="empty-state saved-empty" data-saved-empty hidden>
            <h2>Chưa có xe quan tâm</h2>
            <p>Bấm “Lưu xe” trên thẻ xe hoặc trang chi tiết để thêm vào đây.</p>
            <a class="button button-dark" href="{{ route('cars.index') }}#inventory-title">Khám phá dòng xe</a>
        </div>
    </section>
</div>
@endsection
