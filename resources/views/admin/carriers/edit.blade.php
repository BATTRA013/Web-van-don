@extends('layouts.admin')

@section('title', 'Sửa nhà xe')
@section('page-title', 'Sửa nhà xe')

@section('content')
    <section class="admin-panel">
        <form method="POST" action="{{ route('carriers.update', $carrier) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-ui.input label="Tên nhà xe" name="ten_nha_xe" :value="old('ten_nha_xe', $carrier->ten_nha_xe)" />
            <x-ui.input label="Số điện thoại" name="so_dien_thoai" :value="old('so_dien_thoai', $carrier->so_dien_thoai)" />
            <x-ui.input label="Tuyến đường" name="tuyen_duong" :value="old('tuyen_duong', $carrier->tuyen_duong)" />

            <div class="flex justify-end gap-2">
                <a href="{{ route('carriers.show', $carrier) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <x-ui.button type="submit" variant="primary">Lưu</x-ui.button>
            </div>
        </form>
    </section>
@endsection
