<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/Doi_Soat_CodController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER DOI SOAT COD
|--------------------------------------------------------------------------
| Xu ly nghiep vu doi soat COD: tao, sua, xoa, xem danh sach.
| Du lieu doi soat co lien ket den don hang va hang van chuyen.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cod_Reconciliation;
use App\Models\External_Route_Bill;
use App\Models\Hang_Van_Chuyen;
use App\Models\Order;
use App\Services\Cod_AutoReconciliationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class Doi_Soat_CodController extends Controller
{
    /**
     * Danh sach ban ghi doi soat COD.
     */
    public function index(Request $request): View
    {
        // Nap kem thong tin don hang + hang van chuyen de hien thi tong hop.
        return view('admin.cod.index', [
            'items' => $this->scopeCodRecords($request)
                ->with(['order', 'hangVanChuyen'])
                ->orderByDesc('ma_doi_soat')
                ->get(),
        ]);
    }

    /**
     * Hien thi form tao ban ghi doi soat COD.
     */
    public function create(): RedirectResponse
    {
        return redirect()
            ->route('cod.index')
            ->with('success', 'Module COD da chuyen sang che do tu dong. Vui long dung nut Chay doi soat tu dong.');
    }

    /**
     * Chay doi soat COD tu dong ngay tren UI.
     */
    public function autoReconcile(Request $request, Cod_AutoReconciliationService $service): RedirectResponse
    {
        $limit = max(1, min(1000, (int) $request->input('limit', 300)));
        $onlyMissing = $request->boolean('only_missing', false);

        $ownerUserId = $this->isShopRole($request)
            ? (int) $request->user()->getAuthIdentifier()
            : null;

        $managedCarrierIds = $this->isTransportManagerRole($request)
            ? $this->resolveManagedCarrierIds($request)
            : null;

        $summary = $service->reconcileDeliveredOrders($limit, $onlyMissing, $ownerUserId, $managedCarrierIds);

        $message = sprintf(
            'Da chay doi soat tu dong: xu ly %d don, tao moi %d, cap nhat %d, cho xac nhan %d, bo qua %d, loi %d.',
            $summary['processed'],
            $summary['created'],
            $summary['updated'],
            $summary['pending'],
            $summary['skipped'],
            $summary['failed']
        );

        if ((int) $summary['processed'] === 0) {
            $message .= ' Khong co don nao du dieu kien trong pham vi tai khoan hien tai (yeu cau: co ma tracking va trang thai da_giao hoac dang_van_chuyen).';
        }

        return redirect()->route('cod.index')->with('success', $message);
    }

    /**
     * Luu ban ghi doi soat COD moi.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate va tinh chenh lech COD.
        $validated = $this->validatedPayload($request);

        Cod_Reconciliation::query()->create($validated);

        return redirect()->route('cod.index')->with('success', 'Đã thêm bản ghi đối soát COD.');
    }

    /**
     * Xem chi tiet 1 ban ghi doi soat.
     */
    public function show(Cod_Reconciliation $cod): View
    {
        // Nap quan he de view khong phai query them.
        $this->authorizeCodAccess(request(), $cod);
        $cod->load(['order', 'hangVanChuyen']);

        return view('admin.cod.show', ['item' => $cod]);
    }

    /**
     * Hien thi form sua ban ghi doi soat.
     */
    public function edit(Cod_Reconciliation $cod): View
    {
        $this->authorizeCodAccess(request(), $cod);

        $ordersQuery = $this->scopeOrdersForCod(request())
            ->orderByDesc('ma_don_hang')
            ->limit(100);

        // Muc tieu: Nap du lieu hien tai de chinh sua theo mang doi soat COD.
        return view('admin.cod.edit', [
            'item' => $cod,
            'orders' => $ordersQuery->get(['ma_don_hang', 'ma_tracking']),
            'carriers' => Hang_Van_Chuyen::query()->orderBy('ten_hang')->get(['ma_hang_van_chuyen', 'ten_hang']),
        ]);
    }

    /**
     * Cap nhat ban ghi doi soat COD.
     */
    public function update(Request $request, Cod_Reconciliation $cod): RedirectResponse
    {
        $this->authorizeCodAccess($request, $cod);

        // Validate lai payload truoc khi cap nhat.
        $validated = $this->validatedPayload($request);

        $cod->update($validated);

        return redirect()->route('cod.show', $cod)->with('success', 'Đã cập nhật bản ghi đối soát COD.');
    }

    /**
     * Xoa ban ghi doi soat COD.
     */
    public function destroy(Cod_Reconciliation $cod): RedirectResponse
    {
        $this->authorizeCodAccess(request(), $cod);

        // Muc tieu: Xoa ban ghi sau khi kiem tra rang buoc cua mang doi soat COD.
        $cod->delete();

        return redirect()->route('cod.index')->with('success', 'Đã xóa bản ghi đối soát COD.');
    }

    /**
     * Validate payload doi soat va tinh truong chenhlech.
     */
    private function validatedPayload(Request $request): array
    {
        // Muc tieu: Xu ly nghiep vu ham validatedPayload trong mang doi soat COD.
        $validated = $request->validate([
            'ma_don_hang' => ['required', 'integer', 'exists:don_hang,ma_don_hang'],
            'ma_hang_van_chuyen' => ['required', 'integer', 'exists:hang_van_chuyen,ma_hang_van_chuyen'],
            'cod_ky_vong' => ['required', 'numeric', 'min:0'],
            'cod_thuc_nhan' => ['required', 'numeric', 'min:0'],
            'ngay_doi_soat' => ['nullable', 'date'],
            'trang_thai' => ['required', 'string', 'max:50'],
        ]);

        // Chenh lech = cod thuc nhan - cod ky vong.
        $validated['chenhlech'] = (float) $validated['cod_thuc_nhan'] - (float) $validated['cod_ky_vong'];

        return $validated;
    }

    /**
     * Scope danh sach doi soat COD theo quyen user hien tai.
     */
    private function scopeCodRecords(Request $request): Builder
    {
        $query = Cod_Reconciliation::query();

        if ($this->isTransportManagerRole($request)) {
            $carrierIds = $this->resolveManagedCarrierIds($request);

            if ($carrierIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('order.externalRouteBills', function (Builder $billQuery) use ($carrierIds): void {
                $billQuery->whereIn('ma_nha_xe', $carrierIds);
            });
        }

        if ($this->isShopRole($request)) {
            $ownerId = (int) $request->user()->getAuthIdentifier();

            return $query->whereHas('order', function (Builder $orderQuery) use ($ownerId): void {
                $orderQuery->where('ma_nguoi_dung', $ownerId);
            });
        }

        return $query;
    }

    /**
     * Kiem tra quyen truy cap chi tiet doi soat.
     */
    private function authorizeCodAccess(Request $request, Cod_Reconciliation $cod): void
    {
        if (! $this->isShopRole($request) && ! $this->isTransportManagerRole($request)) {
            return;
        }

        $codOrder = $cod->order;
        if (! $codOrder) {
            abort(404, 'Không tìm thấy đơn hàng liên kết với bản ghi đối soát.');
        }

        if ($this->isShopRole($request)) {
            if ((int) $codOrder->ma_nguoi_dung !== (int) $request->user()->getAuthIdentifier()) {
                abort(403, 'Bạn không có quyền thao tác bản ghi đối soát của shop khác.');
            }

            return;
        }

        $carrierIds = $this->resolveManagedCarrierIds($request);
        if ($carrierIds === []) {
            abort(403, 'Bạn chưa được liên kết chành xe để thao tác đối soát COD.');
        }

        $isAssigned = External_Route_Bill::query()
            ->where('ma_don_hang', (int) $codOrder->ma_don_hang)
            ->whereIn('ma_nha_xe', $carrierIds)
            ->exists();

        if (! $isAssigned) {
            abort(403, 'Bạn chỉ được thao tác bản ghi đối soát của chành xe mình quản lý.');
        }
    }

    /**
     * Scope danh sach don phuc vu form doi soat COD.
     */
    private function scopeOrdersForCod(Request $request): Builder
    {
        $query = Order::query();

        if ($this->isTransportManagerRole($request)) {
            $carrierIds = $this->resolveManagedCarrierIds($request);

            if ($carrierIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('externalRouteBills', function (Builder $billQuery) use ($carrierIds): void {
                $billQuery->whereIn('ma_nha_xe', $carrierIds);
            });
        }

        if ($this->isShopRole($request)) {
            $query->where('ma_nguoi_dung', (int) $request->user()->getAuthIdentifier());
        }

        return $query;
    }

    /**
     * Kiem tra role chu shop.
     */
    private function isShopRole(Request $request): bool
    {
        $role = Str::of((string) ($request->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        return $role === 'chushop';
    }

    /**
     * Kiem tra role quan ly chanh xe.
     */
    private function isTransportManagerRole(Request $request): bool
    {
        $role = Str::of((string) ($request->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        return $role === 'quanlychanhxe';
    }

    /**
     * Lay danh sach ma nha xe ma manager duoc phep quan ly.
     *
     * @return array<int>
     */
    private function resolveManagedCarrierIds(Request $request): array
    {
        $user = $request->user();
        $unitNames = array_filter([
            trim((string) ($user?->ten_don_vi ?? '')),
            trim((string) ($user?->ho_ten ?? '')),
        ]);

        $normalizedUnitNames = array_values(array_unique(array_map(function (string $value): string {
            return Str::of($value)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]/', '')
                ->toString();
        }, $unitNames)));

        $normalizedPhone = preg_replace('/\D+/', '', (string) ($user?->so_dien_thoai ?? '')) ?: '';

        return \App\Models\Nha_Xe::query()
            ->get(['ma_nha_xe', 'ten_nha_xe', 'so_dien_thoai'])
            ->filter(function (\App\Models\Nha_Xe $carrier) use ($normalizedUnitNames, $normalizedPhone): bool {
                $carrierName = Str::of((string) $carrier->ten_nha_xe)
                    ->ascii()
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]/', '')
                    ->toString();

                $carrierPhone = preg_replace('/\D+/', '', (string) ($carrier->so_dien_thoai ?? '')) ?: '';

                if ($carrierName !== '' && in_array($carrierName, $normalizedUnitNames, true)) {
                    return true;
                }

                return $normalizedPhone !== '' && $carrierPhone !== '' && $normalizedPhone === $carrierPhone;
            })
            ->pluck('ma_nha_xe')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}




