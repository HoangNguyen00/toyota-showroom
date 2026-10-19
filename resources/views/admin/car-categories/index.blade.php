@extends('layouts.account')
@section('title', 'Dòng xe')
@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Dòng xe Toyota</h1>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.cars.index') }}" class="rounded-lg border border-zinc-300 px-4 py-3 text-sm">Quản lý xe</a>
                <a href="{{ route('admin.car-categories.create') }}" class="rounded-lg bg-red-700 px-4 py-3 text-sm font-medium text-white">Thêm dòng xe</a>
            </div>
        </div>
        @if ($errors->any())
            <ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        @endif
        <form method="GET" action="{{ route('admin.car-categories.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1">
                <label for="q" class="mb-2 block text-sm">Tên dòng xe</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            </div>
            <button class="rounded-lg bg-zinc-900 px-4 py-3 text-white" type="submit">Tìm</button>
            <a href="{{ route('admin.car-categories.index') }}" class="px-2 py-3 text-sm underline">Xóa lọc</a>
        </form>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Danh sách dòng xe</caption>
                <thead class="bg-zinc-100"><tr><th scope="col" class="p-3">Dòng xe</th><th scope="col" class="p-3">Đường dẫn</th><th scope="col" class="p-3">Số xe</th><th scope="col" class="p-3">Hiển thị</th><th scope="col" class="p-3">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr class="border-t border-zinc-200">
                            <td class="p-3 font-medium">{{ $category->name }}</td>
                            <td class="p-3 text-zinc-500">{{ $category->slug }}</td>
                            <td class="p-3">{{ $category->cars_count }}</td>
                            <td class="p-3">{{ $category->is_visible ? 'Công khai' : 'Đang ẩn' }}</td>
                            <td class="p-3">
                                <a href="{{ route('admin.car-categories.edit', $category) }}" class="underline">Sửa</a>
                                @can('delete', $category)
                                    <form method="POST" action="{{ route('admin.car-categories.destroy', $category) }}" class="mt-2" onsubmit="return confirm('Xóa dòng xe này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-700 underline">Xóa</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-zinc-500">Chưa có dòng xe.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $categories->links() }}
        <p class="text-xs text-zinc-500">Thương hiệu luôn là Toyota (hệ thống tự gán). Không xóa được dòng xe còn xe.</p>
    </div>
@endsection
