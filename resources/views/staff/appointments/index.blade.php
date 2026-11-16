@extends('layouts.account')

@section('title', 'Quản lý lịch hẹn')
@section('content')
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">Lịch hẹn</h1>
        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</p>
        @endif
        @if ($errors->any())
            <ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        @endif

        <form method="GET" action="{{ route('staff.appointments.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="status" class="mb-2 block text-sm">Trạng thái</label>
                <select id="status" name="status" class="rounded-lg border border-zinc-300 bg-white p-3">
                    <option value="">Tất cả</option>
                    @foreach (\App\Enums\AppointmentStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type" class="mb-2 block text-sm">Loại</label>
                <select id="type" name="type" class="rounded-lg border border-zinc-300 bg-white p-3">
                    <option value="">Tất cả</option>
                    @foreach (\App\Enums\AppointmentType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(($filters['type'] ?? '') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0 flex-1">
                <label for="q" class="mb-2 block text-sm">Tên hoặc số điện thoại</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" class="w-full rounded-lg border border-zinc-300 bg-white p-3">
            </div>
            <button class="rounded-lg bg-zinc-900 px-4 py-3 text-white" type="submit">Lọc</button>
            <a href="{{ route('staff.appointments.index') }}" class="px-2 py-3 text-sm underline">Xóa lọc</a>
        </form>

        @forelse ($appointments as $appointment)
            <article class="rounded-xl border border-zinc-200 bg-white p-4 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs">{{ $appointment->type->label() }}</span>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-700">{{ $appointment->status->label() }}</span>
                    <strong>{{ $appointment->contact_name }}</strong>
                    <span class="text-zinc-500">{{ $appointment->contact_phone }}</span>
                </div>
                <p class="text-sm">Thời gian mong muốn: <strong>{{ $appointment->requested_at->format('d/m/Y H:i') }}</strong></p>
                @if ($appointment->car)<p class="text-sm">Xe: {{ $appointment->car->name }}</p>@endif
                @if ($appointment->service)<p class="text-sm">Dịch vụ: {{ $appointment->service->name }}</p>@endif
                @if ($appointment->customer_car_info)<p class="text-sm">Xe khách: {{ $appointment->customer_car_info }}</p>@endif
                @if ($appointment->assignedStaff)<p class="text-sm">Phụ trách: {{ $appointment->assignedStaff->name }}</p>@endif
                @if ($appointment->confirmed_at)<p class="text-sm">Đã xác nhận: {{ $appointment->confirmed_at->format('d/m/Y H:i') }}</p>@endif
                @if ($appointment->note)<p class="text-sm">Ghi chú: {{ $appointment->note }}</p>@endif
                @if ($appointment->cancel_reason)<p class="text-sm text-red-600">Lý do hủy: {{ $appointment->cancel_reason }}</p>@endif

                <div class="flex flex-wrap gap-4">
                    @can('assign', $appointment)
                        <form method="POST" action="{{ route('staff.appointments.assign', $appointment) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label for="assigned_to-{{ $appointment->id }}" class="mb-1 block text-sm">Phân công</label>
                                <select id="assigned_to-{{ $appointment->id }}" name="assigned_to" class="rounded-lg border border-zinc-300 bg-white p-2">
                                    <option value="">— Chưa phân công —</option>
                                    @foreach ($salesStaff as $s)
                                        <option value="{{ $s->id }}" @selected($appointment->assigned_to === $s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-white">Lưu phân công</button>
                        </form>
                    @endcan

                    @can('confirm', $appointment)
                        <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label for="confirmed_at-{{ $appointment->id }}" class="mb-1 block text-sm">Xác nhận giờ (mặc định giờ khách hẹn)</label>
                                <input type="datetime-local" id="confirmed_at-{{ $appointment->id }}" name="confirmed_at" value="{{ old('confirmed_at', $appointment->requested_at->format('Y-m-d\TH:i')) }}" required class="rounded-lg border border-zinc-300 bg-white p-2">
                            </div>
                            <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-white">Xác nhận lịch</button>
                        </form>
                    @endcan

                    @can('cancel', $appointment)
                        <form method="POST" action="{{ route('staff.appointments.reschedule', $appointment) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label for="staff_reschedule_at-{{ $appointment->id }}" class="mb-1 block text-sm">Đổi giờ mong muốn</label>
                                <input type="datetime-local" id="staff_reschedule_at-{{ $appointment->id }}" name="requested_at" required class="rounded-lg border border-zinc-300 bg-white p-2">
                            </div>
                            <button type="submit" class="rounded-lg border border-zinc-300 px-4 py-2 text-zinc-700">Đổi giờ</button>
                        </form>
                        <form method="POST" action="{{ route('staff.appointments.cancel', $appointment) }}" class="flex flex-wrap items-end gap-2" onsubmit="return confirm('Xác nhận hủy lịch này?');">
                            @csrf
                            <div>
                                <label for="staff_cancel_reason-{{ $appointment->id }}" class="mb-1 block text-sm">Lý do hủy</label>
                                <input type="text" id="staff_cancel_reason-{{ $appointment->id }}" name="cancel_reason" maxlength="500" required class="rounded-lg border border-zinc-300 bg-white p-2">
                            </div>
                            <button type="submit" class="rounded-lg border border-red-300 px-4 py-2 text-red-700">Hủy lịch</button>
                        </form>
                    @endcan

                    @can('conclude', $appointment)
                        <form method="POST" action="{{ route('staff.appointments.conclude', $appointment) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label for="outcome-{{ $appointment->id }}" class="mb-1 block text-sm">Kết thúc</label>
                                <select id="outcome-{{ $appointment->id }}" name="outcome" class="rounded-lg border border-zinc-300 bg-white p-2">
                                    <option value="completed">Hoàn tất</option>
                                    <option value="no_show">No-show</option>
                                </select>
                            </div>
                            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-white">Lưu kết quả</button>
                        </form>
                    @endcan
                </div>
            </article>
        @empty
            <p class="text-zinc-500">Không có lịch hẹn nào khớp bộ lọc.</p>
        @endforelse

        {{ $appointments->links() }}
    </div>
@endsection
