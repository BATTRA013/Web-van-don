<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="admin-sidebar fixed inset-y-0 left-0 z-50 w-64 transform transition duration-200 ease-in-out md:translate-x-0"
>
    <div class="admin-sidebar-brand">
        <span class="text-lg font-semibold text-white">Web Vận Đơn</span>
    </div>

    @php
        $normalizedRole = \Illuminate\Support\Str::of((string) (auth()->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
    @endphp

    <nav class="space-y-1 px-3 py-4">
        <a href="{{ route('dashboard') }}" class="admin-sidebar-link {{ request()->routeIs('dashboard') ? 'admin-sidebar-link-active' : '' }}">
            Tổng quan
        </a>
        <a href="{{ route('orders.index') }}" class="admin-sidebar-link {{ request()->routeIs('orders.*') ? 'admin-sidebar-link-active' : '' }}">
            Quản lý đơn hàng
        </a>

        @if (in_array($normalizedRole, ['admin', 'quanlychanhxe'], true))
            <a href="{{ route('carriers.index') }}" class="admin-sidebar-link {{ request()->routeIs('carriers.*') ? 'admin-sidebar-link-active' : '' }}">
                Chành xe (Nhà xe)
            </a>
        @endif

        @if (in_array($normalizedRole, ['admin', 'quanlychanhxe', 'chushop'], true))
            <a href="{{ route('cod.index') }}" class="admin-sidebar-link {{ request()->routeIs('cod.*') ? 'admin-sidebar-link-active' : '' }}">
                Đối soát COD
            </a>
        @endif

        @if (in_array($normalizedRole, ['admin', 'chushop'], true))
            <a href="{{ route('api-config.index') }}" class="admin-sidebar-link {{ request()->routeIs('api-config.*') ? 'admin-sidebar-link-active' : '' }}">
                Cấu hình API
            </a>
        @endif

        @if ($normalizedRole === 'admin')
            <a href="{{ route('users.index') }}" class="admin-sidebar-link {{ request()->routeIs('users.*') ? 'admin-sidebar-link-active' : '' }}">
                Quản lý người dùng
            </a>
        @endif
    </nav>
</aside>