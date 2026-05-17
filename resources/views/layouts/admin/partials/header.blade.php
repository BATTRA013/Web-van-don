<header class="admin-header sticky top-0 z-30">
    @php
        $currentUser = auth()->user();
        $displayName = $currentUser?->ho_ten ?? $currentUser?->ten_dang_nhap ?? 'Người dùng';
        $displayInitial = strtoupper(substr($displayName, 0, 1));
        $normalizedRole = \Illuminate\Support\Str::of((string) ($currentUser?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        $roleLabel = match ($normalizedRole) {
            'admin' => 'Quản trị viên',
            'chushop' => 'Chủ shop',
            'quanlychanhxe' => 'Quản lý chành xe',
            default => 'Người dùng',
        };
    @endphp

    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 p-2 text-gray-600 hover:bg-gray-100 md:hidden"
                @click="sidebarOpen = true"
            >
                <span class="sr-only">Mở menu</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zm0 6.5a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                </svg>
            </button>

            <h1 class="text-base font-semibold text-gray-900 sm:text-lg">@yield('page-title', 'Tổng quan')</h1>
        </div>

        <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
            <button type="button" class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100">
                <span class="sr-only">Thông báo</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 2a4 4 0 00-4 4v1.586l-.707.707A1 1 0 005 10h10a1 1 0 00.707-1.707L15 7.586V6a4 4 0 00-4-4z" />
                    <path d="M10 18a3 3 0 01-2.995-2.824L7 15h6a3 3 0 01-2.824 2.995L10 18z" />
                </svg>
                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500"></span>
            </button>

            <div class="hidden items-center gap-2 sm:flex">
                <a
                    href="{{ route('profile.edit') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Hồ sơ
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                    >
                        Đăng xuất
                    </button>
                </form>

                <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2">
                    <div class="grid h-8 w-8 place-items-center rounded-full bg-slate-800 text-sm font-semibold text-white">
                        {{ $displayInitial }}
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold text-gray-800">{{ $displayName }}</p>
                        <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>