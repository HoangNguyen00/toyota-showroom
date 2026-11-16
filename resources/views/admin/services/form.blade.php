@extends('layouts.account')

@section('title', $service->exists ? 'Sửa dịch vụ' : 'Tạo dịch vụ')
@section('content')
    <div class="space-y-6 max-w-xl">
        <h1 class="text-2xl font-semibold">{{ $service->exists ? 'Sửa dịch vụ' : 'Tạo dịch vụ' }}</h1>
        @if ($errors->any())
            <ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" class="space-y-4">
            @csrf
            @if ($service->exists) @method('PUT') @endif
            <div>
                <label for="name" class="mb-2 block text-sm">Tên dịch vụ</label>
                <input id="name" name="name" value="{{ old('name', $service->name) }}" maxlength="150" required class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            </div>
            <div>
                <label for="type" class="mb-2 block text-sm">Loại</label>
                <select id="type" name="type" required class="rounded-lg border border-zinc-300 bg-white p-3">
                    <option value="warranty" @selected(old('type', $service->type) === 'warranty')>Bảo hành</option>
                    <option value="maintenance" @selected(old('type', $service->type) === 'maintenance')>Bảo dưỡng</option>
                </select>
            </div>
            <div>
                <label for="description" class="mb-2 block text-sm">Mô tả</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" required class="w-full rounded-lg border border-zinc-300 bg-white p-3">{{ old('description', $service->description) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="allow_booking" value="0">
                <input type="checkbox" name="allow_booking" value="1" @checked(old('allow_booking', $service->allow_booking ?? false))> Nhận đặt lịch online
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_visible" value="0">
                <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $service->is_visible ?? true))> Công khai trên trang dịch vụ
            </label>
            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-red-700 px-4 py-3 text-sm font-medium text-white">Lưu</button>
                <a href="{{ route('admin.services.index') }}" class="rounded-lg border border-zinc-300 px-4 py-3 text-sm">Hủy</a>
            </div>
        </form>
    </div>
@endsection
