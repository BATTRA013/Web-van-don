@extends('layouts.admin')

@section('title', 'Tao van don da hang')
@section('page-title', 'Tao van don da hang')

@section('content')
    <section class="admin-panel">
        <h2 class="admin-panel-title">Chon don tu he thong de tao van don</h2>
        <p class="admin-panel-desc">Mot man hinh chung cho GHN va Viettel Post. Chon hang van chuyen trong form ben duoi.</p>

        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-3 py-2 font-medium">Ma tracking</th>
                        <th class="px-3 py-2 font-medium">Nguoi nhan</th>
                        <th class="px-3 py-2 font-medium">SDT</th>
                        <th class="px-3 py-2 font-medium">COD</th>
                        <th class="px-3 py-2 font-medium text-right">Thao tac</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2">{{ $order->ma_tracking }}</td>
                            <td class="px-3 py-2">{{ $order->ten_nguoi_nhan }}</td>
                            <td class="px-3 py-2">{{ $order->sdt_nguoi_nhan }}</td>
                            <td class="px-3 py-2">{{ number_format((float) $order->tien_cod, 0, ',', '.') }}d</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('orders.shipments.create', ['order_id' => $order->ma_don_hang, 'carrier_name' => old('carrier_name', $selectedCarrier ?? 'GHN')]) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50">
                                    Nap vao form
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-3 text-center text-gray-500">Chua co don trong he thong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <form action="{{ route('orders.shipments.store') }}" method="POST" class="space-y-6">
        @csrf

        @if ($selectedOrder)
            <input type="hidden" name="source_order_id" value="{{ $selectedOrder->ma_don_hang }}">
        @endif

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->has('shipment'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first('shipment') }}
            </div>
        @endif

        @if ($errors->has('ghn'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first('ghn') }}
            </div>
        @endif

        <section class="admin-panel" id="carrier-config-panel" data-carrier-defaults='@json($carrierDefaults ?? [])'>
            <h2 class="admin-panel-title">Cau hinh hang van chuyen</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="carrier_name" class="mb-1.5 block text-sm font-medium text-gray-700">Hang van chuyen</label>
                    <select id="carrier_name" name="carrier_name" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach (($supportedCarriers ?? ['GHN', 'VIETTELPOST']) as $carrier)
                            <option value="{{ $carrier }}" @selected(old('carrier_name', $selectedCarrier ?? 'GHN') === $carrier)>
                                {{ $carrier === 'VIETTELPOST' ? 'Viettel Post' : $carrier }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="field-token-wrap">
                    <x-ui.input label="Token API (override)" name="token" :value="old('token', data_get($carrierDefaults, old('carrier_name', $selectedCarrier ?? 'GHN').'.token'))" placeholder="De trong de dung cau hinh da luu" />
                </div>
                <div id="field-shop-id-wrap">
                    <x-ui.input label="Shop ID (neu co)" name="shop_id" :value="old('shop_id', data_get($carrierDefaults, old('carrier_name', $selectedCarrier ?? 'GHN').'.shop_id'))" placeholder="GHN can truong nay" />
                </div>
            </div>

            <div id="carrier-field-guide" class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-700">
                <p id="guide-ghn">GHN: can dien day du ma quan/huyen va ma phuong/xa theo ma GHN (cac truong co duoi from_... va to_...).</p>
                <p id="guide-viettel" class="hidden">Viettel Post: khong can Shop ID. 2 truong bat buoc la viettel_groupaddress_id va viettel_customer_id. Cac combobox dia chi chon theo ten, he thong tu map sang ma dia gioi Viettel truoc khi gui API.</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" id="viettel-extra">
                <x-ui.input label="Viettel groupaddress_id" name="viettel_groupaddress_id" type="number" :value="old('viettel_groupaddress_id', data_get($carrierDefaults, 'VIETTELPOST.groupaddress_id'))" placeholder="Vi du: 28298046" />
                <x-ui.input label="Viettel customer_id" name="viettel_customer_id" type="number" :value="old('viettel_customer_id', data_get($carrierDefaults, 'VIETTELPOST.customer_id'))" placeholder="Vi du: 18088142" />
                <x-ui.input label="Viettel sender_province" name="viettel_sender_province" type="number" :value="old('viettel_sender_province', data_get($carrierDefaults, 'VIETTELPOST.sender_province_id'))" placeholder="De trong de lay theo tinh gui" />
                <x-ui.input label="Viettel receiver_province" name="viettel_receiver_province" type="number" :value="old('viettel_receiver_province', data_get($carrierDefaults, 'VIETTELPOST.receiver_province_id'))" placeholder="De trong de lay theo tinh nhan" />
                <x-ui.input label="Viettel ORDER_SERVICE" name="viettel_order_service" :value="old('viettel_order_service', data_get($carrierDefaults, 'VIETTELPOST.order_service', 'PHS'))" placeholder="Vi du: PHS" />
                <x-ui.input label="Viettel PRODUCT_TYPE" name="viettel_product_type" :value="old('viettel_product_type', data_get($carrierDefaults, 'VIETTELPOST.product_type', 'HH'))" placeholder="Vi du: HH" />
                <div>
                    <label for="viettel_order_payment" class="mb-1.5 block text-sm font-medium text-gray-700">Viettel ORDER_PAYMENT</label>
                    <select id="viettel_order_payment" name="viettel_order_payment" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="1" @selected((int) old('viettel_order_payment', data_get($carrierDefaults, 'VIETTELPOST.order_payment', 3)) === 1)>1 - Nguoi gui tra cuoc</option>
                        <option value="2" @selected((int) old('viettel_order_payment', data_get($carrierDefaults, 'VIETTELPOST.order_payment', 3)) === 2)>2 - Nguoi nhan tra cuoc</option>
                        <option value="3" @selected((int) old('viettel_order_payment', data_get($carrierDefaults, 'VIETTELPOST.order_payment', 3)) === 3)>3 - Mac dinh doi tac (khuyen nghi)</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="admin-panel">
                <h2 class="admin-panel-title">Thong tin nguoi gui</h2>
                <div class="mt-4 space-y-4">
                    <x-ui.input label="Ten nguoi gui" name="sender_name" :value="old('sender_name', config('services.ghn.from_name'))" placeholder="Vi du: Lam Thanh Huu" />
                    <x-ui.input label="SDT nguoi gui" name="sender_phone" :value="old('sender_phone', config('services.ghn.from_phone'))" placeholder="Vi du: 0869676724" />
                    <x-ui.input label="Dia chi lay hang" name="sender_address" :value="old('sender_address', config('services.ghn.from_address'))" placeholder="Vi du: 18 Ung Van Khiem, My Xuyen" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="from_province_select" class="mb-1.5 block text-sm font-medium text-gray-700">Tinh/thanh lay hang</label>
                            <select id="from_province_select" class="w-full rounded-lg border-gray-300 text-sm" data-geo="province" data-target="from" data-selected="{{ old('from_province_id', '') }}">
                                <option value="">Chon tinh/thanh</option>
                            </select>
                            <input type="hidden" name="from_province_id" id="from_province_id" value="{{ old('from_province_id', '') }}">
                            <input type="hidden" name="from_province_name" id="from_province_name" value="{{ old('from_province_name', '') }}">
                        </div>
                        <div>
                            <label for="from_district_select" class="mb-1.5 block text-sm font-medium text-gray-700">Quan/huyen lay hang</label>
                            <select id="from_district_select" class="w-full rounded-lg border-gray-300 text-sm" data-geo="district" data-target="from" data-selected="{{ old('from_district_id', config('services.ghn.from_district_id')) }}">
                                <option value="">Chon quan/huyen</option>
                            </select>
                            <input type="hidden" name="from_district_id" id="from_district_id" value="{{ old('from_district_id', config('services.ghn.from_district_id')) }}">
                            <input type="hidden" name="from_district_name" id="from_district_name" value="{{ old('from_district_name', '') }}">
                        </div>
                        <div>
                            <label for="from_ward_select" class="mb-1.5 block text-sm font-medium text-gray-700">Phuong/xa lay hang</label>
                            <select id="from_ward_select" class="w-full rounded-lg border-gray-300 text-sm" data-geo="ward" data-target="from" data-selected="{{ old('from_ward_code', config('services.ghn.from_ward_code')) }}">
                                <option value="">Chon phuong/xa</option>
                            </select>
                            <input type="hidden" name="from_ward_code" id="from_ward_code" value="{{ old('from_ward_code', config('services.ghn.from_ward_code')) }}">
                            <input type="hidden" name="from_ward_name" id="from_ward_name" value="{{ old('from_ward_name', '') }}">
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <h2 class="admin-panel-title">Thong tin nguoi nhan</h2>
                <div class="mt-4 space-y-4">
                    <x-ui.input label="Ho ten nguoi nhan" name="receiver_name" :value="old('receiver_name', $prefill['receiver_name'] ?? '')" placeholder="Vi du: Nguyen Van A" />
                    <x-ui.input label="So dien thoai nguoi nhan" name="receiver_phone" :value="old('receiver_phone', $prefill['receiver_phone'] ?? '')" placeholder="Vi du: 0912345678" />
                    <x-ui.input label="Dia chi nhan" name="receiver_address" :value="old('receiver_address', $prefill['receiver_address'] ?? '')" placeholder="So nha, duong, phuong, quan" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="to_province_select" class="mb-1.5 block text-sm font-medium text-gray-700">Tinh/thanh nhan</label>
                            <select id="to_province_select" class="w-full rounded-lg border-gray-300 text-sm" data-geo="province" data-target="to" data-selected="{{ old('to_province_id', $prefill['to_province_id'] ?? '') }}">
                                <option value="">Chon tinh/thanh</option>
                            </select>
                            <input type="hidden" name="to_province_id" id="to_province_id" value="{{ old('to_province_id', $prefill['to_province_id'] ?? '') }}">
                            <input type="hidden" name="to_province_name" id="to_province_name" value="{{ old('to_province_name', '') }}">
                        </div>
                        <div>
                            <label for="to_district_select" class="mb-1.5 block text-sm font-medium text-gray-700">Quan/huyen nhan</label>
                            <select id="to_district_select" class="w-full rounded-lg border-gray-300 text-sm" data-geo="district" data-target="to" data-selected="{{ old('to_district_id', $prefill['to_district_id'] ?? '') }}">
                                <option value="">Chon quan/huyen</option>
                            </select>
                            <input type="hidden" name="to_district_id" id="to_district_id" value="{{ old('to_district_id', $prefill['to_district_id'] ?? '') }}">
                            <input type="hidden" name="to_district_name" id="to_district_name" value="{{ old('to_district_name', '') }}">
                        </div>
                        <div>
                            <label for="to_ward_select" class="mb-1.5 block text-sm font-medium text-gray-700">Phuong/xa nhan</label>
                            <select id="to_ward_select" class="w-full rounded-lg border-gray-300 text-sm" data-geo="ward" data-target="to" data-selected="{{ old('to_ward_code', $prefill['to_ward_code'] ?? '') }}">
                                <option value="">Chon phuong/xa</option>
                            </select>
                            <input type="hidden" name="to_ward_code" id="to_ward_code" value="{{ old('to_ward_code', $prefill['to_ward_code'] ?? '') }}">
                            <input type="hidden" name="to_ward_name" id="to_ward_name" value="{{ old('to_ward_name', '') }}">
                        </div>
                    </div>

                    <div>
                        <label for="receiver_note" class="mb-1.5 block text-sm font-medium text-gray-700">Ghi chu</label>
                        <textarea id="receiver_note" name="note" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('note') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="admin-panel xl:col-span-2">
                <h2 class="admin-panel-title">Thong so hang hoa</h2>
                <div class="mt-4 space-y-4">
                    <x-ui.input label="Ten hang" name="item_name" :value="old('item_name', $prefill['item_name'] ?? '')" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Khoi luong (gram)" name="item_weight" type="number" min="1" step="1" :value="old('item_weight', $prefill['item_weight'] ?? 1000)" />
                        <x-ui.input label="So kien" name="item_quantity" type="number" min="1" step="1" :value="old('item_quantity', $prefill['item_quantity'] ?? 1)" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Gia tri COD (VND)" name="cod_value" type="number" min="0" step="1000" :value="old('cod_value', $prefill['cod_value'] ?? 0)" placeholder="Vi du: 150000" />
                        <x-ui.input label="Loai dich vu GHN (service_type_id)" name="service_type_id" type="number" min="1" step="1" :value="old('service_type_id', 2)" placeholder="Thuong dung: 2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.input label="Dai (cm)" name="length" type="number" min="1" :value="old('length', $prefill['length'] ?? 20)" />
                        <x-ui.input label="Rong (cm)" name="width" type="number" min="1" :value="old('width', $prefill['width'] ?? 20)" />
                        <x-ui.input label="Cao (cm)" name="height" type="number" min="1" :value="old('height', $prefill['height'] ?? 20)" />
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Huy
            </a>
            <x-ui.button type="submit" variant="primary">Tao van don</x-ui.button>
        </div>
    </form>

    @if (session('shipment_create_order_result') || session('ghn_create_order_result'))
        @php
            $result = session('shipment_create_order_result') ?: session('ghn_create_order_result');
            $carrier = session('shipment_result_carrier', old('carrier_name', $selectedCarrier ?? 'GHN'));
        @endphp

        <section class="admin-panel p-6">
            <div class="flex items-center gap-2">
                <h3 class="text-base font-semibold text-gray-900">Ket qua tao van don {{ $carrier }}</h3>
                <x-ui.badge :status="$result['ok'] ? 'da_giao' : 'hoan'" :label="$result['ok'] ? 'Thanh cong' : 'That bai'" />
            </div>

            <p class="mt-2 text-sm text-gray-600">HTTP {{ $result['status'] ?? 'N/A' }} - {{ $result['message'] ?? 'Unknown' }}</p>

            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-slate-900 p-4">
                <pre class="text-xs text-slate-100">{{ json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('carrier-config-panel');
    var carrierSelect = document.getElementById('carrier_name');

    if (!panel || !carrierSelect) {
        return;
    }

    var defaults = {};
    try {
        defaults = JSON.parse(panel.getAttribute('data-carrier-defaults') || '{}');
    } catch (e) {
        defaults = {};
    }

    var tokenInput = document.querySelector('input[name="token"]');
    var shopIdInput = document.querySelector('input[name="shop_id"]');
    var groupAddressInput = document.querySelector('input[name="viettel_groupaddress_id"]');
    var customerIdInput = document.querySelector('input[name="viettel_customer_id"]');
    var orderPaymentInput = document.querySelector('select[name="viettel_order_payment"]');
    var shopIdWrap = document.getElementById('field-shop-id-wrap');
    var viettelExtra = document.getElementById('viettel-extra');
    var guideGhn = document.getElementById('guide-ghn');
    var guideViettel = document.getElementById('guide-viettel');
    var geoApi = {
        provinces: @json(route('orders.ghn.meta.provinces')),
        districts: @json(route('orders.ghn.meta.districts')),
        wards: @json(route('orders.ghn.meta.wards'))
    };

    var provinceSelects = {
        from: document.getElementById('from_province_select'),
        to: document.getElementById('to_province_select')
    };

    var districtSelects = {
        from: document.getElementById('from_district_select'),
        to: document.getElementById('to_district_select')
    };
    var wardSelects = {
        from: document.getElementById('from_ward_select'),
        to: document.getElementById('to_ward_select')
    };
    var hiddenDistrictInputs = {
        from: document.getElementById('from_district_id'),
        to: document.getElementById('to_district_id')
    };
    var hiddenProvinceInputs = {
        from: document.getElementById('from_province_id'),
        to: document.getElementById('to_province_id')
    };
    var hiddenProvinceNameInputs = {
        from: document.getElementById('from_province_name'),
        to: document.getElementById('to_province_name')
    };
    var hiddenDistrictNameInputs = {
        from: document.getElementById('from_district_name'),
        to: document.getElementById('to_district_name')
    };
    var hiddenWardInputs = {
        from: document.getElementById('from_ward_code'),
        to: document.getElementById('to_ward_code')
    };
    var hiddenWardNameInputs = {
        from: document.getElementById('from_ward_name'),
        to: document.getElementById('to_ward_name')
    };

    function extractDisplayName(selectEl) {
        if (!selectEl || !selectEl.options || selectEl.selectedIndex < 0) {
            return '';
        }

        var selectedOption = selectEl.options[selectEl.selectedIndex];
        var fullText = selectedOption ? String(selectedOption.textContent || '') : '';
        return fullText.split('(')[0].trim();
    }

    function setValue(el, value) {
        if (!el) {
            return;
        }
        el.value = value == null ? '' : String(value);
    }

    function shouldShowGeoIds() {
        return (carrierSelect.value || 'GHN').toUpperCase() === 'GHN';
    }

    function refreshGeoOptionLabels() {
        var showIds = shouldShowGeoIds();

        ['from', 'to'].forEach(function (target) {
            [provinceSelects[target], districtSelects[target], wardSelects[target]].forEach(function (selectEl) {
                if (!selectEl) {
                    return;
                }

                Array.prototype.forEach.call(selectEl.options, function (option) {
                    if (!option || option.value === '') {
                        return;
                    }

                    var name = option.getAttribute('data-name') || option.textContent;
                    var code = option.getAttribute('data-code') || option.value;
                    option.textContent = showIds ? (name + ' (' + code + ')') : name;
                });
            });
        });
    }

    function applyCarrierDefaults() {
        var carrier = (carrierSelect.value || 'GHN').toUpperCase();
        var carrierDefaults = defaults[carrier] || {};

        setValue(tokenInput, carrierDefaults.token || '');
        setValue(shopIdInput, carrierDefaults.shop_id || '');

        if (carrier === 'VIETTELPOST') {
            if (shopIdWrap) {
                shopIdWrap.classList.add('hidden');
            }
            if (viettelExtra) {
                viettelExtra.classList.remove('hidden');
            }
            if (guideGhn) {
                guideGhn.classList.add('hidden');
            }
            if (guideViettel) {
                guideViettel.classList.remove('hidden');
            }

            setValue(groupAddressInput, carrierDefaults.groupaddress_id || '');
            setValue(customerIdInput, carrierDefaults.customer_id || '');
            if (orderPaymentInput) {
                orderPaymentInput.value = String(carrierDefaults.order_payment || 3);
            }
        } else {
            if (shopIdWrap) {
                shopIdWrap.classList.remove('hidden');
            }
            if (viettelExtra) {
                viettelExtra.classList.add('hidden');
            }
            if (guideGhn) {
                guideGhn.classList.remove('hidden');
            }
            if (guideViettel) {
                guideViettel.classList.add('hidden');
            }
        }

        refreshGeoOptionLabels();
    }

    function resetSelect(selectEl, placeholder) {
        if (!selectEl) {
            return;
        }
        selectEl.innerHTML = '';
        var placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        selectEl.appendChild(placeholderOption);
    }

    function fillProvinceSelect(selectEl, provinces, selectedValue) {
        resetSelect(selectEl, 'Chon tinh/thanh');
        var showIds = shouldShowGeoIds();

        (provinces || []).forEach(function (item) {
            var option = document.createElement('option');
            option.value = String(item.id);
            option.setAttribute('data-name', item.name);
            option.setAttribute('data-code', String(item.id));
            option.textContent = showIds ? (item.name + ' (' + item.id + ')') : item.name;
            selectEl.appendChild(option);
        });

        if (selectedValue) {
            selectEl.value = String(selectedValue);
        }

        var target = selectEl.getAttribute('data-target');
        if (target && hiddenProvinceInputs[target]) {
            updateHiddenInput(hiddenProvinceInputs[target], selectEl.value || '');
            updateHiddenInput(hiddenProvinceNameInputs[target], extractDisplayName(selectEl));
        }
    }

    function fillDistrictSelect(selectEl, districts, selectedValue) {
        resetSelect(selectEl, 'Chon quan/huyen');
        var showIds = shouldShowGeoIds();

        (districts || []).forEach(function (item) {
            var option = document.createElement('option');
            option.value = String(item.id);
            option.setAttribute('data-name', item.name);
            option.setAttribute('data-code', String(item.id));
            option.textContent = showIds ? (item.name + ' (' + item.id + ')') : item.name;
            selectEl.appendChild(option);
        });

        if (selectedValue) {
            selectEl.value = String(selectedValue);
        }
    }

    function fillWardSelect(selectEl, wards, selectedValue) {
        resetSelect(selectEl, 'Chon phuong/xa');
        var showIds = shouldShowGeoIds();

        (wards || []).forEach(function (item) {
            var option = document.createElement('option');
            option.value = String(item.code);
            option.setAttribute('data-name', item.name);
            option.setAttribute('data-code', String(item.code));
            option.textContent = showIds ? (item.name + ' (' + item.code + ')') : item.name;
            selectEl.appendChild(option);
        });

        if (selectedValue) {
            selectEl.value = String(selectedValue);
        }
    }

    function updateHiddenInput(inputEl, value) {
        if (!inputEl) {
            return;
        }
        inputEl.value = value == null ? '' : String(value);
    }

    function loadWards(target, districtId, selectedWard) {
        var wardSelect = wardSelects[target];

        if (!wardSelect) {
            return Promise.resolve();
        }

        if (!districtId) {
            resetSelect(wardSelect, 'Chon phuong/xa');
            updateHiddenInput(hiddenWardInputs[target], '');
            return Promise.resolve();
        }

        return fetch(geoApi.wards + '?district_id=' + encodeURIComponent(districtId), {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json || json.ok !== true) {
                    throw new Error(json && json.message ? json.message : 'Khong tai duoc danh sach phuong/xa');
                }

                fillWardSelect(wardSelect, json.data || [], selectedWard || '');
                updateHiddenInput(hiddenWardInputs[target], wardSelect.value || '');
                updateHiddenInput(hiddenWardNameInputs[target], extractDisplayName(wardSelect));
            })
            .catch(function () {
                resetSelect(wardSelect, 'Khong tai duoc phuong/xa');
                updateHiddenInput(hiddenWardNameInputs[target], '');
            });
    }

    function loadDistricts(target, provinceId, selectedDistrict, selectedWard) {
        var districtSelect = districtSelects[target];

        if (!districtSelect) {
            return Promise.resolve();
        }

        if (!provinceId) {
            resetSelect(districtSelect, 'Chon quan/huyen');
            updateHiddenInput(hiddenDistrictInputs[target], '');
            return loadWards(target, '', '');
        }

        return fetch(geoApi.districts + '?province_id=' + encodeURIComponent(provinceId), {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json || json.ok !== true) {
                    throw new Error(json && json.message ? json.message : 'Khong tai duoc danh sach quan/huyen');
                }

                fillDistrictSelect(districtSelect, json.data || [], selectedDistrict || '');
                updateHiddenInput(hiddenDistrictInputs[target], districtSelect.value || '');
                updateHiddenInput(hiddenDistrictNameInputs[target], extractDisplayName(districtSelect));
                return loadWards(target, districtSelect.value || '', selectedWard || '');
            })
            .catch(function () {
                resetSelect(districtSelect, 'Khong tai duoc quan/huyen');
                resetSelect(wardSelects[target], 'Khong tai duoc phuong/xa');
                updateHiddenInput(hiddenDistrictInputs[target], '');
                updateHiddenInput(hiddenDistrictNameInputs[target], '');
                updateHiddenInput(hiddenWardInputs[target], '');
                updateHiddenInput(hiddenWardNameInputs[target], '');
            });
    }

    function bindGeoEvents(target) {
        var provinceSelect = provinceSelects[target];
        var districtSelect = districtSelects[target];
        var wardSelect = wardSelects[target];

        if (!provinceSelect || !districtSelect || !wardSelect) {
            return;
        }

        provinceSelect.addEventListener('change', function () {
            updateHiddenInput(hiddenProvinceInputs[target], provinceSelect.value || '');
            updateHiddenInput(hiddenProvinceNameInputs[target], extractDisplayName(provinceSelect));
            loadDistricts(target, provinceSelect.value || '', '', '');
        });

        districtSelect.addEventListener('change', function () {
            var districtValue = districtSelect.value || '';
            updateHiddenInput(hiddenDistrictInputs[target], districtValue);
            updateHiddenInput(hiddenDistrictNameInputs[target], extractDisplayName(districtSelect));
            loadWards(target, districtValue, '');
        });

        wardSelect.addEventListener('change', function () {
            updateHiddenInput(hiddenWardInputs[target], wardSelect.value || '');
            updateHiddenInput(hiddenWardNameInputs[target], extractDisplayName(wardSelect));
        });
    }

    function initializeGeoCombobox() {
        fetch(geoApi.provinces, {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (!json || json.ok !== true) {
                    throw new Error(json && json.message ? json.message : 'Khong tai duoc danh sach tinh/thanh');
                }

                ['from', 'to'].forEach(function (target) {
                    var provinceSelect = provinceSelects[target];
                    var districtSelect = districtSelects[target];
                    var wardSelect = wardSelects[target];

                    if (!provinceSelect || !districtSelect || !wardSelect) {
                        return;
                    }

                    var selectedProvince = provinceSelect.getAttribute('data-selected') || '';
                    var selectedDistrict = districtSelect.getAttribute('data-selected') || hiddenDistrictInputs[target].value || '';
                    var selectedWard = wardSelect.getAttribute('data-selected') || hiddenWardInputs[target].value || '';

                    fillProvinceSelect(provinceSelect, json.data || [], selectedProvince);

                    bindGeoEvents(target);
                    loadDistricts(target, provinceSelect.value || '', selectedDistrict, selectedWard);
                });
            })
            .catch(function () {
                ['from', 'to'].forEach(function (target) {
                    resetSelect(provinceSelects[target], 'Khong tai duoc tinh/thanh');
                    resetSelect(districtSelects[target], 'Khong tai duoc quan/huyen');
                    resetSelect(wardSelects[target], 'Khong tai duoc phuong/xa');
                });
            });
    }

    carrierSelect.addEventListener('change', applyCarrierDefaults);
    applyCarrierDefaults();
    initializeGeoCombobox();
});
</script>
@endpush
