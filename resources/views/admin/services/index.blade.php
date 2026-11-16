@extends('layouts.account')

@section('title', 'Quản lý dịch vụ')
@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Quản lý dịch vụ</h1>
            <a href="{{ route('admin.services.create') }}" class="rounded-lg bg-red-700 px-4 py-3 text-sm font-medium text-white">Tạo dịch vụ</a>
        </div>
        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</p>
        @endif

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Danh sách dịch vụ</caption>
                <thead class="bg-zinc-100"><tr><th scope="col" class="p-3">Dịch vụ</th><th scope="col" class="p-3">Loại</th><th scope="col" class="p-3">Đặt lịch</th><th scope="col" class="p-3">Hiển thị</th><th scope="col" class="p-3">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr class="border-t border-zinc-200">
                            <td class="p-3"><p class="font-medium">{{ $service->name }}</p><p class="text-zinc-500">{{ \Illuminate\Support\Str::limit($service->description, 60) }}</p></td>
                            <td class="p-3">{{ $service->type === \App\Enums\ServiceType::Warranty ? 'Bảo hành' : 'Bảo dưỡng' }}</td>
                            <td class="p-3">{{ $service->allow_booking ? 'Nhận đặt lịch' : 'Không' }}</td>
                            <td class="p-3">{{ $service->is_visible ? 'Công khai' : 'Ẩn' }}</td>
                            <td class="p-3">
                                <a href="{{ route('admin.services.edit', $service) }}" class="whitespace-nowrap rounded-lg border border-zinc-300 px-3 py-2">Sửa</a>
                                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="inline" onsubmit="return confirm('Xóa dịch vụ này?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="whitespace-nowrap rounded-lg border border-red-300 px-3 py-2 text-red-700">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-zinc-500">Chưa có dịch vụ nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $services->links() }}
        <p class="text-xs text-zinc-500">Dịch vụ đã có lịch hẹn không xóa được, hãy tắt "Nhận đặt lịch" hoặc "Hiển thị" thay vì xóa.</p>
    </div>
@endsection
