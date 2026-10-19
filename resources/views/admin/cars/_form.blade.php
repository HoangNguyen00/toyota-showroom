@php($editing = $car->exists)
<form method="POST" action="{{ $editing ? route('admin.cars.update', $car) : route('admin.cars.store') }}" class="space-y-5">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="mb-2 block text-sm font-medium">Tên xe / dòng xe *</label>
            <input id="name" name="name" value="{{ old('name', $car->name) }}" required maxlength="200" class="w-full rounded-lg border border-zinc-300 bg-white p-3 @error('name') border-red-500 @enderror">
            @error('name') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="category_id" class="mb-2 block text-sm font-medium">Dòng xe Toyota *</label>
            <select id="category_id" name="category_id" required class="w-full rounded-lg border border-zinc-300 bg-white p-3 @error('category_id') border-red-500 @enderror">
                <option value="">Chọn dòng xe</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $car->category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_visible ? '' : ' (đang ẩn)' }}</option>
                @endforeach
            </select>
            @error('category_id') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            <a href="{{ route('admin.car-categories.create') }}" class="mt-2 inline-block text-sm underline">Thêm dòng xe mới</a>
        </div>
        <div>
            <label for="code" class="mb-2 block text-sm font-medium">Mã chiếc xe *</label>
            <input id="code" name="code" value="{{ old('code', $car->code) }}" required maxlength="50" placeholder="VD: VIOS-TRG-2026-01" class="w-full rounded-lg border border-zinc-300 bg-white p-3 uppercase @error('code') border-red-500 @enderror">
            @error('code') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-zinc-500">Chữ in hoa, số, gạch ngang. Mỗi chiếc xe một mã.</p>
        </div>
        <div>
            <label for="year" class="mb-2 block text-sm font-medium">Năm sản xuất *</label>
            <input id="year" name="year" type="number" min="1990" max="{{ now()->year + 1 }}" value="{{ old('year', $car->year) }}" required class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            @error('year') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="color" class="mb-2 block text-sm font-medium">Màu ngoại thất *</label>
            <input id="color" name="color" value="{{ old('color', $car->color) }}" required maxlength="100" class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            @error('color') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="price" class="mb-2 block text-sm font-medium">Giá công bố (VND) *</label>
            <input id="price" name="price" type="number" min="0" step="1" value="{{ old('price', $car->price !== null ? (int) $car->price : '') }}" required class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            @error('price') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-medium">Tình trạng *</label>
            <select id="status" name="status" required class="w-full rounded-lg border border-zinc-300 bg-white p-3">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $car->status?->value) === $status->value)>{{ $statusLabels[$status->value] }}</option>
                @endforeach
            </select>
            @error('status') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>
    <fieldset class="rounded-xl border border-zinc-200 p-4">
        <legend class="px-2 text-sm font-medium">Hiển thị công khai</legend>
        <input type="hidden" name="is_visible" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $car->is_visible))> Cho phép khách xem xe này (dòng xe cũng phải đang hiển thị)</label>
        @error('is_visible') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
    <fieldset class="rounded-xl border border-zinc-200 p-4">
        <legend class="px-2 text-sm font-medium">Thông số (không bắt buộc)</legend>
        @php($specRows = old('specs', $car->specs ?? []))
        @for ($i = 0; $i < 6; $i++)
            <div class="mb-2 grid gap-2 sm:grid-cols-[1fr_1fr]">
                <input name="specs[{{ $i }}][label]" value="{{ $specRows[$i]['label'] ?? '' }}" maxlength="100" placeholder="Tên thông số" aria-label="Tên thông số {{ $i + 1 }}" class="rounded-lg border border-zinc-300 p-2">
                <input name="specs[{{ $i }}][value]" value="{{ $specRows[$i]['value'] ?? '' }}" maxlength="150" placeholder="Giá trị" aria-label="Giá trị thông số {{ $i + 1 }}" class="rounded-lg border border-zinc-300 p-2">
            </div>
        @endfor
        @error('specs.*.label') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
        @error('specs.*.value') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-red-700 px-5 py-3 font-medium text-white">{{ $editing ? 'Lưu thay đổi' : 'Thêm xe' }}</button>
        <a href="{{ route('admin.cars.index') }}" class="rounded-lg border border-zinc-300 px-5 py-3">Quay lại danh sách</a>
    </div>
</form>
