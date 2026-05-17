@extends('layouts.admin')

@section('title', 'Thêm người dùng')
@section('page-title', 'Thêm người dùng')

@section('content')
    <section class="admin-panel">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf
            <x-ui.input label="Họ tên" name="ho_ten" :value="old('ho_ten')" />
            <x-ui.input label="Tên shop/chành xe" name="ten_don_vi" :value="old('ten_don_vi')" />
            <x-ui.input label="Tài khoản" name="ten_dang_nhap" :value="old('ten_dang_nhap')" />
            <x-ui.input label="Số điện thoại" name="so_dien_thoai" :value="old('so_dien_thoai')" />
            <x-ui.input label="Email" name="email" type="email" :value="old('email')" />
            <x-ui.input label="MST" name="mst" :value="old('mst')" />
            <x-ui.input label="Địa chỉ" name="dia_chi" :value="old('dia_chi')" />
            <x-ui.input label="Mật khẩu" name="mat_khau" type="password" />

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Vai trò</label>
                <select name="vai_tro" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="admin" @selected(old('vai_tro') === 'admin')>Admin</option>
                    <option value="chu_shop" @selected(old('vai_tro', 'chu_shop') === 'chu_shop')>Chủ shop</option>
                    <option value="quan_ly_chanh_xe" @selected(old('vai_tro') === 'quan_ly_chanh_xe')>Quản lý chành xe</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Trạng thái</label>
                <select name="trang_thai" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="1">Hoạt động</option>
                    <option value="0">Khóa</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <x-ui.button type="submit" variant="primary">Thêm</x-ui.button>
            </div>
        </form>
    </section>
@endsection
