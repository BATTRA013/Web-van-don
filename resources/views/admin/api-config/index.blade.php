@extends('layouts.admin')

@section('title', 'Cấu hình API')
@section('page-title', 'Cấu hình API')

@section('content')
    <div class="space-y-6">
        <section class="admin-panel p-6">
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

            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-900">Danh sách cấu hình đã lưu</h3>
                <a href="{{ route('api-config.create') }}" class="btn-primary">
                    Thêm cấu hình
                </a>
            </div>

            <form method="GET" action="{{ route('api-config.index') }}" class="mt-4 grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 md:grid-cols-4">
                <div>
                    <label for="carrier" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">Hãng</label>
                    <select id="carrier" name="carrier" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700">
                        <option value="">Tất cả hãng</option>
                        <option value="GHN" @selected(($filters['carrier'] ?? '') === 'GHN')>GHN</option>
                        <option value="VIETTEL_POST" @selected(($filters['carrier'] ?? '') === 'VIETTEL_POST')>VIETTEL_POST</option>
                    </select>
                </div>

                <div>
                    <label for="usage" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">Tham chiếu</label>
                    <select id="usage" name="usage" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700">
                        <option value="all" @selected(($filters['usage'] ?? 'all') === 'all')>Tất cả</option>
                        <option value="used" @selected(($filters['usage'] ?? 'all') === 'used')>Đang được dùng</option>
                        <option value="unused" @selected(($filters['usage'] ?? 'all') === 'unused')>Chưa tham chiếu</option>
                    </select>
                </div>

                <div>
                    <label for="sort" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">Sắp xếp</label>
                    <select id="sort" name="sort" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700">
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Mới nhất</option>
                        <option value="oldest" @selected(($filters['sort'] ?? 'newest') === 'oldest')>Cũ nhất</option>
                        <option value="impact" @selected(($filters['sort'] ?? 'newest') === 'impact')>Ảnh hưởng nhiều nhất</option>
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end justify-end gap-2">
                    <a href="{{ route('api-config.index') }}" class="btn-secondary">Xóa lọc</a>
                    <button type="submit" class="btn-primary">Lọc dữ liệu</button>
                </div>
            </form>

            <div class="table-surface mt-4">
                <table class="min-w-full text-sm">
                    <thead class="admin-table-head">
                        <tr>
                            <th class="admin-table-cell">Hãng</th>
                            <th class="admin-table-cell">Shop ID</th>
                            <th class="admin-table-cell">Môi trường</th>
                            <th class="admin-table-cell">Tham chiếu</th>
                            <th class="admin-table-cell text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="admin-table-body">
                        @forelse ($carriers as $carrier)
                            <tr>
                                <td class="admin-table-cell">{{ $carrier->ten_hang }}</td>
                                <td class="admin-table-cell">{{ $carrier->shop_id ?: '-' }}</td>
                                <td class="admin-table-cell">{{ (int) $carrier->moi_truong === 0 ? 'dev' : 'prod' }}</td>
                                <td class="admin-table-cell">
                                    Đơn: {{ (int) $carrier->orders_count }} | COD: {{ (int) $carrier->cod_reconciliations_count }}
                                </td>
                                <td class="admin-table-cell">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('api-config.show', $carrier) }}" class="btn-secondary px-3 py-1.5">Chi tiết</a>
                                        <a href="{{ route('api-config.edit', $carrier) }}" class="btn-primary px-3 py-1.5">Sửa</a>
                                        <form method="POST" action="{{ route('api-config.destroy', $carrier) }}" onsubmit="return confirm('Xóa cấu hình {{ $carrier->ten_hang }}? Hiện đang tham chiếu: Đơn={{ (int) $carrier->orders_count }}, COD={{ (int) $carrier->cod_reconciliations_count }}. Hệ thống sẽ chuyển liên kết sang cấu hình cùng hãng nếu có.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger px-3 py-1.5">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-table-cell text-center text-slate-500">Chưa có cấu hình nào được lưu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($carriers, 'links'))
                <div class="app-pagination">{{ $carriers->links() }}</div>
            @endif
        </section>
    </div>
@endsection