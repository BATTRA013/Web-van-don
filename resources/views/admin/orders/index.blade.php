@extends('layouts.admin')

@section('title', 'Danh sách đơn hàng')
@php
    $normalizedRole = \Illuminate\Support\Str::of((string) (auth()->user()?->vai_tro ?? ''))
        ->ascii()
        ->lower()
        ->replaceMatches('/[^a-z0-9]/', '')
        ->toString();
    $isTransportManager = $normalizedRole === 'quanlychanhxe';
@endphp
@section('page-title', $isTransportManager ? 'Đơn được giao cho chành xe' : 'Quản lý đơn hàng')

@section('content')
    <div class="space-y-6">
        <section class="admin-panel">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $filters = $filters ?? [
                    'status' => '',
                    'order_code' => '',
                    'receiver_phone' => '',
                ];
            @endphp

            <div class="space-y-4">
                <form method="GET" action="{{ route('orders.index') }}" class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-2/3">
                        <div>
                            <label for="status-filter" class="mb-1.5 block text-sm font-medium text-gray-700">Trạng thái</label>
                            <select id="status-filter" name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tất cả trạng thái</option>
                                <option value="moi" @selected(($filters['status'] ?? '') === 'moi')>Mới tạo</option>
                                <option value="cho_lay_hang" @selected(($filters['status'] ?? '') === 'cho_lay_hang')>Chờ lấy hàng</option>
                                <option value="dang_van_chuyen" @selected(($filters['status'] ?? '') === 'dang_van_chuyen')>Đang vận chuyển</option>
                                <option value="da_giao" @selected(($filters['status'] ?? '') === 'da_giao')>Đã giao</option>
                                <option value="hoan" @selected(($filters['status'] ?? '') === 'hoan')>Hoàn hàng</option>
                            </select>
                        </div>

                        <x-ui.input label="Mã đơn" name="order_code" :value="$filters['order_code'] ?? ''" placeholder="Nhập mã đơn..." />
                        <x-ui.input label="SĐT người nhận" name="receiver_phone" :value="$filters['receiver_phone'] ?? ''" placeholder="Nhập số điện thoại..." />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn-secondary">
                            Lọc dữ liệu
                        </button>
                        <a href="{{ route('orders.index') }}" class="btn-secondary">
                            Xóa lọc
                        </a>
                    </div>
                </form>

                @if (! $isTransportManager)
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('orders.create') }}" class="btn-secondary">
                            Tạo đơn mới
                        </a>
                        <a href="{{ route('orders.shipments.create') }}" class="btn-primary">
                            Tạo vận đơn đa hãng
                        </a>
                        <form method="POST" action="{{ route('orders.shipments.sync-all') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">
                                Đồng bộ vận đơn
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </section>

        <section>
            <x-ui.table>
                <thead class="admin-table-head">
                    <tr>
                        <th class="admin-table-cell">Mã đơn</th>
                        <th class="admin-table-cell">Người nhận</th>
                        <th class="admin-table-cell">Nhà xe</th>
                        <th class="admin-table-cell">COD</th>
                        <th class="admin-table-cell">Trạng thái</th>
                        <th class="admin-table-cell text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse ($orders as $order)
                        @php
                            $statusLabelMap = [
                                'moi' => 'Mới tạo',
                                'cho_lay_hang' => 'Chờ lấy hàng',
                                'dang_van_chuyen' => 'Đang vận chuyển',
                                'da_giao' => 'Đã giao',
                                'hoan' => 'Hoàn hàng',
                            ];
                            $status = $order->trang_thai ?: 'moi';
                        @endphp
                        <tr>
                            <td class="admin-table-cell font-semibold">{{ $order->ma_tracking }}</td>
                            <td class="admin-table-cell">
                                <p class="font-medium text-gray-900">{{ $order->ten_nguoi_nhan }}</p>
                                <p class="text-xs text-gray-500">{{ $order->sdt_nguoi_nhan }}</p>
                            </td>
                            <td class="admin-table-cell">
                                @if ($isTransportManager)
                                    {{ $order->externalRouteBills->first()?->nhaXe?->ten_nha_xe ?? 'Chưa gán' }}
                                @else
                                    {{ $order->hangVanChuyen?->ten_hang ?? 'Chưa gán' }}
                                @endif
                            </td>
                            <td class="admin-table-cell">{{ number_format((float) $order->tien_cod, 0, ',', '.') }}đ</td>
                            <td class="admin-table-cell">
                                <x-ui.badge :status="$status" :label="$statusLabelMap[$status] ?? ucfirst($status)" />
                            </td>
                            <td class="admin-table-cell">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('orders.show', $order) }}" class="btn-secondary px-3 py-1.5">
                                        Chi tiết đơn
                                    </a>
                                    @if (! $isTransportManager)
                                        <a href="{{ route('orders.shipments.create', ['order_id' => $order->ma_don_hang]) }}" class="btn-primary px-3 py-1.5">
                                            Tạo vận đơn
                                        </a>
                                        <form method="POST" action="{{ route('orders.shipments.sync-one', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn-secondary px-3 py-1.5">
                                                Đồng bộ trạng thái
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Bạn có chắc muốn xóa đơn này không?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger px-3 py-1.5">
                                                Xóa đơn
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-table-cell text-center text-sm text-gray-500">
                                Chưa có đơn hàng nào trong hệ thống.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            @if (method_exists($orders, 'links'))
                <div class="app-pagination">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
@endsection