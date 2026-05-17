@extends('layouts.admin')

@section('title', 'Quản lý người dùng')
@section('page-title', 'Quản lý người dùng')

@section('content')
    @php
        $roleLabels = [
            'admin' => 'Admin',
            'chu_shop' => 'Chủ shop',
            'quan_ly_chanh_xe' => 'Quản lý chành xe',
        ];
    @endphp

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="admin-panel">
            <div class="mb-4 flex justify-end">
                <a href="{{ route('users.create') }}" class="btn-primary">
                    Thêm người dùng
                </a>
            </div>

            <div class="table-surface">
                <table class="min-w-full text-sm">
                    <thead class="admin-table-head">
                        <tr>
                            <th class="admin-table-cell">ID</th>
                            <th class="admin-table-cell">Họ tên</th>
                            <th class="admin-table-cell">Tên shop/chành xe</th>
                            <th class="admin-table-cell">Tài khoản</th>
                            <th class="admin-table-cell">Vai trò</th>
                            <th class="admin-table-cell">Trạng thái</th>
                            <th class="admin-table-cell">Duyệt tài khoản</th>
                            <th class="admin-table-cell text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="admin-table-body">
                        @forelse ($users as $user)
                            <tr>
                                <td class="admin-table-cell">{{ $user->ma_nguoi_dung }}</td>
                                <td class="admin-table-cell">{{ $user->ho_ten }}</td>
                                <td class="admin-table-cell">{{ $user->ten_don_vi ?: '-' }}</td>
                                <td class="admin-table-cell">{{ $user->ten_dang_nhap }}</td>
                                <td class="admin-table-cell">{{ $roleLabels[$user->vai_tro] ?? $user->vai_tro }}</td>
                                <td class="admin-table-cell">{{ $user->trang_thai ? 'Hoạt động' : 'Khóa' }}</td>
                                <td class="admin-table-cell">
                                    @if ((int) $user->trang_thai_duyet === 1)
                                        <x-ui.badge status="da_duyet" label="Đã duyệt" />
                                    @elseif ((int) $user->trang_thai_duyet === 2)
                                        <x-ui.badge status="tu_choi" label="Từ chối" />
                                    @else
                                        <x-ui.badge status="cho_duyet" label="Chờ duyệt" />
                                    @endif
                                </td>
                                <td class="admin-table-cell">
                                    <div class="flex justify-end gap-2">
                                        @if ((int) $user->trang_thai_duyet === 0)
                                            <form method="POST" action="{{ route('users.approve', $user) }}">
                                                @csrf
                                                <button type="submit" class="btn-success px-3 py-1.5">Duyệt</button>
                                            </form>
                                            <form method="POST" action="{{ route('users.reject', $user) }}" class="js-reject-user-form">
                                                @csrf
                                                <input type="hidden" name="ly_do_tu_choi" value="">
                                                <button type="submit" class="btn-secondary border-amber-300 bg-amber-50 px-3 py-1.5 text-amber-700 hover:bg-amber-100">Từ chối</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('users.show', $user) }}" class="btn-secondary px-3 py-1.5">Chi tiết</a>
                                        <a href="{{ route('users.edit', $user) }}" class="btn-primary px-3 py-1.5">Sửa</a>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Xóa người dùng này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger px-3 py-1.5">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="admin-table-cell text-center text-slate-500">Chưa có người dùng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($users, 'links'))
                <div class="app-pagination">{{ $users->links() }}</div>
            @endif
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
