@extends('layouts.admin')

@section('title', 'Chi tiết cấu hình API')
@section('page-title', 'Chi tiết cấu hình API')

@section('content')
    <section class="admin-panel p-6">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <h2 class="text-lg font-semibold text-gray-900">{{ $carrier->ten_hang }}</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <p><span class="font-semibold text-gray-700">Mã:</span> {{ $carrier->ma_hang_van_chuyen }}</p>
            <p><span class="font-semibold text-gray-700">Shop ID:</span> {{ $carrier->shop_id ?: '-' }}</p>
            <p><span class="font-semibold text-gray-700">Môi trường:</span> {{ (int) $carrier->moi_truong === 0 ? 'dev' : 'prod' }}</p>
            <p><span class="font-semibold text-gray-700">Token:</span> {{ $carrier->api_token }}</p>
            <p><span class="font-semibold text-gray-700">Đơn đang tham chiếu:</span> {{ (int) $carrier->orders_count }}</p>
            <p><span class="font-semibold text-gray-700">Đối soát COD tham chiếu:</span> {{ (int) $carrier->cod_reconciliations_count }}</p>
        </div>

        @if ((int) $carrier->orders_count > 0 || (int) $carrier->cod_reconciliations_count > 0)
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Cấu hình này đang được tham chiếu. Khi xóa, hệ thống sẽ cố gắng chuyển liên kết sang cấu hình cùng hãng phù hợp trước khi xóa.
            </div>
        @endif

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('api-config.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Danh sách</a>
            <a href="{{ route('api-config.edit', $carrier) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Sửa</a>
            <form method="POST" action="{{ route('api-config.destroy', $carrier) }}" onsubmit="return confirm('Xóa cấu hình {{ $carrier->ten_hang }}? Hiện đang tham chiếu: Đơn={{ (int) $carrier->orders_count }}, COD={{ (int) $carrier->cod_reconciliations_count }}.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Xóa</button>
            </form>
        </div>
    </section>
@endsection
