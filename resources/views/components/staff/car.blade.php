{{-- "Xe khách chọn" — một cách trình bày cho mọi màn sales/admin.
     Đọc từ quan hệ car đã lưu của hồ sơ/lịch; mã chiếc xe luôn hiện để phân biệt hai chiếc cùng tên.
     compact: một dòng cho danh sách. full: ảnh chính (nếu đã eager-load images), tên, mã, màu. --}}
@props(['car' => null, 'compact' => false, 'empty' => 'Chưa chọn xe'])
@if (! $car)
    <span {{ $attributes->class(['staff-sub italic']) }}>{{ $empty }}</span>
@elseif ($compact)
    <span {{ $attributes->class(['staff-car staff-car--compact']) }}>
        <span class="staff-car-name">{{ $car->name }}</span>
        <span class="staff-car-code">{{ $car->code }}</span>
        @if ($car->color)<span class="staff-sub">{{ $car->color }}</span>@endif
    </span>
@else
    @php($image = $car->relationLoaded('images') ? ($car->images->firstWhere('is_primary', true) ?? $car->images->first()) : null)
    <div {{ $attributes->class(['staff-car']) }}>
        @if ($image)
            <img src="{{ $image->url() }}" alt="{{ $car->name }} {{ $car->code }}" class="staff-car-image" loading="lazy" width="120" height="72">
        @else
            <span class="staff-car-image staff-car-image--empty" aria-hidden="true"><x-customer-icon name="car"/></span>
        @endif
        <dl class="staff-car-facts">
            <div><dt class="sr-only">Tên xe</dt><dd class="staff-car-name">{{ $car->name }}</dd></div>
            <div><dt>Mã chiếc xe</dt><dd><span class="staff-car-code">{{ $car->code }}</span></dd></div>
            <div><dt>Màu</dt><dd>{{ $car->color ?: '—' }}</dd></div>
        </dl>
    </div>
@endif
