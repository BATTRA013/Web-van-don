<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'Web Vận Đơn') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-body font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-black/50 md:hidden"
                @click="sidebarOpen = false"
            ></div>

            @include('layouts.admin.partials.sidebar')

            <div class="md:pl-64">
                @include('layouts.admin.partials.header')

                <main class="admin-main p-4 sm:p-6 lg:p-8">
                    @if (session('error'))
                        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>