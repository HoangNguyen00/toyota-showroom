@extends('layouts.staff')

@section('title', $service->exists ? 'Sửa dịch vụ' : 'Tạo dịch vụ')
@section('eyebrow', 'Catalog')
@section('back')
    <a class="staff-back" href="{{ route('admin.services.index') }}"><span aria-hidden="true">←</span> Danh sách dịch vụ</a>
@endsection
@section('content')
    <div class="staff-card max-w-xl space-y-4">
        @if ($errors->any())
            <div role="alert" class="staff-alert staff-alert--error"><ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
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
                <button type="submit" class="staff-btn staff-btn--primary">Lưu</button>
                <a href="{{ route('admin.services.index') }}" class="staff-btn staff-btn--secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection