@extends('layouts.admin')

@section('title', 'Tổng quan')
@section('page-title', 'Tổng quan hệ thống')

@section('content')
    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="admin-panel">
                <p class="text-sm text-gray-500">Tổng đơn hàng</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((int) ($kpis['total_orders'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-emerald-600">Theo phạm vi quyền hiện tại</p>
            </div>

            <div class="admin-panel">
                <p class="text-sm text-gray-500">Đơn đang giao</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((int) ($kpis['in_transit_orders'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-sky-600">Gồm chờ lấy hàng + đang vận chuyển</p>
            </div>

            <div class="admin-panel">
                <p class="text-sm text-gray-500">Đối soát COD chờ xử lý</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((int) ($kpis['cod_pending'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-amber-600">Cần xác nhận biên nhận</p>
            </div>

            <div class="admin-panel">
                <p class="text-sm text-gray-500">Tỉ lệ giao thành công</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((float) ($kpis['delivery_rate'] ?? 0), 1) }}%</p>
                <p class="mt-1 text-xs text-emerald-600">Trên các đơn đã chốt trạng thái</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="admin-panel xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="admin-panel-title">Đơn hàng mới nhất</h2>
                    <a href="{{ route('orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Xem tất cả</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="admin-table-head">
                            <tr>
                                <th class="admin-table-cell">Mã đơn</th>
                                <th class="admin-table-cell">Người nhận</th>
                                <th class="admin-table-cell">Hãng vận chuyển</th>
                                <th class="admin-table-cell">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="admin-table-body">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="admin-table-cell font-semibold">{{ $order->ma_tracking ?: 'DON-'.$order->ma_don_hang }}</td>
                                    <td class="admin-table-cell">{{ $order->ten_nguoi_nhan }}</td>
                                    <td class="admin-table-cell">{{ $order->hangVanChuyen?->ten_hang ?? 'Chưa gán hãng' }}</td>
                                    <td class="admin-table-cell">
                                        <x-ui.badge status="{{ (string) $order->trang_thai }}" label="{{ str_replace('_', ' ', (string) $order->trang_thai) }}" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="admin-table-cell text-center text-sm text-gray-500">Chưa có dữ liệu đơn hàng để hiển thị.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-panel">
                <h2 class="admin-panel-title">Hoạt động gần đây</h2>
                <ul class="mt-4 space-y-4 text-sm text-gray-600">
                    @forelse ($recentActivities as $activity)
                        <li class="border-l-2 border-indigo-500 pl-3">{{ $activity }}</li>
                    @empty
                        <li class="border-l-2 border-slate-300 pl-3">Chưa có hoạt động để hiển thị.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
@endsection