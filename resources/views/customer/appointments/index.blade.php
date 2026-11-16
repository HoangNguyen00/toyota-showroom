@extends('layouts.account')

@section('title', 'Lịch hẹn của tôi')
@section('content')
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">Lịch hẹn của tôi</h1>
        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</p>
        @endif
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Danh sách lịch hẹn của bạn</caption>
                <thead class="bg-zinc-100"><tr><th scope="col" class="p-3">Nội dung</th><th scope="col" class="p-3">Thời gian</th><th scope="col" class="p-3">Trạng thái</th><th scope="col" class="p-3">Nhân viên phụ trách</th><th scope="col" class="p-3">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr class="border-t border-zinc-200">
                            <td class="p-3">
                                <p class="font-medium">{{ $appointment->type === \App\Enums\AppointmentType::Viewing ? 'Xem xe' : 'Bảo dưỡng' }}</p>
                                @if ($appointment->car)<p class="text-zinc-500">{{ $appointment->car->name }}</p>@endif
                                @if ($appointment->service)<p class="text-zinc-500">{{ $appointment->service->name }}</p>@endif
                            </td>
                            <td class="p-3">{{ $appointment->confirmed_at ?? $appointment->requested_at }}@if (!$appointment->confirmed_at) <span class="text-zinc-500">(đã yêu cầu)</span>@endif</td>
                            <td class="p-3">{{ $appointment->status->label() }}</td>
                            <td class="p-3">{{ $appointment->assignedStaff?->name ?? 'Chưa phân công' }}</td>
                            <td class="p-3">
                                @can('cancel', $appointment)
                                    <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" onsubmit="return confirm('Xác nhận hủy lịch hẹn này?');">
                                        @csrf
                                        <input type="hidden" name="cancel_reason" value="Khách hủy">
                                        <button type="submit" class="whitespace-nowrap rounded-lg border border-red-300 px-3 py-2 text-red-700">Hủy lịch</button>
                                    </form>
                                @else
                                    <span class="text-zinc-500">—</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-zinc-500">Bạn chưa có lịch hẹn nào. <a href="{{ route('appointments.create') }}" class="underline">Đặt lịch ngay</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $appointments->links() }}
    </div>
@endsection
