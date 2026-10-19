@extends('layouts.account')
@section('title', 'Quản lý xe')
@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Quản lý xe</h1>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.car-categories.index') }}" class="rounded-lg border border-zinc-300 px-4 py-3 text-sm">Dòng xe</a>
                <a href="{{ route('admin.cars.create') }}" class="rounded-lg bg-red-700 px-4 py-3 text-sm font-medium text-white">Thêm xe</a>
            </div>
        </div>
        @if ($errors->any())
            <ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        @endif
        <form method="GET" action="{{ route('admin.cars.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1">
                <label for="q" class="mb-2 block text-sm">Tên hoặc mã xe</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            </div>
            <div>
                <label for="category" class="mb-2 block text-sm">Dòng xe</label>
                <select id="category" name="category" class="rounded-lg border border-zinc-300 bg-white p-3">
                    <option value="">Tất cả</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-2 block text-sm">Tình trạng</label>
                <select id="status" name="status" class="rounded-lg border border-zinc-300 bg-white p-3">
                    <option value="">Tất cả</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="visible" class="mb-2 block text-sm">Hiển thị</label>
                <select id="visible" name="visible" class="rounded-lg border border-zinc-300 bg-white p-3">
                    <option value="">Tất cả</option>
                    <option value="1" @selected(($filters['visible'] ?? '') === '1')>Công khai</option>
                    <option value="0" @selected(($filters['visible'] ?? '') === '0')>Đang ẩn</option>
                </select>
            </div>
            <button class="rounded-lg bg-zinc-900 px-4 py-3 text-white" type="submit">Tìm</button>
            <a href="{{ route('admin.cars.index') }}" class="px-2 py-3 text-sm underline">Xóa lọc</a>
        </form>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Danh sách xe của showroom</caption>
                <thead class="bg-zinc-100"><tr><th scope="col" class="p-3">Xe</th><th scope="col" class="p-3">Giá</th><th scope="col" class="p-3">Tình trạng</th><th scope="col" class="p-3">Ảnh</th><th scope="col" class="p-3">Hiển thị</th><th scope="col" class="p-3">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($cars as $car)
                        <tr class="border-t border-zinc-200">
                            <td class="p-3">
                                <p class="font-medium">{{ $car->name }}</p>
                                <p class="text-zinc-500">{{ $car->code }} · {{ $car->category?->name }} · {{ $car->year }} · {{ $car->color }}</p>
                            </td>
                            <td class="whitespace-nowrap p-3">{{ number_format((int) $car->price, 0, ',', '.') }} ₫</td>
                            <td class="p-3">{{ $statusLabels[$car->status->value] ?? $car->status->value }}</td>
                            <td class="p-3">{{ $car->images->count() }}</td>
                            <td class="p-3">{{ $car->is_visible ? 'Công khai' : 'Đang ẩn' }}</td>
                            <td class="p-3">
                                <a href="{{ route('admin.cars.edit', $car) }}" class="whitespace-nowrap underline">Sửa</a>
                                @can('delete', $car)
                                    <form method="POST" action="{{ route('admin.cars.destroy', $car) }}" class="mt-2" onsubmit="return confirm('Xóa xe này? Xe đã có hồ sơ tư vấn/lịch hẹn sẽ không xóa được.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="whitespace-nowrap text-red-700 underline">Xóa</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-zinc-500">Chưa có xe phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $cars->links() }}
        <p class="text-xs text-zinc-500">Chỉ xe và dòng xe cùng bật hiển thị mới xuất hiện công khai. Xe đã có hồ sơ nên ẩn thay vì xóa.</p>
    </div>
@endsection
