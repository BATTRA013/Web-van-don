@extends('layouts.admin')

@section('title', 'Chi tiết đối soát COD')
@section('page-title', 'Chi tiết đối soát COD')

@section('content')
    <section class="admin-panel">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <h2 class="admin-panel-title">Bản ghi #{{ $item->ma_doi_soat }}</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <p><span class="font-semibold text-gray-700">Đơn hàng:</span> {{ $item->order?->ma_tracking ?? $item->ma_don_hang }}</p>
            <p><span class="font-semibold text-gray-700">Hãng VC:</span> {{ $item->hangVanChuyen?->ten_hang ?? $item->ma_hang_van_chuyen }}</p>
            <p><span class="font-semibold text-gray-700">COD kỳ vọng:</span> {{ number_format((float) $item->cod_ky_vong, 0, ',', '.') }}đ</p>
            <p><span class="font-semibold text-gray-700">COD thực nhận:</span> {{ number_format((float) $item->cod_thuc_nhan, 0, ',', '.') }}đ</p>
            <p><span class="font-semibold text-gray-700">Chênh lệch:</span> {{ number_format((float) $item->chenhlech, 0, ',', '.') }}đ</p>
            <p><span class="font-semibold text-gray-700">Trạng thái:</span> {{ $item->trang_thai }}</p>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('cod.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Danh sách</a>
            <a href="{{ route('cod.edit', $item) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Sửa</a>
            <form method="POST" action="{{ route('cod.destroy', $item) }}" onsubmit="return confirm('Xóa bản ghi này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Xóa</button>
            </form>
        </div>
    </section>
@endsection
