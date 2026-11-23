@extends('layouts.staff')
@section('title', 'Quản lý xe')
@section('eyebrow', 'Catalog')
@section('lead', 'Mỗi bản ghi là một chiếc xe tại showroom.')
@section('actions')
    <a href="{{ route('admin.car-categories.index') }}" class="staff-btn staff-btn--secondary">Dòng xe</a>
    <a href="{{ route('admin.cars.create') }}" class="staff-btn staff-btn--primary">Thêm xe</a>
@endsection
@section('content')
    @if ($errors->any())
        <div role="alert" class="staff-alert staff-alert--error"><ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
    @endif
    <form method="GET" action="{{ route('admin.cars.index') }}" class="staff-filter" role="search" aria-label="Lọc xe">
        <div class="staff-field staff-field--grow">
            <label for="q">Tên hoặc mã xe</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
        </div>
        <div class="staff-field">
            <label for="category">Dòng xe</label>
            <select id="category" name="category">
                <option value="">Tất cả</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="staff-field">
            <label for="status">Tình trạng</label>
            <select id="status" name="status">
                <option value="">Tất cả</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="staff-field">
            <label for="visible">Hiển thị</label>
            <select id="visible" name="visible">
                <option value="">Tất cả</option>
                <option value="1" @selected(($filters['visible'] ?? '') === '1')>Công khai</option>
                <option value="0" @selected(($filters['visible'] ?? '') === '0')>Đang ẩn</option>
            </select>
        </div>
        <button class="staff-btn staff-btn--dark" type="submit">Tìm</button>
        <a href="{{ route('admin.cars.index') }}" class="staff-btn staff-btn--link">Xóa lọc</a>
    </form>
    <div class="staff-table-wrap">
        <table class="staff-table staff-table--stack">
            <caption class="sr-only">Danh sách xe của showroom</caption>
            <thead><tr><th scope="col">Xe</th><th scope="col">Giá</th><th scope="col">Tình trạng</th><th scope="col">Ảnh</th><th scope="col">Hiển thị</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
                @forelse ($cars as $car)
                    <tr>
                        <td data-label="Xe">
                            <p class="staff-strong">{{ $car->name }}</p>
                            <p class="staff-sub">{{ $car->code }} · {{ $car->category?->name }} · {{ $car->year }} · {{ $car->color }}</p>
                        </td>
                        <td data-label="Giá" class="whitespace-nowrap">{{ number_format((int) $car->price, 0, ',', '.') }} ₫</td>
                        <td data-label="Tình trạng">{{ $statusLabels[$car->status->value] ?? $car->status->value }}</td>
                        <td data-label="Ảnh">{{ $car->images->count() }}</td>
                        <td data-label="Hiển thị"><x-staff.status :tone="$car->is_visible ? 'success' : 'neutral'">{{ $car->is_visible ? 'Công khai' : 'Đang ẩn' }}</x-staff.status></td>
                        <td data-label="Thao tác">
                            <div class="staff-row-actions">
                                <a href="{{ route('admin.cars.edit', $car) }}" class="staff-btn staff-btn--secondary staff-btn--sm">Sửa<span class="sr-only"> {{ $car->code }}</span></a>
                                @can('delete', $car)
                                    <form method="POST" action="{{ route('admin.cars.destroy', $car) }}" onsubmit="return confirm('Xóa xe này? Xe đã có hồ sơ tư vấn/lịch hẹn sẽ không xóa được.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="staff-btn staff-btn--danger staff-btn--sm">Xóa<span class="sr-only"> {{ $car->code }}</span></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="staff-empty">Chưa có xe phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $cars->links() }}</div>
    <p class="staff-footnote">Chỉ xe và dòng xe cùng bật hiển thị mới xuất hiện công khai. Xe đã có hồ sơ nên ẩn thay vì xóa.</p>
@endsection
