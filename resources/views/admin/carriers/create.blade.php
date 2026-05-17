@extends('layouts.admin')

@section('title', 'Thêm nhà xe')
@section('page-title', 'Thêm nhà xe')

@section('content')
    <section class="admin-panel">
        <form method="POST" action="{{ route('carriers.store') }}" class="space-y-4">
            @csrf
            <x-ui.input label="Tên nhà xe" name="ten_nha_xe" :value="old('ten_nha_xe')" />
            <x-ui.input label="Số điện thoại" name="so_dien_thoai" :value="old('so_dien_thoai')" />
            <x-ui.input label="Tuyến đường" name="tuyen_duong" :value="old('tuyen_duong')" />

            <div class="flex justify-end gap-2">
                <a href="{{ route('carriers.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <x-ui.button type="submit" variant="primary">Thêm</x-ui.button>
            </div>
        </form>
    </section>
@endsection
