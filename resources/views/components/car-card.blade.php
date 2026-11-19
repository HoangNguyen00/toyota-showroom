@props(['car'])
<article class="car-card" data-car data-name="{{ $car['name'] }}" data-code="{{ $car['code'] }}" data-category="{{ $car['category'] }}" data-price="{{ $car['price'] }}" data-status="{{ $car['status'] }}">
    <div class="product-image-wrap"><img class="card-image" src="{{ $car['image'] }}" alt="{{ $car['image_alt'] }}" width="800" height="450" loading="lazy"></div>
    <div class="product-card-body">
        <h3>{{ $car['name'] }}</h3>
        <p class="product-price">{{ number_format($car['price'], 0, ',', '.') }} ₫ <small>· giá công bố</small></p>
        <div class="product-tags"><span class="year-tag">{{ $car['year'] }}</span><span>Xe mới</span><span class="product-state">{{ $car['status_label'] }}</span></div>
        <dl class="product-highlights"><div><dd>{{ $car['color'] }}</dd><dt>Màu ngoại thất</dt></div><div><dd>{{ $car['category_name'] ?? 'Toyota' }}</dd><dt>Dòng xe</dt></div><div><dd>{{ $car['code'] }}</dd><dt>Mã chiếc xe tại showroom</dt></div></dl>
        <a class="product-spec-link" href="{{ route('cars.show', $car['id']) }}#thong-so">Thông số và trang bị tiêu chuẩn</a>
        <div class="product-card-actions"><a class="button button-dark" href="{{ route('cars.show', $car['id']) }}">Xem chi tiết<span class="sr-only"> {{ $car['name'] }}</span></a>
        @if($car['status'] !== 'sold')<button class="button" type="button" data-preview="Tư vấn {{ $car['name'] }}">Tư vấn</button>@else<span class="button product-sold">Đã bán</span>@endif</div>
        <p class="product-price-note">Giá công bố tham khảo, chưa gồm chi phí lăn bánh.</p>
    </div>
</article>
