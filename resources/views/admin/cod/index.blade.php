@extends('layouts.admin')

@section('title', 'Đối soát COD')
@section('page-title', 'Đối soát COD')

@section('content')
    <div class="space-y-6">
        <section class="admin-panel">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('cod.auto') }}" class="flex flex-wrap items-center justify-end gap-3">
                @csrf
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="only_missing" value="1" class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-600">
                    Chi doi soat don chua co ban ghi
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    Gioi han so don quet
                    <input type="number" name="limit" value="300" min="1" max="1000" class="w-28 rounded-lg border-slate-300 text-sm focus:border-cyan-600 focus:ring-cyan-600">
                </label>
                <button type="submit" class="btn-primary">
                    Chay doi soat tu dong
                </button>
            </form>

            <p class="mt-3 text-xs text-slate-500">
                Luu y: Doi soat tu dong chi quet don co ma tracking va trang thai <span class="font-semibold">da_giao</span> hoac <span class="font-semibold">dang_van_chuyen</span> trong pham vi tai khoan hien tai.
            </p>
        </section>

        <section>
            <x-ui.table>
                <thead class="admin-table-head">
                    <tr>
                        <th class="admin-table-cell">Mã đối soát</th>
                        <th class="admin-table-cell">Đơn hàng</th>
                        <th class="admin-table-cell">Hãng VC</th>
                        <th class="admin-table-cell">COD kỳ vọng</th>
                        <th class="admin-table-cell">COD thực nhận</th>
                        <th class="admin-table-cell">Chênh lệch</th>
                        <th class="admin-table-cell">Trạng thái</th>
                        <th class="admin-table-cell text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse ($items as $item)
                        <tr>
                            <td class="admin-table-cell">{{ $item->ma_doi_soat }}</td>
                            <td class="admin-table-cell">{{ $item->order?->ma_tracking ?? $item->ma_don_hang }}</td>
                            <td class="admin-table-cell">{{ $item->hangVanChuyen?->ten_hang ?? $item->ma_hang_van_chuyen }}</td>
                            <td class="admin-table-cell">{{ number_format((float) $item->cod_ky_vong, 0, ',', '.') }}đ</td>
                            <td class="admin-table-cell">{{ number_format((float) $item->cod_thuc_nhan, 0, ',', '.') }}đ</td>
                            <td class="admin-table-cell">{{ number_format((float) $item->chenhlech, 0, ',', '.') }}đ</td>
                            <td class="admin-table-cell">{{ $item->trang_thai }}</td>
                            <td class="admin-table-cell">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('cod.show', $item) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Chi tiết</a>
                                    <a href="{{ route('cod.edit', $item) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Sửa</a>
                                    <form method="POST" action="{{ route('cod.destroy', $item) }}" onsubmit="return confirm('Xóa bản ghi này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-rose-700">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="admin-table-cell text-center text-sm text-gray-500">Chưa có dữ liệu đối soát COD.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </section>
    </div>
@endsection