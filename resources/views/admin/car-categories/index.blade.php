@extends('layouts.staff')
@section('title', 'Dòng xe')
@section('eyebrow', 'Catalog')
@section('heading', 'Dòng xe Toyota')
@section('actions')
    <a href="{{ route('admin.cars.index') }}" class="staff-btn staff-btn--secondary">Quản lý xe</a>
    <a href="{{ route('admin.car-categories.create') }}" class="staff-btn staff-btn--primary">Thêm dòng xe</a>
@endsection
@section('content')
    @if ($errors->any())
        <div role="alert" class="staff-alert staff-alert--error"><ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
    @endif
    <form method="GET" action="{{ route('admin.car-categories.index') }}" class="staff-filter" role="search" aria-label="Lọc dòng xe">
        <div class="staff-field staff-field--grow">
            <label for="q">Tên dòng xe</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
        </div>
        <button class="staff-btn staff-btn--dark" type="submit">Tìm</button>
        <a href="{{ route('admin.car-categories.index') }}" class="staff-btn staff-btn--link">Xóa lọc</a>
    </form>
    <div class="staff-table-wrap">
        <table class="staff-table staff-table--stack">
            <caption class="sr-only">Danh sách dòng xe</caption>
            <thead><tr><th scope="col">Dòng xe</th><th scope="col">Đường dẫn</th><th scope="col">Số xe</th><th scope="col">Hiển thị</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td data-label="Dòng xe" class="staff-strong">{{ $category->name }}</td>
                        <td data-label="Đường dẫn" class="staff-sub">{{ $category->slug }}</td>
                        <td data-label="Số xe">{{ $category->cars_count }}</td>
                        <td data-label="Hiển thị"><x-staff.status :tone="$category->is_visible ? 'success' : 'neutral'">{{ $category->is_visible ? 'Công khai' : 'Đang ẩn' }}</x-staff.status></td>
                        <td data-label="Thao tác">
                            <div class="staff-row-actions">
                                <a href="{{ route('admin.car-categories.edit', $category) }}" class="staff-btn staff-btn--secondary staff-btn--sm">Sửa<span class="sr-only"> {{ $category->name }}</span></a>
                                @can('delete', $category)
                                    <form method="POST" action="{{ route('admin.car-categories.destroy', $category) }}" onsubmit="return confirm('Xóa dòng xe này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="staff-btn staff-btn--danger staff-btn--sm">Xóa<span class="sr-only"> {{ $category->name }}</span></button>
                                    </form>
                                @else
                                    <span class="staff-btn staff-btn--sm staff-btn--secondary cursor-not-allowed opacity-50" title="Dòng xe vẫn còn xe nên không xóa được">Xóa</span>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="staff-empty">Chưa có dòng xe.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $categories->links() }}</div>
    <p class="staff-footnote">Thương hiệu luôn là Toyota (hệ thống tự gán). Không xóa được dòng xe còn xe.</p>
@endsection
