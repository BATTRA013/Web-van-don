@extends('layouts.admin')

@section('title', 'Tạo đơn mới')
@section('page-title', 'Tạo đơn mới')

@section('content')
    <form action="{{ route('orders.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="admin-panel">
                <h2 class="admin-panel-title">Thông tin người nhận</h2>
                <p class="admin-panel-desc">Lưu đơn hàng vào hệ thống trước khi đẩy sang hãng vận chuyển.</p>

                <div class="mt-4 space-y-4">
                    <x-ui.input label="Họ và tên người nhận" name="receiver_name" :value="old('receiver_name')" placeholder="Ví dụ: Nguyễn Văn A" />
                    <x-ui.input label="Số điện thoại" name="receiver_phone" :value="old('receiver_phone')" placeholder="Ví dụ: 0901 234 567" />
                    <x-ui.input label="Địa chỉ nhận hàng" name="receiver_address" :value="old('receiver_address')" placeholder="Số nhà, đường, quận/huyện" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="to_province_select" class="mb-1.5 block text-sm font-medium text-gray-700">Tỉnh/thành</label>
                            <select id="to_province_select" class="w-full rounded-lg border-gray-300 text-sm" data-selected="{{ old('to_province_id', 1) }}">
                                <option value="">Chọn tỉnh/thành</option>
                            </select>
                            <input type="hidden" name="to_province_id" id="to_province_id" value="{{ old('to_province_id', 1) }}">
                        </div>
                        <div>
                            <label for="to_district_select" class="mb-1.5 block text-sm font-medium text-gray-700">Quận/huyện</label>
                            <select id="to_district_select" class="w-full rounded-lg border-gray-300 text-sm" data-selected="{{ old('to_district_id') }}">
                                <option value="">Chọn quận/huyện</option>
                            </select>
                            <input type="hidden" name="to_district_id" id="to_district_id" value="{{ old('to_district_id') }}">
                        </div>
                        <div>
                            <label for="to_ward_select" class="mb-1.5 block text-sm font-medium text-gray-700">Phường/xã</label>
                            <select id="to_ward_select" class="w-full rounded-lg border-gray-300 text-sm" data-selected="{{ old('to_ward_code') }}">
                                <option value="">Chọn phường/xã</option>
                            </select>
                            <input type="hidden" name="to_ward_code" id="to_ward_code" value="{{ old('to_ward_code') }}">
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <h2 class="admin-panel-title">Thông số hàng hóa</h2>
                <p class="admin-panel-desc">Các thông tin này dùng để tính phí và đồng bộ sang GHN sau này.</p>

                <div class="mt-4 space-y-4">
                    <x-ui.input label="Tên hàng" name="item_name" :value="old('item_name')" placeholder="Ví dụ: Mỹ phẩm" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Khối lượng (gram)" name="item_weight" type="number" min="1" step="1" :value="old('item_weight', 1000)" placeholder="1000" />
                        <x-ui.input label="Số lượng" name="item_quantity" type="number" min="1" step="1" :value="old('item_quantity', 1)" placeholder="1" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Đơn giá (VNĐ)" name="item_price" type="number" min="0" step="1000" :value="old('item_price', 0)" placeholder="0" />
                        <x-ui.input label="Tiền COD (VNĐ)" name="cod_value" type="number" min="0" step="1000" :value="old('cod_value', 0)" placeholder="0" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.input label="Dài (cm)" name="length" type="number" min="0" :value="old('length', 20)" placeholder="20" />
                        <x-ui.input label="Rộng (cm)" name="width" type="number" min="0" :value="old('width', 20)" placeholder="20" />
                        <x-ui.input label="Cao (cm)" name="height" type="number" min="0" :value="old('height', 20)" placeholder="20" />
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Hủy
            </a>
            <x-ui.button type="submit" variant="primary">Lưu đơn vào hệ thống</x-ui.button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var provinceSelect = document.getElementById('to_province_select');
    var hiddenProvince = document.getElementById('to_province_id');
    var districtSelect = document.getElementById('to_district_select');
    var wardSelect = document.getElementById('to_ward_select');
    var hiddenDistrict = document.getElementById('to_district_id');
    var hiddenWard = document.getElementById('to_ward_code');
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