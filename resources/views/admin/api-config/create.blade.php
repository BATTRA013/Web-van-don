@extends('layouts.admin')

@section('title', 'Thêm cấu hình API')
@section('page-title', 'Thêm cấu hình API')

@section('content')
    <section class="admin-panel p-6">
        <form method="POST" action="{{ route('api-config.store') }}" class="space-y-4">
            @csrf

            <x-ui.input label="Tên hãng" name="ten_hang" :value="old('ten_hang')" placeholder="Ví dụ: GHN" />
            <x-ui.input label="Token API" name="api_token" :value="old('api_token')" />
            <x-ui.input label="Shop ID" name="shop_id" :value="old('shop_id')" />

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Môi trường</label>
                <select name="moi_truong" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="1" @selected((int) old('moi_truong', 1) === 1)>prod</option>
                    <option value="0" @selected((int) old('moi_truong') === 0)>dev</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('api-config.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <x-ui.button type="submit" variant="primary">Thêm</x-ui.button>
            </div>
        </form>
    </section>
@endsection
