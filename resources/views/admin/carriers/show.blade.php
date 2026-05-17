@extends('layouts.admin')

@section('title', 'Chi tiết nhà xe')
@section('page-title', 'Chi tiết nhà xe')

@section('content')
    @php
        $normalizedRole = \Illuminate\Support\Str::of((string) (auth()->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
        $canDeleteCarrier = $normalizedRole === 'admin';
        $roleLabels = [
            'admin' => 'Admin',
            'chu_shop' => 'Chủ shop',
            'quan_ly_chanh_xe' => 'Quản lý chành xe',
        ];
    @endphp

    <section class="admin-panel">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <h2 class="admin-panel-title">{{ $carrier->ten_nha_xe }}</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <p><span class="font-semibold text-gray-700">Mã nhà xe:</span> {{ $carrier->ma_nha_xe }}</p>
            <p><span class="font-semibold text-gray-700">Số điện thoại:</span> {{ $carrier->so_dien_thoai ?: '-' }}</p>
            <p class="sm:col-span-2"><span class="font-semibold text-gray-700">Tuyến đường:</span> {{ $carrier->tuyen_duong ?: '-' }}</p>
            <p><span class="font-semibold text-gray-700">Số vận đơn ngoài tuyến:</span> {{ (int) ($carrier->external_route_bills_count ?? 0) }}</p>
            <p><span class="font-semibold text-gray-700">Tài khoản liên kết:</span> {{ $linkedUser?->ten_dang_nhap ?: '-' }}</p>
            <p><span class="font-semibold text-gray-700">Người phụ trách:</span> {{ $linkedUser?->ho_ten ?: '-' }}</p>
            <p><span class="font-semibold text-gray-700">Vai trò tài khoản:</span> {{ $linkedUser ? ($roleLabels[$linkedUser->vai_tro] ?? $linkedUser->vai_tro) : '-' }}</p>
            <p class="sm:col-span-2"><span class="font-semibold text-gray-700">Tên đơn vị (shop/chành xe):</span> {{ $linkedUser?->ten_don_vi ?: '-' }}</p>
            <p><span class="font-semibold text-gray-700">Email quản lý:</span> {{ $linkedUser?->email ?: '-' }}</p>
            <p><span class="font-semibold text-gray-700">Địa chỉ quản lý:</span> {{ $linkedUser?->dia_chi ?: '-' }}</p>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('carriers.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Danh sách</a>
            <a href="{{ route('carriers.edit', $carrier) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Sửa</a>
            @if ($canDeleteCarrier)
                <form method="POST" action="{{ route('carriers.destroy', $carrier) }}" onsubmit="return confirm('Xóa nhà xe này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Xóa</button>
                </form>
            @endif
        </div>
    </section>
@endsection
