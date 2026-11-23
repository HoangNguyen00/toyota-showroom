@extends('layouts.staff')

@section('title', 'Quản lý lịch hẹn')
@section('heading', 'Lịch hẹn')
@section('lead', auth()->user()->role === \App\Enums\UserRole::Admin ? 'Phân công, xác nhận và theo dõi lịch xem xe, bảo dưỡng.' : 'Xác nhận và kết thúc lịch hẹn của showroom.')
@section('content')
    @if ($errors->any())
        <div role="alert" class="staff-alert staff-alert--error">
            Chưa lưu được thao tác:
            <ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="GET" action="{{ route('staff.appointments.index') }}" class="staff-filter" role="search" aria-label="Lọc lịch hẹn">
        <div class="staff-field">
            <label for="status">Trạng thái</label>
            <select id="status" name="status">
                <option value="">Tất cả</option>
                @foreach (\App\Enums\AppointmentStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="staff-field">
            <label for="type">Loại</label>
            <select id="type" name="type">
                <option value="">Tất cả</option>
                @foreach (\App\Enums\AppointmentType::cases() as $t)
                    <option value="{{ $t->value }}" @selected(($filters['type'] ?? '') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="staff-field staff-field--grow">
            <label for="q">Tên hoặc số điện thoại</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
        </div>
        <button class="staff-btn staff-btn--dark" type="submit">Lọc</button>
        <a href="{{ route('staff.appointments.index') }}" class="staff-btn staff-btn--link">Xóa lọc</a>
    </form>

    <div class="staff-stack">
        @forelse ($appointments as $appointment)
            <article class="staff-card staff-appointment" aria-labelledby="appointment-{{ $appointment->id }}-title">
                <div class="staff-appointment-head">
                    <h2 id="appointment-{{ $appointment->id }}-title">Lịch #{{ $appointment->id }} · {{ $appointment->contact_name }}</h2>
                    <span class="staff-chip">{{ $appointment->type->label() }}</span>
                    <x-staff.status :status="$appointment->status" />
                </div>
                @if ($appointment->type !== \App\Enums\AppointmentType::Maintenance)
                    <section aria-label="Xe khách chọn" class="staff-car-box">
                        <p class="staff-label mb-2">Xe khách chọn</p>
                        <x-staff.car :car="$appointment->car" />
                    </section>
                @endif
                <dl class="staff-facts">
                    <div><dt>Giờ mong muốn</dt><dd>{{ $appointment->requested_at->format('H:i d/m/Y') }}</dd></div>
                    <div><dt>Giờ xác nhận</dt><dd @class(['is-empty' => ! $appointment->confirmed_at])>{{ $appointment->confirmed_at?->format('H:i d/m/Y') ?? 'Chưa xác nhận' }}</dd></div>
                    <div><dt>Người phụ trách</dt><dd @class(['is-empty' => ! $appointment->assignedStaff])>{{ $appointment->assignedStaff?->name ?? 'Chưa phân công' }}</dd></div>
                    <div><dt>Điện thoại</dt><dd>{{ $appointment->contact_phone }}</dd></div>
                    @if ($appointment->type === \App\Enums\AppointmentType::Maintenance)
                        {{-- Bảo dưỡng: ưu tiên dịch vụ và xe khách khai báo. --}}
                        <div><dt>Dịch vụ</dt><dd @class(['is-empty' => ! $appointment->service])>{{ $appointment->service?->name ?? 'Chưa chọn dịch vụ' }}</dd></div>
                        <div><dt>Xe khách khai báo</dt><dd @class(['is-empty' => ! $appointment->customer_car_info])>{{ $appointment->customer_car_info ?: 'Không khai báo' }}</dd></div>
                        @if ($appointment->car)<div><dt>Xe tại showroom</dt><dd><x-staff.car :car="$appointment->car" compact /></dd></div>@endif
                    @else
                        @if ($appointment->service)<div><dt>Dịch vụ</dt><dd>{{ $appointment->service->name }}</dd></div>@endif
                        @if ($appointment->customer_car_info)<div><dt>Xe khách</dt><dd>{{ $appointment->customer_car_info }}</dd></div>@endif
                    @endif
                    @if ($appointment->note)<div><dt>Ghi chú</dt><dd>{{ $appointment->note }}</dd></div>@endif
                    @if ($appointment->cancel_reason)<div><dt>Lý do hủy</dt><dd class="text-red-700">{{ $appointment->cancel_reason }}</dd></div>@endif
                </dl>

                @php($ops = auth()->user()->can('assign', $appointment) || auth()->user()->can('confirm', $appointment) || auth()->user()->can('cancel', $appointment) || auth()->user()->can('conclude', $appointment))
                @if ($ops)
                    <div class="staff-op-grid">
                        @can('assign', $appointment)
                            <section class="staff-op staff-op--assign" aria-labelledby="op-assign-{{ $appointment->id }}">
                                <h3 id="op-assign-{{ $appointment->id }}">Phân công</h3>
                                <form method="POST" action="{{ route('staff.appointments.assign', $appointment) }}">
                                    @csrf
                                    <div class="staff-field">
                                        <label for="assigned_to-{{ $appointment->id }}">Nhân viên phụ trách</label>
                                        <select id="assigned_to-{{ $appointment->id }}" name="assigned_to">
                                            <option value="">— Chưa phân công —</option>
                                            @foreach ($salesStaff as $s)
                                                <option value="{{ $s->id }}" @selected($appointment->assigned_to === $s->id)>{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="staff-btn staff-btn--secondary">Lưu phân công</button>
                                </form>
                            </section>
                        @endcan

                        @can('confirm', $appointment)
                            <section class="staff-op staff-op--confirm" aria-labelledby="op-confirm-{{ $appointment->id }}">
                                <h3 id="op-confirm-{{ $appointment->id }}">Xác nhận lịch</h3>
                                <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}">
                                    @csrf
                                    <div class="staff-field">
                                        <label for="confirmed_at-{{ $appointment->id }}">Xác nhận giờ (mặc định giờ khách hẹn)</label>
                                        <input type="datetime-local" id="confirmed_at-{{ $appointment->id }}" name="confirmed_at" value="{{ old('confirmed_at', $appointment->requested_at->format('Y-m-d\TH:i')) }}" required>
                                    </div>
                                    <button type="submit" class="staff-btn staff-btn--primary">Xác nhận lịch</button>
                                </form>
                            </section>
                        @endcan

                        @can('cancel', $appointment)
                            <section class="staff-op staff-op--reschedule" aria-labelledby="op-reschedule-{{ $appointment->id }}">
                                <h3 id="op-reschedule-{{ $appointment->id }}">Đổi giờ</h3>
                                <form method="POST" action="{{ route('staff.appointments.reschedule', $appointment) }}">
                                    @csrf
                                    <div class="staff-field">
                                        <label for="staff_reschedule_at-{{ $appointment->id }}">Đổi giờ mong muốn</label>
                                        <input type="datetime-local" id="staff_reschedule_at-{{ $appointment->id }}" name="requested_at" required>
                                    </div>
                                    <button type="submit" class="staff-btn staff-btn--secondary">Đổi giờ</button>
                                </form>
                            </section>
                            <section class="staff-op staff-op--cancel" aria-labelledby="op-cancel-{{ $appointment->id }}">
                                <h3 id="op-cancel-{{ $appointment->id }}">Hủy lịch</h3>
                                <form method="POST" action="{{ route('staff.appointments.cancel', $appointment) }}" onsubmit="return confirm('Xác nhận hủy lịch này?');">
                                    @csrf
                                    <div class="staff-field">
                                        <label for="staff_cancel_reason-{{ $appointment->id }}">Lý do hủy</label>
                                        <input type="text" id="staff_cancel_reason-{{ $appointment->id }}" name="cancel_reason" maxlength="500" required>
                                    </div>
                                    <button type="submit" class="staff-btn staff-btn--danger">Hủy lịch</button>
                                </form>
                            </section>
                        @endcan

                        @can('conclude', $appointment)
                            <section class="staff-op staff-op--conclude" aria-labelledby="op-conclude-{{ $appointment->id }}">
                                <h3 id="op-conclude-{{ $appointment->id }}">Kết thúc lịch</h3>
                                <form method="POST" action="{{ route('staff.appointments.conclude', $appointment) }}">
                                    @csrf
                                    <div class="staff-field">
                                        <label for="outcome-{{ $appointment->id }}">Kết quả</label>
                                        <select id="outcome-{{ $appointment->id }}" name="outcome">
                                            <option value="completed">Hoàn tất</option>
                                            <option value="no_show">Không đến</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="staff-btn staff-btn--dark">Lưu kết quả</button>
                                </form>
                            </section>
                        @endcan
                    </div>
                @endif
            </article>
        @empty
            <p class="staff-card staff-empty">Không có lịch hẹn nào khớp bộ lọc.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $appointments->links() }}</div>
@endsection
