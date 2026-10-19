@extends('layouts.account')
@section('title', $category->exists ? 'Sửa dòng xe' : 'Thêm dòng xe')
@section('content')
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">{{ $category->exists ? 'Sửa dòng xe' : 'Thêm dòng xe Toyota' }}</h1>
        <form method="POST" action="{{ $category->exists ? route('admin.car-categories.update', $category) : route('admin.car-categories.store') }}" class="space-y-5">
            @csrf
            @if ($category->exists) @method('PUT') @endif
            <div>
                <label for="name" class="mb-2 block text-sm font-medium">Tên dòng xe *</label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="100" class="w-full rounded-lg border border-zinc-300 bg-white p-3 @error('name') border-red-500 @enderror">
                @error('name') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" class="mb-2 block text-sm font-medium">Mô tả</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" class="w-full rounded-lg border border-zinc-300 bg-white p-3">{{ old('description', $category->description) }}</textarea>
                @error('description') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <fieldset class="rounded-xl border border-zinc-200 p-4">
                <legend class="px-2 text-sm font-medium">Hiển thị công khai</legend>
                <input type="hidden" name="is_visible" value="0">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $category->is_visible))> Cho phép khách xem dòng xe này</label>
            </fieldset>
            <p class="text-xs text-zinc-500">Đường dẫn (slug) được tạo tự động từ tên; thương hiệu cố định Toyota.</p>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-red-700 px-5 py-3 font-medium text-white">Lưu</button>
                <a href="{{ route('admin.car-categories.index') }}" class="rounded-lg border border-zinc-300 px-5 py-3">Quay lại</a>
            </div>
        </form>
    </div>
@endsection
