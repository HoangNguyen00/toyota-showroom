@extends('layouts.auth')
@section('title', 'Đăng nhập')
@section('content')
    <div class="auth-card">
        <h1 class="text-2xl font-semibold">Đăng nhập</h1>
        <p class="mt-2 text-sm text-zinc-600">Chào bạn trở lại. Đăng nhập để tiếp tục hành trình.</p>
        <form method="POST" action="{{ route('login.store') }}" class="auth-form" data-auth-form>
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="username"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:outline-red-700">
                @error('email') <p id="email-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium">Mật khẩu</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:outline-red-700">
                @error('password') <p id="password-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                <p class="mt-2 text-right text-sm"><a href="{{ route('password.request') }}" class="font-medium text-red-700 underline">Quên mật khẩu?</a></p>
            </div>
            <button type="submit" class="w-full rounded-lg bg-red-700 px-4 py-3 font-medium text-white hover:bg-red-800">Đăng nhập</button>
        </form>
        <p class="mt-6 text-sm text-zinc-600">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-medium text-red-700 underline">Đăng ký</a></p>
    </div>
@endsection
