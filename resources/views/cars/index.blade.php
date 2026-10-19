@extends('layouts.app')
@section('title', 'Dòng xe | Toyota Showroom')
@section('content')
<section class="inventory" aria-labelledby="inventory-title">
    <p class="eyebrow">TOYOTA / COLLECTION</p>
    <h1 id="inventory-title">Tìm chiếc Toyota của bạn.</h1>
    <p class="catalog-intro">Mỗi mã xe là một chiếc tại showroom. Giá công bố tham khảo, chưa phải giá lăn bánh.</p>
    <div class="catalog-layout">
        <form class="filters" method="GET" action="{{ route('cars.index') }}" role="search" aria-label="Tìm xe">
            <h2>Bộ lọc xe</h2>
            <label class="search-field"><span>Tên hoặc mã xe</span><input type="search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" autocomplete="off"></label>
            <label><span>Dòng xe</span>
                <select name="category">
                    <option value="">Tất cả dòng xe</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>Tình trạng</span>
                <select name="status">
                    <option value="">Tất cả tình trạng</option>
                    @foreach (\App\Support\Catalog\CarPresenter::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>Giá từ (VND)</span><input type="number" name="min_price" min="0" step="1000000" value="{{ $filters['min_price'] ?? '' }}"></label>
            <label><span>Giá đến (VND)</span><input type="number" name="max_price" min="0" step="1000000" value="{{ $filters['max_price'] ?? '' }}"></label>
            <label><span>Sắp xếp</span>
                <select name="sort">
                    <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>Mới nhất</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Giá tăng dần</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Giá giảm dần</option>
                </select>
            </label>
            <button class="button button-dark" type="submit">Tìm xe</button>
            <a class="button catalog-reset" href="{{ route('cars.index') }}">Xóa bộ lọc</a>
        </form>
        <div class="catalog-results">
            @if ($errors->any())
                <ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif
            <div class="car-grid">
                @forelse ($cars as $car)
                    <x-car-card :car="\App\Support\Catalog\CarPresenter::fromCar($car)" />
                @empty
                    <div class="empty-state"><h2>Không tìm thấy xe phù hợp</h2><p>Thử một dòng xe, mức giá hoặc tình trạng khác.</p></div>
                @endforelse
            </div>
            <div class="results-footer">
                <p aria-live="polite">{{ $cars->total() }} xe phù hợp</p>
                {{ $cars->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
