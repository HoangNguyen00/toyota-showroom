@extends('layouts.auth')
@section('title', 'Đặt lại mật khẩu')
@section('content')
    <div class="auth-card">
        <h1 class="text-2xl font-semibold">Đặt lại mật khẩu</h1>
        <p class="mt-2 text-sm text-zinc-600">Tạo mật khẩu mới tối thiểu 8 ký tự. Sau khi đổi, các phiên đăng nhập cũ sẽ bị đăng xuất.</p>
        <form method="POST" action="{{ route('password.store') }}" class="auth-form" data-auth-form>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required maxlength="255" autocomplete="username"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:outline-red-700">
                @error('email') <p id="email-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            @foreach (['password' => 'Mật khẩu mới', 'password_confirmation' => 'Xác nhận mật khẩu mới'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="password" required minlength="8" autocomplete="new-password"
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:outline-red-700">
                    @error($field) <p id="{{ $field }}-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button type="submit" class="w-full rounded-lg bg-red-700 px-4 py-3 font-medium text-white hover:bg-red-800">Đặt lại mật khẩu</button>
        </form>
        <p class="mt-6 text-sm text-zinc-600">Liên kết hết hạn? <a href="{{ route('password.request') }}" class="font-medium text-red-700 underline">Gửi liên kết mới</a></p>
    </div>
@endsection
