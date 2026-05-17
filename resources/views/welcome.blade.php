<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Đăng nhập | {{ config('app.name', 'Web Vận Đơn') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100">
    @auth
        <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h1 class="text-2xl font-bold text-slate-900">Bạn đã đăng nhập</h1>
                <p class="mt-2 text-sm text-slate-500">Truy cập nhanh vào trang quản trị Web Vận Đơn.</p>

                <a
                    href="{{ url('/dashboard') }}"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Vào Dashboard
                </a>
            </div>
        </div>
    @else
        <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-10">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="mb-8 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Web Vận Đơn</p>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">Đăng nhập hệ thống</h1>
                    <p class="mt-2 text-sm text-slate-500">Quản lý vận đơn, đối soát COD và cấu hình API tại một nơi.</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="mb-1 block text-sm font-medium text-slate-700">Tài khoản</label>
                        <input
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-900"
                            placeholder="Nhập tên đăng nhập"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Mật khẩu</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-900"
                            placeholder="••••••••"
                        >
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        Ghi nhớ đăng nhập
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Đăng nhập
                    </button>

                    {{--
                    @if (Route::has('password.request'))
                        <div class="text-center">
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">Quên mật khẩu?</a>
                        </div>
                    @endif
                    --}}
                </form>

                @if (Route::has('register'))
                    <p class="mt-5 text-center text-sm text-slate-600">
                        Chưa có tài khoản?
                        <a href="{{ route('register') }}" class="font-semibold text-slate-900 hover:underline">Đăng ký</a>
                    </p>
                @endif
            </div>
        </div>
    @endauth
</body>
</html>
