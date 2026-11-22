@extends('layouts.account')
@section('title', 'Sửa xe')
@section('content')
    <div class="space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">Sửa xe: {{ $car->name }}</h1>
            @if ($car->is_visible)
                <a href="{{ route('cars.show', $car) }}" class="text-sm underline">Xem trang công khai ↗</a>
            @endif
        </div>
        @include('admin.cars._form')

        <section class="rounded-xl border border-zinc-200 bg-white p-5" aria-labelledby="images-title">
            <h2 id="images-title" class="text-lg font-semibold">Ảnh xe</h2>
            <p class="mt-1 text-sm text-zinc-600">Tối đa 10 ảnh JPEG/PNG/WebP, mỗi ảnh 5 MB. Ảnh chính hiển thị trên card; số thứ tự nhỏ hiển thị trước. Ẩn xe trước khi xóa ảnh cuối.</p>
            @if ($car->images->isEmpty())
                <p class="mt-4 text-sm text-zinc-500">Chưa có ảnh cho xe này.</p>
            @else
                <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($car->images as $image)
                        <li class="rounded-lg border border-zinc-200 p-2">
                            <img src="{{ $image->url() }}" alt="Ảnh {{ $car->name }} số {{ $loop->iteration }}" class="h-32 w-full rounded object-cover">
                            <form method="POST" action="{{ route('admin.cars.images.update', [$car, $image]) }}" class="my-3 space-y-2 text-sm">
                                @csrf @method('PATCH')
                                <label class="block">Thứ tự ảnh {{ $loop->iteration }}<input name="sort_order" type="number" min="0" max="9999" required value="{{ $image->sort_order }}" class="w-full rounded border p-2"></label>
                                <label class="block"><input name="is_primary" type="checkbox" value="1" @checked($image->is_primary)> Đặt làm ảnh chính</label>
                                <button class="rounded border px-3 py-2">Lưu ảnh</button>
                            </form>
                            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                <span>{{ $image->is_primary ? 'Ảnh chính' : 'Thứ tự '.$image->sort_order }}</span>
                                @can('deleteImage', $car)
                                    <form method="POST" action="{{ route('admin.cars.images.destroy', [$car, $image]) }}" onsubmit="return confirm('Xóa ảnh này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-700 underline">Xóa</button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
            @can('uploadImage', $car)
                <form method="POST" action="{{ route('admin.cars.images.store', $car) }}" enctype="multipart/form-data" class="mt-5 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="image" class="mb-2 block text-sm">Chọn ảnh</label>
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required class="text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_primary" value="1"> Đặt làm ảnh chính</label>
                    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-3 text-sm text-white">Tải ảnh lên</button>
                </form>
                @error('image') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            @endcan
        </section>
    </div>
@endsection
