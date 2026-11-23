@extends('layouts.app')
@section('title', 'So sánh xe | Toyota Showroom')
@section('content')
<div class="detail-page compare-page">
    <nav class="breadcrumbs" aria-label="Đường dẫn"><a href="{{ route('cars.index') }}">Dòng xe</a><span aria-hidden="true">/</span><span aria-current="page">So sánh xe</span></nav>
    <header class="compare-header">
        <p class="eyebrow">TOYOTA / SO SÁNH</p>
        <h1 id="compare-title">So sánh hai chiếc xe.</h1>
        <p class="catalog-intro">Chọn hai chiếc tại showroom theo tên, mã xe và màu. Giá công bố tham khảo, chưa phải giá lăn bánh.</p>
    </header>

    <form class="compare-picker" method="GET" action="{{ route('cars.compare') }}" aria-labelledby="compare-title" novalidate>
        @foreach (['xe1' => 'Chiếc xe thứ nhất', 'xe2' => 'Chiếc xe thứ hai'] as $field => $label)
            <label>
                <span>{{ $label }}</span>
                <select name="{{ $field }}" required @if ($compareErrors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>
                    <option value="">— Chọn xe —</option>
                    @foreach ($options as $category => $cars)
                        <optgroup label="{{ $category }}">
                            @foreach ($cars as $option)
                                <option value="{{ $option->id }}" @selected($selected[$field] === (string) $option->id)>{{ $option->name }} · {{ $option->code }} · {{ $option->color }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @if ($compareErrors->has($field))
                    <span class="compare-error" id="{{ $field }}-error">{{ $compareErrors->first($field) }}</span>
                @endif
            </label>
        @endforeach
        <div class="compare-picker-actions">
            <button class="button button-dark" type="submit">So sánh</button>
            <a class="button catalog-reset" href="{{ route('cars.compare') }}">Chọn lại</a>
        </div>
    </form>

    @if ($compareErrors->isNotEmpty())
        <ul class="compare-alert" role="alert">
            @foreach ($compareErrors->unique() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    @if ($comparison)
        @php($differences = collect($comparison['rows'])->where('different', true)->count())
        <section class="compare-result" aria-labelledby="compare-result-title">
            <div class="compare-result-heading">
                <h2 id="compare-result-title">Kết quả so sánh</h2>
                <p><span class="compare-legend" aria-hidden="true"></span>{{ $differences }} mục khác nhau được tô nền và đánh dấu “Khác”.</p>
            </div>
            <div class="compare-table-wrap">
                <table class="compare-table">
                    <caption class="sr-only">So sánh {{ $comparison['cars'][0]['name'] }} ({{ $comparison['cars'][0]['code'] }}) với {{ $comparison['cars'][1]['name'] }} ({{ $comparison['cars'][1]['code'] }})</caption>
                    <thead>
                        <tr>
                            <td class="compare-corner"></td>
                            @foreach ($comparison['cars'] as $car)
                                <th scope="col" class="compare-car">
                                    <div class="compare-car-image">
                                        @if ($car['image'])
                                            <img src="{{ $car['image'] }}" alt="{{ $car['name'] }} màu {{ $car['color'] }}" width="800" height="450" loading="lazy">
                                        @else
                                            <span class="gallery-placeholder">Ảnh xe đang được cập nhật</span>
                                        @endif
                                    </div>
                                    <span class="compare-car-name">{{ $car['name'] }}</span>
                                    <span class="compare-car-meta">{{ $car['code'] }} · {{ $car['color'] }}</span>
                                    <a class="button" href="{{ route('cars.show', $car['id']) }}">Xem chi tiết<span class="sr-only"> {{ $car['name'] }} {{ $car['code'] }}</span></a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison['rows'] as $row)
                            <tr @class(['is-different' => $row['different']])>
                                <th scope="row">{{ $row['label'] }}@if ($row['different'])<span class="compare-badge">Khác</span>@endif</th>
                                @foreach ($row['values'] as $value)
                                    <td @class(['is-missing' => $value === \App\Support\Catalog\CarComparison::MISSING])>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($comparison['price_gap'] > 0)
                <p class="compare-note">Chênh lệch giá công bố: {{ number_format($comparison['price_gap'], 0, ',', '.') }} ₫. Thông số chưa niêm yết hiển thị “Đang cập nhật” — liên hệ showroom để xác nhận.</p>
            @else
                <p class="compare-note">Thông số chưa niêm yết hiển thị “Đang cập nhật” — liên hệ showroom để xác nhận.</p>
            @endif
        </section>
    @elseif ($compareErrors->isEmpty())
        <div class="empty-state compare-empty"><h2>Chọn hai chiếc để bắt đầu</h2><p>Các chiếc cùng tên được phân biệt bằng mã xe và màu ngoại thất.</p></div>
    @endif
</div>
@endsection
