@extends('layouts.staff')

@section('title', 'Quản lý dịch vụ')
@section('eyebrow', 'Catalog')
@section('lead', 'Dịch vụ bảo dưỡng, bảo hành hiển thị trên trang khách.')
@section('actions')
    <a href="{{ route('admin.services.create') }}" class="staff-btn staff-btn--primary">Tạo dịch vụ</a>
@endsection
@section('content')
    <div class="staff-table-wrap">
        <table class="staff-table staff-table--stack">
            <caption class="sr-only">Danh sách dịch vụ</caption>
            <thead><tr><th scope="col">Dịch vụ</th><th scope="col">Loại</th><th scope="col">Đặt lịch</th><th scope="col">Hiển thị</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td data-label="Dịch vụ"><p class="staff-strong">{{ $service->name }}</p><p class="staff-sub">{{ \Illuminate\Support\Str::limit($service->description, 60) }}</p></td>
                        <td data-label="Loại">{{ $service->type === \App\Enums\ServiceType::Warranty ? 'Bảo hành' : 'Bảo dưỡng' }}</td>
                        <td data-label="Đặt lịch">{{ $service->allow_booking ? 'Nhận đặt lịch' : 'Không' }}</td>
                        <td data-label="Hiển thị"><x-staff.status :tone="$service->is_visible ? 'success' : 'neutral'">{{ $service->is_visible ? 'Công khai' : 'Ẩn' }}</x-staff.status></td>
                        <td data-label="Thao tác">
                            <div class="staff-row-actions">
                                <a href="{{ route('admin.services.edit', $service) }}" class="staff-btn staff-btn--secondary staff-btn--sm">Sửa<span class="sr-only"> {{ $service->name }}</span></a>
                                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('Xóa dịch vụ này?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="staff-btn staff-btn--danger staff-btn--sm">Xóa<span class="sr-only"> {{ $service->name }}</span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="staff-empty">Chưa có dịch vụ nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $services->links() }}</div>
    <p class="staff-footnote">Dịch vụ đã có lịch hẹn không xóa được, hãy tắt "Nhận đặt lịch" hoặc "Hiển thị" thay vì xóa.</p>
@endsection
