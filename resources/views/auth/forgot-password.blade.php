@extends('layouts.auth')
@section('title', 'Quên mật khẩu')
@section('content')
    <div class="auth-card">
        <h1 class="text-2xl font-semibold">Quên mật khẩu</h1>
        <p class="mt-2 text-sm text-zinc-600">Nhập email đã đăng ký. Chúng tôi sẽ gửi liên kết đặt lại mật khẩu, có hiệu lực trong 60 phút.</p>
        <form method="POST" action="{{ route('password.email') }}" class="auth-form" data-auth-form>
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" autofocus
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:outline-red-700">
                @error('email') <p id="email-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-lg bg-red-700 px-4 py-3 font-medium text-white hover:bg-red-800">Gửi liên kết đặt lại</button>
        </form>
        <p class="mt-6 text-sm text-zinc-600">Nhớ mật khẩu rồi? <a href="{{ route('login') }}" class="font-medium text-red-700 underline">Đăng nhập</a></p>
    </div>
@endsection
