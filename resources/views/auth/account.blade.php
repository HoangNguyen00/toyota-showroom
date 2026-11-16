@extends('layouts.account')
@section('title', 'Tài khoản của tôi')
@section('content')
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium text-red-700">TÀI KHOẢN</p>
        <h1 class="mt-2 break-words text-2xl font-semibold">Xin chào, {{ auth()->user()->name }}</h1>
        <dl class="mt-6 space-y-4 text-sm">
            <div><dt class="text-zinc-500">Email</dt><dd class="mt-1 break-all">{{ auth()->user()->email }}</dd></div>
            <div><dt class="text-zinc-500">Số điện thoại</dt><dd class="mt-1">{{ auth()->user()->phone ?: 'Chưa cập nhật' }}</dd></div>
            <div><dt class="text-zinc-500">Vai trò</dt><dd class="mt-1">{{ match(auth()->user()->role) {
                \App\Enums\UserRole::Admin => 'Quản trị viên',
                \App\Enums\UserRole::Sales => 'Nhân viên tư vấn',
                default => 'Khách hàng',
            } }}</dd></div>
        </dl>

        @if (auth()->user()->role === \App\Enums\UserRole::Customer)
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('appointments.create') }}" class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-red-700 transition hover:border-red-200 hover:bg-red-50">+ Đặt lịch hẹn mới</a>
                <a href="{{ route('customer.appointments.index') }}" class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-300 hover:bg-zinc-50">Lịch hẹn của tôi →</a>
            </div>
        @elseif (auth()->user()->role === \App\Enums\UserRole::Sales)
            <div class="mt-6">
                <a href="{{ route('staff.appointments.index') }}" class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-300 hover:bg-zinc-50">Quản lý lịch hẹn →</a>
            </div>
        @else
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <a href="{{ route('staff.appointments.index') }}" class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-300 hover:bg-zinc-50">Quản lý lịch hẹn →</a>
                <a href="{{ route('admin.services.index') }}" class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-300 hover:bg-zinc-50">Quản lý dịch vụ →</a>
                @can('viewAny', \App\Models\User::class)
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-300 hover:bg-zinc-50">Quản lý tài khoản →</a>
                @endcan
            </div>
        @endif

        <a href="{{ url('/') }}" class="mt-6 inline-block text-sm font-medium text-red-700 underline">Về trang chủ</a>
    </div>
@endsection
