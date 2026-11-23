@extends('layouts.staff')
@section('title', 'Tạo nhân viên')
@section('heading', 'Tạo tài khoản nhân viên')
@section('lead', 'Vai trò được cố định là nhân viên. Mật khẩu ban đầu được bàn giao riêng.')
@section('back')
    <a class="staff-back" href="{{ route('admin.users.index') }}"><span aria-hidden="true">←</span> Danh sách tài khoản</a>
@endsection
@section('content')
    <div class="staff-card max-w-xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf
            @foreach (['name' => ['Họ tên', 'text', 100], 'email' => ['Email', 'email', 255], 'phone' => ['Điện thoại', 'tel', 10], 'password' => ['Mật khẩu', 'password', 72], 'password_confirmation' => ['Xác nhận mật khẩu', 'password', 72]] as $field => [$label, $type, $max])
                <div class="staff-field">
                    <label for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" required maxlength="{{ $max }}"
                        @if ($type !== 'password') value="{{ old($field) }}" @else autocomplete="new-password" @endif
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                        class="w-full">
                    @error($field)<p id="{{ $field }}-error" role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <p class="staff-hint">Mật khẩu ít nhất 8 ký tự, tối đa 72 byte.</p>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="staff-btn staff-btn--primary">Tạo nhân viên</button>
                <a href="{{ route('admin.users.index') }}" class="staff-btn staff-btn--secondary">Về danh sách</a>
            </div>
        </form>
    </div>
@endsection
