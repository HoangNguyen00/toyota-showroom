<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tài khoản') | Toyota Showroom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900">
    <header class="border-b border-zinc-200 bg-white">
        <nav aria-label="Điều hướng tài khoản" class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-5 py-5">
            <a href="{{ url('/') }}" class="font-bold tracking-wide text-red-700">TOYOTA SHOWROOM</a>
            <div class="flex flex-wrap items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('account') }}" class="hover:underline">Tài khoản</a>
                    @can('viewAny', \App\Models\User::class)
                        <a href="{{ route('admin.users.index') }}" class="hover:underline">Quản lý tài khoản</a>
                        <a href="{{ route('admin.cars.index') }}" class="hover:underline">Quản lý xe</a>
                        <a href="{{ route('admin.car-categories.index') }}" class="hover:underline">Dòng xe</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="font-medium text-red-700 hover:underline">Đăng xuất</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:underline">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="font-medium text-red-700 hover:underline">Đăng ký</a>
                @endauth
            </div>
        </nav>
    </header>
    <main class="mx-auto w-full {{ request()->routeIs('admin.users.*', 'admin.cars.*', 'admin.car-categories.*') ? 'max-w-6xl' : 'max-w-lg' }} px-5 py-12">
        @if (session('status'))
            <div role="status" class="mb-6 rounded-xl bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if (session('account_error'))
            <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ session('account_error') }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="px-5 pb-8 text-center text-xs text-zinc-500">
        Đồ án môn học — không phải website đại lý Toyota chính thức.
    </footer>
</body>
</html>
