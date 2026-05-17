@extends('layouts.admin')

@section('title', 'Thêm đối soát COD')
@section('page-title', 'Thêm đối soát COD')

@section('content')
    <section class="admin-panel">
        <form method="POST" action="{{ route('cod.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Đơn hàng</label>
                <select name="ma_don_hang" class="w-full rounded-lg border-gray-300 text-sm">
                    @foreach ($orders as $order)
                        <option value="{{ $order->ma_don_hang }}" @selected((int) old('ma_don_hang') === (int) $order->ma_don_hang)>{{ $order->ma_tracking }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Hãng vận chuyển</label>
                <select name="ma_hang_van_chuyen" class="w-full rounded-lg border-gray-300 text-sm">
                    @foreach ($carriers as $carrier)
                        <option value="{{ $carrier->ma_hang_van_chuyen }}" @selected((int) old('ma_hang_van_chuyen') === (int) $carrier->ma_hang_van_chuyen)>{{ $carrier->ten_hang }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input label="COD kỳ vọng" name="cod_ky_vong" type="number" :value="old('cod_ky_vong', 0)" />
            <x-ui.input label="COD thực nhận" name="cod_thuc_nhan" type="number" :value="old('cod_thuc_nhan', 0)" />
            <x-ui.input label="Ngày đối soát" name="ngay_doi_soat" type="datetime-local" :value="old('ngay_doi_soat')" />
            <x-ui.input label="Trạng thái" name="trang_thai" :value="old('trang_thai', 'moi')" />

            <div class="sm:col-span-2 flex justify-end gap-2">
                <a href="{{ route('cod.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <x-ui.button type="submit" variant="primary">Thêm</x-ui.button>
            </div>
        </form>
    </section>
@endsection
