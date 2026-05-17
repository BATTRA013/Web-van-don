@extends('layouts.admin')

@section('title', 'Sửa người dùng')
@section('page-title', 'Sửa người dùng')

@section('content')
    <section class="admin-panel">
        <form method="POST" action="{{ route('users.update', $userModel) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-ui.input label="Họ tên" name="ho_ten" :value="old('ho_ten', $userModel->ho_ten)" />
            <x-ui.input label="Tên shop/chành xe" name="ten_don_vi" :value="old('ten_don_vi', $userModel->ten_don_vi)" />
            <x-ui.input label="Tài khoản" name="ten_dang_nhap" :value="old('ten_dang_nhap', $userModel->ten_dang_nhap)" />
            <x-ui.input label="Số điện thoại" name="so_dien_thoai" :value="old('so_dien_thoai', $userModel->so_dien_thoai)" />
            <x-ui.input label="Email" name="email" type="email" :value="old('email', $userModel->email)" />
            <x-ui.input label="MST" name="mst" :value="old('mst', $userModel->mst)" />
            <x-ui.input label="Địa chỉ" name="dia_chi" :value="old('dia_chi', $userModel->dia_chi)" />
            <x-ui.input label="Mật khẩu mới (để trống nếu không đổi)" name="mat_khau" type="password" />

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Vai trò</label>
                <select name="vai_tro" class="w-full rounded-lg border-gray-300 text-sm">
                    @foreach (['admin' => 'Admin', 'chu_shop' => 'Chủ shop', 'quan_ly_chanh_xe' => 'Quản lý chành xe'] as $role => $label)
                        <option value="{{ $role }}" @selected(old('vai_tro', $userModel->vai_tro) === $role)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Trạng thái</label>
                <select name="trang_thai" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="1" @selected((int) old('trang_thai', $userModel->trang_thai) === 1)>Hoạt động</option>
                    <option value="0" @selected((int) old('trang_thai', $userModel->trang_thai) === 0)>Khóa</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Trạng thái duyệt</label>
                <select name="trang_thai_duyet" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="1" @selected((int) old('trang_thai_duyet', $userModel->trang_thai_duyet) === 1)>Đã duyệt</option>
                    <option value="0" @selected((int) old('trang_thai_duyet', $userModel->trang_thai_duyet) === 0)>Chờ duyệt</option>
                    <option value="2" @selected((int) old('trang_thai_duyet', $userModel->trang_thai_duyet) === 2)>Từ chối</option>
                </select>
            </div>

            <x-ui.input label="Lý do từ chối (nếu có)" name="ly_do_tu_choi" :value="old('ly_do_tu_choi', $userModel->ly_do_tu_choi)" />

            <div class="flex justify-end gap-2">
                <a href="{{ route('users.show', $userModel) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <x-ui.button type="submit" variant="primary">Lưu</x-ui.button>
            </div>
        </form>
    </section>
@endsection
