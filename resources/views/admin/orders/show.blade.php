@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', 'Chi tiết đơn hàng')

@section('content')
    @php
        $normalizedRole = \Illuminate\Support\Str::of((string) (auth()->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
        $isTransportManager = $normalizedRole === 'quanlychanhxe';
    @endphp

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <section class="admin-panel">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="admin-panel-title">Đơn {{ $order->ma_tracking }}</h2>
                    <p class="admin-panel-desc">Thông tin chi tiết đơn hàng trong cơ sở dữ liệu.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        Quay lại danh sách
                    </a>
                    @if (! $isTransportManager)
                        <a href="{{ route('orders.shipments.create', ['order_id' => $order->ma_don_hang]) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Tạo vận đơn đa hãng
                        </a>
                        <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Xóa đơn này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                                Xóa đơn
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        @php
            $detail = $order->orderDetails->first();
        @endphp

        @if (! $isTransportManager)
        <form method="POST" action="{{ route('orders.update', $order) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="admin-panel">
                <h3 class="text-base font-semibold text-gray-900">Chỉnh sửa đơn hàng</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-ui.input label="Người nhận" name="receiver_name" :value="old('receiver_name', $order->ten_nguoi_nhan)" />
                    <x-ui.input label="SĐT" name="receiver_phone" :value="old('receiver_phone', $order->sdt_nguoi_nhan)" />
                    <div>
                        <label for="edit_to_province_select" class="mb-1.5 block text-sm font-medium text-gray-700">Tỉnh/thành</label>
                        <select id="edit_to_province_select" class="w-full rounded-lg border-gray-300 text-sm" data-selected="{{ old('to_province_id', $order->ma_tinh_thanh) }}">
                            <option value="">Chọn tỉnh/thành</option>
                        </select>
                        <input type="hidden" name="to_province_id" id="edit_to_province_id" value="{{ old('to_province_id', $order->ma_tinh_thanh) }}">
                    </div>
                    <div>
                        <label for="edit_to_district_select" class="mb-1.5 block text-sm font-medium text-gray-700">Quận/huyện</label>
                        <select id="edit_to_district_select" class="w-full rounded-lg border-gray-300 text-sm" data-selected="{{ old('to_district_id', $order->ma_quan_huyen) }}">
                            <option value="">Chọn quận/huyện</option>
                        </select>
                        <input type="hidden" name="to_district_id" id="edit_to_district_id" value="{{ old('to_district_id', $order->ma_quan_huyen) }}">
                    </div>
                    <div>
                        <label for="edit_to_ward_select" class="mb-1.5 block text-sm font-medium text-gray-700">Phường/xã</label>
                        <select id="edit_to_ward_select" class="w-full rounded-lg border-gray-300 text-sm" data-selected="{{ old('to_ward_code', $order->ma_phuong_xa) }}">
                            <option value="">Chọn phường/xã</option>
                        </select>
                        <input type="hidden" name="to_ward_code" id="edit_to_ward_code" value="{{ old('to_ward_code', $order->ma_phuong_xa) }}">
                    </div>
                    <x-ui.input label="Trọng lượng (gram)" name="item_weight" type="number" :value="old('item_weight', $detail?->khoi_luong_sp ?? $order->trong_luong)" />
                    <x-ui.input label="Tên hàng" name="item_name" :value="old('item_name', $detail?->ten_san_pham)" />
                    <x-ui.input label="Số lượng" name="item_quantity" type="number" :value="old('item_quantity', $detail?->so_luong ?? 1)" />
                    <x-ui.input label="Giá bán" name="item_price" type="number" :value="old('item_price', $detail?->gia_ban ?? 0)" />
                    <x-ui.input label="COD" name="cod_value" type="number" :value="old('cod_value', $order->tien_cod)" />
                    <x-ui.input label="Dài" name="length" type="number" :value="old('length', $order->chieu_dai)" />
                    <x-ui.input label="Rộng" name="width" type="number" :value="old('width', $order->chieu_rong)" />
                    <x-ui.input label="Cao" name="height" type="number" :value="old('height', $order->chieu_cao)" />
                </div>

                <div class="mt-4">
                    <x-ui.input label="Địa chỉ nhận" name="receiver_address" :value="old('receiver_address', $order->dia_chi_chi_tiet)" />
                </div>

                <div class="mt-4 flex justify-end">
                    <x-ui.button type="submit" variant="primary">Lưu thay đổi</x-ui.button>
                </div>
            </section>
        </form>
        @endif

        <section class="admin-panel space-y-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Gửi chành xe</h3>
                <p class="text-sm text-gray-500">
                    @if ($isTransportManager)
                        Chành xe nhận hoặc từ chối yêu cầu vận chuyển, sau đó cập nhật biên lai thực tế.
                    @else
                        Chủ shop gửi yêu cầu vận chuyển cho chành xe. Biên lai sẽ do chành xe cập nhật sau khi nhận đơn.
                    @endif
                </p>
            </div>

            @if (! $isTransportManager)
                <form method="POST" action="{{ route('orders.external-route-bills.store', $order) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @csrf

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Chọn chành xe nhận đơn</label>
                        <select name="ma_nha_xe" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">-- Chọn nhà xe --</option>
                            @foreach ($carriers as $carrier)
                                <option value="{{ $carrier->ma_nha_xe }}" @selected((int) old('ma_nha_xe') === (int) $carrier->ma_nha_xe)>
                                    {{ $carrier->ten_nha_xe }}
                                </option>
                            @endforeach
                        </select>
                        @error('ma_nha_xe')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 flex items-end justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Gửi yêu cầu cho chành xe
                        </button>
                    </div>
                </form>
            @endif

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="admin-table-head">Mã biên lai</th>
                            <th class="admin-table-head">Nhà xe</th>
                            <th class="admin-table-head">Trạng thái</th>
                            <th class="admin-table-head">Ảnh biên lai</th>
                            <th class="admin-table-head text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->externalRouteBills as $bill)
                            <tr>
                                <td class="admin-table-cell font-medium text-gray-900">{{ $bill->ma_bien_lai }}</td>
                                <td class="admin-table-cell">{{ $bill->nhaXe?->ten_nha_xe ?? 'N/A' }}</td>
                                <td class="admin-table-cell">
                                    @php
                                        $statusMap = [
                                            'cho_nhan' => 'Chờ chành xe nhận',
                                            'da_nhan' => 'Chành xe đã nhận',
                                            'da_gui_bien_lai' => 'Đã cập nhật biên lai',
                                            'tu_choi' => 'Chành xe từ chối',
                                        ];
                                        $statusLabel = $statusMap[$bill->trang_thai ?? ''] ?? ($bill->trang_thai ?: 'N/A');
                                    @endphp
                                    <span class="text-sm text-slate-700">{{ $statusLabel }}</span>
                                    @if ($bill->ly_do_tu_choi)
                                        <p class="mt-1 text-xs text-rose-600">Lý do: {{ $bill->ly_do_tu_choi }}</p>
                                    @endif
                                </td>
                                <td class="admin-table-cell">
                                    @if ($bill->anh_chup_bien_lai)
                                        @php
                                            $receiptImageUrl = \Illuminate\Support\Str::startsWith((string) $bill->anh_chup_bien_lai, ['http://', 'https://'])
                                                ? (string) $bill->anh_chup_bien_lai
                                                : \Illuminate\Support\Facades\Storage::url((string) $bill->anh_chup_bien_lai);
                                        @endphp
                                        <a href="{{ $receiptImageUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-700">Xem ảnh</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="admin-table-cell text-right">
                                    <div class="flex justify-end gap-2">
                                        @if ($isTransportManager && ($bill->trang_thai ?? '') === 'cho_nhan')
                                            <form method="POST" action="{{ route('orders.external-route-bills.accept', ['order' => $order, 'bill' => $bill]) }}" class="inline-flex">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                                    Nhận đơn
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('orders.external-route-bills.reject', ['order' => $order, 'bill' => $bill]) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                <input type="text" name="ly_do_tu_choi" placeholder="Lý do từ chối" class="w-36 rounded-lg border-gray-300 px-2 py-1 text-xs" required>
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700">
                                                    Từ chối
                                                </button>
                                            </form>
                                        @endif

                                        @if (! $isTransportManager)
                                            <form method="POST" action="{{ route('orders.external-route-bills.destroy', ['order' => $order, 'bill' => $bill]) }}" onsubmit="return confirm('Xóa biên lai này?');" class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700">
                                                    Xóa
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-table-cell text-center text-gray-500">Chưa có yêu cầu gửi chành xe cho đơn này.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($isTransportManager)
                @php
                    $updatableBills = $order->externalRouteBills->filter(fn ($bill) => in_array($bill->trang_thai, ['da_nhan', 'da_gui_bien_lai'], true));
                @endphp

                @if ($updatableBills->isNotEmpty())
                    <div class="space-y-4 border-t border-slate-200 pt-4">
                        @foreach ($updatableBills as $bill)
                            <form method="POST" action="{{ route('orders.external-route-bills.receipt', ['order' => $order, 'bill' => $bill]) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 rounded-lg border border-slate-200 p-4 sm:grid-cols-4">
                                @csrf
                                @method('PUT')

                                <div class="sm:col-span-4 text-sm text-slate-600">
                                    Cập nhật cho yêu cầu: <span class="font-semibold text-slate-900">{{ $bill->nhaXe?->ten_nha_xe }} - {{ $bill->ma_bien_lai }}</span>
                                </div>

                                <x-ui.input label="Mã biên lai thực tế" name="ma_bien_lai" :value="old('ma_bien_lai', $bill->ma_bien_lai)" />

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ảnh biên lai từ máy</label>
                                    <input type="file" name="anh_bien_lai_file" accept="image/png,image/jpeg,image/webp" class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>

                                <div class="sm:col-span-4 flex justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800">
                                        Cập nhật biên lai
                                    </button>
                                </div>
                            </form>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection

@if (! $isTransportManager)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var provinceSelect = document.getElementById('edit_to_province_select');
        var hiddenProvince = document.getElementById('edit_to_province_id');
        var districtSelect = document.getElementById('edit_to_district_select');
        var wardSelect = document.getElementById('edit_to_ward_select');
        var hiddenDistrict = document.getElementById('edit_to_district_id');
        var hiddenWard = document.getElementById('edit_to_ward_code');
        var provincesUrl = @json(route('orders.ghn.meta.provinces'));
        var districtsUrl = @json(route('orders.ghn.meta.districts'));
        var wardsUrl = @json(route('orders.ghn.meta.wards'));

        if (!provinceSelect || !hiddenProvince || !districtSelect || !wardSelect || !hiddenDistrict || !hiddenWard) {
            return;
        }

        function resetSelect(selectEl, placeholder) {
            selectEl.innerHTML = '';
            var option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            selectEl.appendChild(option);
        }

        function fillDistricts(rows, selectedId) {
            resetSelect(districtSelect, 'Chọn quận/huyện');
            (rows || []).forEach(function (row) {
                var option = document.createElement('option');
                option.value = String(row.id);
                option.textContent = row.name + ' (' + row.id + ')';
                districtSelect.appendChild(option);
            });

            if (selectedId) {
                districtSelect.value = String(selectedId);
            }

            hiddenDistrict.value = districtSelect.value || '';
        }

        function fillProvinces(rows, selectedId) {
            resetSelect(provinceSelect, 'Chọn tỉnh/thành');
            (rows || []).forEach(function (row) {
                var option = document.createElement('option');
                option.value = String(row.id);
                option.textContent = row.name + ' (' + row.id + ')';
                provinceSelect.appendChild(option);
            });

            if (selectedId) {
                provinceSelect.value = String(selectedId);
            }

            hiddenProvince.value = provinceSelect.value || '';
        }

        function fillWards(rows, selectedCode) {
            resetSelect(wardSelect, 'Chọn phường/xã');
            (rows || []).forEach(function (row) {
                var option = document.createElement('option');
                option.value = String(row.code);
                option.textContent = row.name + ' (' + row.code + ')';
                wardSelect.appendChild(option);
            });

            if (selectedCode) {
                wardSelect.value = String(selectedCode);
            }

            hiddenWard.value = wardSelect.value || '';
        }

        function loadWards(districtId, selectedCode) {
            if (!districtId) {
                fillWards([], '');
                return;
            }

            fetch(wardsUrl + '?district_id=' + encodeURIComponent(districtId), {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    if (!json || json.ok !== true) {
                        throw new Error('ward load failed');
                    }
                    fillWards(json.data || [], selectedCode || '');
                })
                .catch(function () {
                    resetSelect(wardSelect, 'Không tải được phường/xã');
                });
        }

        function loadDistricts(provinceId, selectedDistrict, selectedWard) {
            if (!provinceId) {
                resetSelect(districtSelect, 'Chọn tỉnh/thành trước');
                resetSelect(wardSelect, 'Chọn phường/xã');
                hiddenDistrict.value = '';
                hiddenWard.value = '';
                return;
            }

            fetch(districtsUrl + '?province_id=' + encodeURIComponent(provinceId), {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    if (!json || json.ok !== true) {
                        throw new Error('district load failed');
                    }
                    fillDistricts(json.data || [], selectedDistrict || '');
                    loadWards(districtSelect.value || '', selectedWard || '');
                })
                .catch(function () {
                    resetSelect(districtSelect, 'Không tải được quận/huyện');
                    resetSelect(wardSelect, 'Không tải được phường/xã');
                });
        }

        districtSelect.addEventListener('change', function () {
            hiddenDistrict.value = districtSelect.value || '';
            loadWards(districtSelect.value || '', '');
        });

        wardSelect.addEventListener('change', function () {
            hiddenWard.value = wardSelect.value || '';
        });

        provinceSelect.addEventListener('change', function () {
            hiddenProvince.value = provinceSelect.value || '';
            loadDistricts(provinceSelect.value || '', '', '');
        });

        fetch(provincesUrl, {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json || json.ok !== true) {
                    throw new Error('province load failed');
                }

                var selectedProvince = provinceSelect.getAttribute('data-selected') || hiddenProvince.value || '';
                var selectedDistrict = districtSelect.getAttribute('data-selected') || hiddenDistrict.value || '';
                var selectedWard = wardSelect.getAttribute('data-selected') || hiddenWard.value || '';

                fillProvinces(json.data || [], selectedProvince);
                loadDistricts(provinceSelect.value || '', selectedDistrict, selectedWard);
            })
            .catch(function () {
                resetSelect(provinceSelect, 'Không tải được tỉnh/thành');
                resetSelect(districtSelect, 'Không tải được quận/huyện');
                resetSelect(wardSelect, 'Không tải được phường/xã');
            });
    });
    </script>
    @endpush
@endif
