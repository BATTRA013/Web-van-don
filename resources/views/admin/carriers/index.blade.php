@extends('layouts.admin')

@section('title', 'Quản lý nhà xe')
@section('page-title', 'Chành xe (Nhà xe)')

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

    <div class="space-y-6">
        <section class="admin-panel">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if (($canCreateCarrier ?? false) === true)
                <div class="flex justify-end">
                    <a href="{{ route('carriers.create') }}" class="btn-primary">
                        Thêm nhà xe
                    </a>
                </div>
            @endif
        </section>

        <section>
            <x-ui.table>
                <thead class="admin-table-head">
                    <tr>
                        <th class="admin-table-cell">Nhà xe</th>
                        <th class="admin-table-cell">Người phụ trách</th>
                        <th class="admin-table-cell">Đơn vị liên kết</th>
                        <th class="admin-table-cell">Liên hệ</th>
                        <th class="admin-table-cell">Tuyến chính</th>
                        <th class="admin-table-cell">VĐ ngoài tuyến</th>
                        <th class="admin-table-cell text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse ($carriers as $carrier)
                        @php
                            $linkedUser = $carrier->nguoi_phu_trach ?? null;
                        @endphp
                        <tr>
                            <td class="admin-table-cell">
                                <p class="font-semibold text-gray-900">{{ $carrier->ten_nha_xe }}</p>
                                <p class="text-xs text-gray-500">Mã: {{ $carrier->ma_nha_xe }}</p>
                            </td>
                            <td class="admin-table-cell">
                                @if ($linkedUser)
                                    <p class="font-medium text-gray-900">{{ $linkedUser->ho_ten }}</p>
                                    <p class="text-xs text-gray-500">{{ $linkedUser->ten_dang_nhap }}</p>
                                @else
                                    <span class="text-gray-500">Chưa liên kết</span>
                                @endif
                            </td>
                            <td class="admin-table-cell">
                                @if ($linkedUser)
                                    <p>{{ $linkedUser->ten_don_vi ?: '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $roleLabels[$linkedUser->vai_tro] ?? $linkedUser->vai_tro }}</p>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="admin-table-cell">{{ $carrier->so_dien_thoai ?: '-' }}</td>
                            <td class="admin-table-cell">{{ $carrier->tuyen_duong ?: '-' }}</td>
                            <td class="admin-table-cell">{{ (int) ($carrier->external_route_bills_count ?? 0) }}</td>
                            <td class="admin-table-cell">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('carriers.show', $carrier) }}" class="btn-secondary px-3 py-1.5">Chi tiết</a>
                                    <a href="{{ route('carriers.edit', $carrier) }}" class="btn-primary px-3 py-1.5">Sửa</a>
                                    @if ($canDeleteCarrier)
                                        <form method="POST" action="{{ route('carriers.destroy', $carrier) }}" onsubmit="return confirm('Xóa nhà xe này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger px-3 py-1.5">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-table-cell text-center text-sm text-gray-500">Chưa có nhà xe nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            @if (method_exists($carriers, 'links'))
                <div class="app-pagination">{{ $carriers->links() }}</div>
            @endif
        </section>
    </div>
@endsection