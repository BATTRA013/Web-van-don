@extends('layouts.admin')

@section('title', 'Chi tiết người dùng')
@section('page-title', 'Chi tiết người dùng')

@section('content')
    @php
        $roleLabels = [
            'admin' => 'Admin',
            'chu_shop' => 'Chủ shop',
            'quan_ly_chanh_xe' => 'Quản lý chành xe',
        ];
        $unitLabel = match ((string) $userModel->vai_tro) {
            'chu_shop' => 'Tên shop',
            'quan_ly_chanh_xe' => 'Tên chành xe',
            default => 'Tên đơn vị',
        };
    @endphp

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="admin-panel">
            <h2 class="admin-panel-title">{{ $userModel->ho_ten }}</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                <p><span class="font-semibold text-gray-700">ID:</span> {{ $userModel->ma_nguoi_dung }}</p>
                <p><span class="font-semibold text-gray-700">{{ $unitLabel }}:</span> {{ $userModel->ten_don_vi ?: '-' }}</p>
                <p><span class="font-semibold text-gray-700">Tài khoản:</span> {{ $userModel->ten_dang_nhap }}</p>
                <p><span class="font-semibold text-gray-700">Số điện thoại:</span> {{ $userModel->so_dien_thoai ?: '-' }}</p>
                <p><span class="font-semibold text-gray-700">Email:</span> {{ $userModel->email ?: '-' }}</p>
                <p><span class="font-semibold text-gray-700">MST:</span> {{ $userModel->mst ?: '-' }}</p>
                <p><span class="font-semibold text-gray-700">Địa chỉ:</span> {{ $userModel->dia_chi ?: '-' }}</p>
                <p><span class="font-semibold text-gray-700">Vai trò:</span> {{ $roleLabels[$userModel->vai_tro] ?? $userModel->vai_tro }}</p>
                <p><span class="font-semibold text-gray-700">Trạng thái:</span> {{ $userModel->trang_thai ? 'Hoạt động' : 'Khóa' }}</p>
                <p>
                    <span class="font-semibold text-gray-700">Duyệt tài khoản:</span>
                    @if ((int) $userModel->trang_thai_duyet === 1)
                        Đã duyệt
                    @elseif ((int) $userModel->trang_thai_duyet === 2)
                        Từ chối
                    @else
                        Chờ duyệt
                    @endif
                </p>
                <p><span class="font-semibold text-gray-700">Lý do từ chối:</span> {{ $userModel->ly_do_tu_choi ?: '-' }}</p>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Danh sách</a>
                @if ((int) $userModel->trang_thai_duyet === 0)
                    <form method="POST" action="{{ route('users.approve', $userModel) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Duyệt</button>
                    </form>
                    <form method="POST" action="{{ route('users.reject', $userModel) }}" class="js-reject-user-form">
                        @csrf
                        <input type="hidden" name="ly_do_tu_choi" value="">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100">Từ chối</button>
                    </form>
                @endif
                <a href="{{ route('users.edit', $userModel) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Sửa</a>
                <form method="POST" action="{{ route('users.destroy', $userModel) }}" onsubmit="return confirm('Xóa người dùng này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Xóa</button>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.js-reject-user-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const reason = window.prompt('Nhập lý do từ chối tài khoản (bắt buộc):');

                if (reason === null) {
                    event.preventDefault();

                    return;
                }

                const normalizedReason = reason.trim();

                if (!normalizedReason) {
                    event.preventDefault();
                    window.alert('Vui lòng nhập lý do từ chối.');

                    return;
                }

                form.querySelector('input[name="ly_do_tu_choi"]').value = normalizedReason;
            });
        });
    </script>
@endpush
