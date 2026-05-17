<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Web Vận Đơn') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-theme-bg min-h-screen text-slate-900 antialiased">
        @php($isRegisterPage = request()->routeIs('register'))

        <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-10 {{ $isRegisterPage ? 'max-w-none' : '' }}">
            <div class="app-surface w-full {{ $isRegisterPage ? 'max-w-6xl' : 'max-w-md' }} p-8 md:p-10">
                <div class="mb-8 {{ $isRegisterPage ? 'text-left md:mb-10' : 'text-center' }}">
                    <a href="{{ url('/') }}" class="inline-block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 hover:text-slate-700">Web Vận Đơn</a>
                </div>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
