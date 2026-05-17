<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/Api_ConfigController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER CAU HINH API HANG VAN CHUYEN
|--------------------------------------------------------------------------
| Quan ly CRUD cau hinh token/shop_id theo tung hang van chuyen.
| Co cac ham luu cau hinh nhanh va test ket noi API (hien uu tien GHN).
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kiem_Tra_Ket_Noi_Hang_Van_ChuyenRequest;
use App\Http\Requests\Admin\Luu_Cau_Hinh_Hang_Van_ChuyenRequest;
use App\Models\Hang_Van_Chuyen;
use App\Services\Carrier_ServiceManager;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Api_ConfigController extends Controller
{
    /**
     * Trang danh sach cau hinh API theo tung hang van chuyen.
     */
    public function index(Request $request, Carrier_ServiceManager $carrierManager)
    {
        $carrierFilter = trim((string) $request->query('carrier'));
        $usageFilter = trim((string) $request->query('usage', 'all'));
        $sortFilter = trim((string) $request->query('sort', 'newest'));

        // Buoc 1: Lay danh sach cau hinh theo scope quyen cua user hien tai + bo loc giao dien.
        $query = $this->scopedCarriers($request);

        if ($carrierFilter !== '') {
            $normalizedCarrier = $this->normalizeCarrierNameForStorage($carrierFilter);

            if ($normalizedCarrier === 'VIETTEL_POST') {
                $query->whereRaw('LOWER(ten_hang) like ?', ['%viettel%']);
            } else {
                $query->where('ten_hang', $normalizedCarrier);
            }
        }

        if ($usageFilter === 'used') {
            $query->where(function (Builder $builder): void {
                $builder
                    ->whereHas('orders')
                    ->orWhereHas('codReconciliations');
            });
        } elseif ($usageFilter === 'unused') {
            $query
                ->whereDoesntHave('orders')
                ->whereDoesntHave('codReconciliations');
        }

        $query->withCount(['orders', 'codReconciliations']);

        if ($sortFilter === 'oldest') {
            $query->orderBy('ma_hang_van_chuyen');
        } elseif ($sortFilter === 'impact') {
            $query
                ->orderByRaw('(orders_count + cod_reconciliations_count) DESC')
                ->orderByDesc('ma_hang_van_chuyen');
        } else {
            $sortFilter = 'newest';
            $query->orderByDesc('ma_hang_van_chuyen');
        }

        $carriers = $query->get();
        // Buoc 2: Chon cau hinh GHN/Viettel de prefill nhanh cho form.
        $shopGhnConfig = $carriers->first(fn (Hang_Van_Chuyen $carrier) => Str::lower((string) $carrier->ten_hang) === 'ghn');
        $shopViettelConfig = $carriers->first(fn (Hang_Van_Chuyen $carrier) => str_contains(Str::lower((string) $carrier->ten_hang), 'viettel'));
        $ghnDefaults = $carrierManager->getDefaultCredentials('GHN');
        $viettelDefaults = $carrierManager->getDefaultCredentials('VIETTELPOST');

        return view('admin.api-config.index', [
            'ghnDefaults' => [
                'token' => $shopGhnConfig?->api_token ?: data_get($ghnDefaults, 'token'),
                'shop_id' => $shopGhnConfig?->shop_id ?: data_get($ghnDefaults, 'shop_id'),
            ],
            'carrierDefaults' => [
                'GHN' => [
                    'token' => $shopGhnConfig?->api_token ?: data_get($ghnDefaults, 'token'),
                    'shop_id' => $shopGhnConfig?->shop_id ?: data_get($ghnDefaults, 'shop_id'),
                ],
                'VIETTELPOST' => [
                    'token' => $shopViettelConfig?->api_token ?: data_get($viettelDefaults, 'token'),
                    'shop_id' => $shopViettelConfig?->shop_id ?: data_get($viettelDefaults, 'shop_id'),
                ],
            ],
            'carriers' => $carriers,
            'filters' => [
                'carrier' => $carrierFilter,
                'usage' => in_array($usageFilter, ['all', 'used', 'unused'], true) ? $usageFilter : 'all',
                'sort' => $sortFilter,
            ],
        ]);
    }

    /**
     * Hien thi form tao cau hinh API moi.
     */
    public function create(Request $request)
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang cau hinh API hang van chuyen.
        return view('admin.api-config.create');
    }

    /**
     * Luu cau hinh API moi.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate du lieu form.
        $validated = $request->validate([
            'ten_hang' => ['required', 'string', 'max:150'],
            'api_token' => ['required', 'string'],
            'shop_id' => ['nullable', 'string', 'max:50'],
            'moi_truong' => ['nullable', 'integer', 'in:0,1'],
        ]);

        // Chuan hoa ten hang de tranh tao ban ghi trung do khac format viet/hoa/thuong.
        $validated['ten_hang'] = $this->normalizeCarrierNameForStorage((string) $validated['ten_hang']);

        $ownerId = $this->isAdmin($request)
            ? null
            : (int) $request->user()->getAuthIdentifier();

        $carrier = Hang_Van_Chuyen::query()->updateOrCreate(
            [
                'ten_hang' => $validated['ten_hang'],
                'ma_nguoi_dung' => $ownerId,
            ],
            [
                'api_token' => $validated['api_token'],
                'shop_id' => $validated['shop_id'] ?? null,
                'moi_truong' => $validated['moi_truong'] ?? 0,
            ]
        );

        return redirect()->route('api-config.index')->with('success',
            $carrier->wasRecentlyCreated
                ? 'Đã thêm cấu hình API mới.'
                : 'Đã cập nhật cấu hình API hiện có, không tạo bản ghi trùng.'
        );
    }

    /**
     * Xem chi tiet 1 cau hinh API.
     */
    public function show(Request $request, Hang_Van_Chuyen $carrier)
    {
        // Kiem tra quyen truy cap cau hinh.
        $this->authorizeCarrierAccess($request, $carrier);
        $carrier->loadCount(['orders', 'codReconciliations']);

        return view('admin.api-config.show', ['carrier' => $carrier]);
    }

    /**
     * Hien thi form sua cau hinh API.
     */
    public function edit(Request $request, Hang_Van_Chuyen $carrier)
    {
        // Muc tieu: Nap du lieu hien tai de chinh sua theo mang cau hinh API hang van chuyen.
        $this->authorizeCarrierAccess($request, $carrier);

        return view('admin.api-config.edit', ['carrier' => $carrier]);
    }

    /**
     * Cap nhat cau hinh API.
     */
    public function update(Request $request, Hang_Van_Chuyen $carrier): RedirectResponse
    {
        // Muc tieu: Cap nhat du lieu da validate theo quy tac cua mang cau hinh API hang van chuyen.
        $this->authorizeCarrierAccess($request, $carrier);

        $validated = $request->validate([
            'ten_hang' => ['required', 'string', 'max:150'],
            'api_token' => ['required', 'string'],
            'shop_id' => ['nullable', 'string', 'max:50'],
            'moi_truong' => ['nullable', 'integer', 'in:0,1'],
        ]);

        // Chuan hoa ten hang de giu du lieu nhat quan trong DB.
        $validated['ten_hang'] = $this->normalizeCarrierNameForStorage((string) $validated['ten_hang']);

        $ownerId = $carrier->ma_nguoi_dung;
        $conflict = Hang_Van_Chuyen::query()
            ->where('ma_hang_van_chuyen', '!=', $carrier->ma_hang_van_chuyen)
            ->where('ten_hang', $validated['ten_hang'])
            ->where(function (Builder $query) use ($ownerId): void {
                if ($ownerId === null) {
                    $query->whereNull('ma_nguoi_dung');

                    return;
                }

                $query->where('ma_nguoi_dung', $ownerId);
            })
            ->first();

        if ($conflict) {
            return back()
                ->withErrors(['ten_hang' => 'Đã tồn tại cấu hình cùng hãng cho cùng chủ sở hữu. Vui lòng chỉnh sửa bản ghi hiện có để tránh trùng lặp.'])
                ->withInput();
        }

        // Luu thay doi cau hinh.
        $carrier->update($validated);

        return redirect()->route('api-config.show', $carrier)->with('success', 'Đã cập nhật cấu hình API.');
    }

    /**
     * Xoa cau hinh API.
     */
    public function destroy(Request $request, Hang_Van_Chuyen $carrier): RedirectResponse
    {
        // Muc tieu: Xoa ban ghi sau khi kiem tra rang buoc cua mang cau hinh API hang van chuyen.
        $this->authorizeCarrierAccess($request, $carrier);

        $replacement = $this->findReplacementCarrier($request, $carrier);

        if (! $replacement) {
            $orderCount = (int) DB::table('don_hang')->where('ma_hang_van_chuyen', $carrier->ma_hang_van_chuyen)->count();
            $codCount = (int) DB::table('doi_soat_cod')->where('ma_hang_van_chuyen', $carrier->ma_hang_van_chuyen)->count();

            if ($orderCount > 0 || $codCount > 0) {
                return redirect()->route('api-config.index')->with('error',
                    'Không thể xóa cấu hình API vì đang được tham chiếu (đơn hàng: '.$orderCount.', đối soát COD: '.$codCount.'). '
                    .'Hãy tạo/cập nhật một cấu hình cùng hãng rồi thử lại.'
                );
            }
        }

        try {
            DB::transaction(function () use ($carrier, $replacement): void {
                if ($replacement) {
                    DB::table('don_hang')
                        ->where('ma_hang_van_chuyen', $carrier->ma_hang_van_chuyen)
                        ->update(['ma_hang_van_chuyen' => $replacement->ma_hang_van_chuyen]);

                    DB::table('doi_soat_cod')
                        ->where('ma_hang_van_chuyen', $carrier->ma_hang_van_chuyen)
                        ->update(['ma_hang_van_chuyen' => $replacement->ma_hang_van_chuyen]);
                }

                $carrier->delete();
            });
        } catch (QueryException) {
            return redirect()->route('api-config.index')->with('error',
                'Không thể xóa cấu hình API do còn dữ liệu ràng buộc. Vui lòng thử xóa lại sau khi chuyển toàn bộ đơn liên quan sang cấu hình khác.'
            );
        }

        return redirect()->route('api-config.index')->with('success', 'Đã xóa cấu hình API.');
    }

    /**
     * Tim cau hinh thay the cung hang de chuyen lien ket truoc khi xoa.
     */
    private function findReplacementCarrier(Request $request, Hang_Van_Chuyen $carrier): ?Hang_Van_Chuyen
    {
        $normalizedName = $this->normalizeCarrierNameForStorage((string) $carrier->ten_hang);

        $query = $this->scopedCarriers($request)
            ->where('ma_hang_van_chuyen', '!=', $carrier->ma_hang_van_chuyen)
            ->whereRaw('UPPER(REPLACE(ten_hang, " ", "")) LIKE ?', ['%'.str_replace('_', '', $normalizedName).'%']);

        return $query
            ->orderByRaw('CASE WHEN shop_id IS NULL OR shop_id = "" THEN 1 ELSE 0 END')
            ->orderByDesc('ma_hang_van_chuyen')
            ->first();
    }

    /**
     * Luu nhanh cau hinh API theo ten hang (mac dinh GHN).
     */
    public function luuCauHinhHangVanChuyen(Luu_Cau_Hinh_Hang_Van_ChuyenRequest $request)
    {
        // Buoc 1: Lay input da validate va chuan hoa ten hang.
        $validated = $request->validated();
        $tenHang = $this->normalizeCarrierNameForStorage((string) ($validated['ten_hang'] ?? 'GHN'));

        // Buoc 2: Xac dinh owner record theo role (admin dung chung, shop dung rieng).
        $ownerId = $this->isAdmin($request)
            ? null
            : (int) $request->user()->getAuthIdentifier();

        // Buoc 3: Tim/tao ban ghi cau hinh theo cap ten_hang + owner.
        $carrier = Hang_Van_Chuyen::query()->firstOrNew([
            'ten_hang' => $tenHang,
            'ma_nguoi_dung' => $ownerId,
        ]);

        // Buoc 4: Cap nhat credential co ban.
        $carrier->api_token = $validated['token'];
        $carrier->shop_id = isset($validated['shop_id']) ? (string) $validated['shop_id'] : null;

        // Buoc 5: Luu metadata phu tro rieng cho GHN, hang khac luu moc saved_at.
        if ($tenHang === 'GHN') {
            $carrier->moi_truong = $carrier->moi_truong ?? (str_contains((string) config('services.ghn.base_url'), 'dev') ? 0 : 1);
            $carrier->config_json = array_merge((array) $carrier->config_json, [
                'base_url' => config('services.ghn.base_url'),
                'saved_at' => now()->toDateTimeString(),
            ]);
        } else {
            $carrier->config_json = array_merge((array) $carrier->config_json, [
                'saved_at' => now()->toDateTimeString(),
            ]);
        }

        $carrier->save();

        return back()->with('success', 'Đã lưu cấu hình API '.$tenHang.' vào cơ sở dữ liệu thành công.');
    }

    /**
        * Test ket noi API hang van chuyen theo adapter da dang ky.
     */
    public function kiemTraKetNoiHangVanChuyen(Kiem_Tra_Ket_Noi_Hang_Van_ChuyenRequest $request, Carrier_ServiceManager $carrierManager)
    {
        // Buoc 1: Chuan hoa ten hang va resolve credential theo uu tien form -> defaults.
        $validated = $request->validated();
        $tenHang = $carrierManager->normalizeCarrierName((string) ($validated['ten_hang'] ?? 'GHN'));

        if ($tenHang === '') {
            $tenHang = 'GHN';
        }

        $defaults = $carrierManager->getDefaultCredentials($tenHang);

        $token = $this->resolveToken($validated, $defaults);
        $shopId = $this->resolveShopId($validated, $defaults);

        // Buoc 2: Chan som neu token trong de tranh goi API vo nghia.
        if (! $token) {
            return back()
                ->withErrors(['token' => 'Vui lòng nhập token hoặc cấu hình token trong mục Cấu hình API/.env'])
                ->withInput();
        }

        // Buoc 3: Goi adapter test ket noi cua hang duoc chon.
        $result = $carrierManager->testConnection($tenHang, [
            'token' => $token,
            'shop_id' => $shopId > 0 ? $shopId : null,
            'base_url' => data_get($defaults, 'base_url'),
        ]);

        $shops = data_get($result, 'data.data.shops', []);
        // Buoc 4: Neu la GHN, co gang tim shop khop de doi chieu nhanh tren UI.
        $matchedShop = $tenHang === 'GHN' ? $this->findMatchedShop($shops, $shopId) : null;

        $flashData = [
            'ten_hang_kiem_tra' => $tenHang,
            'ket_qua_kiem_tra_ket_noi' => $result,
            'cua_hang_phu_hop' => $matchedShop,
        ];

        // Giu key cu cho man hinh hien tai dang dung GHN.
        if ($tenHang === 'GHN') {
            $flashData['ghn_connection_result'] = $result;
            $flashData['ghn_matched_shop'] = $matchedShop;
        }

        return back()->with($flashData)->withInput();
    }

    /**
     * Ham wrapper de giu tuong thich route/ten ham cu (chi cho GHN).
     */
    public function saveGhnConfig(Luu_Cau_Hinh_Hang_Van_ChuyenRequest $request)
    {
        // Muc tieu: Luu du lieu nghiep vu theo quy tac cua mang cau hinh API hang van chuyen.
        $request->merge(['ten_hang' => 'GHN']);

        return $this->luuCauHinhHangVanChuyen($request);
    }

    /**
     * Ham wrapper test ket noi GHN (giu tuong thich route cu).
     */
    public function testGhnConnection(Kiem_Tra_Ket_Noi_Hang_Van_ChuyenRequest $request, Carrier_ServiceManager $carrierManager)
    {
        // Muc tieu: Xu ly nghiep vu ham testGhnConnection trong mang cau hinh API hang van chuyen.
        $request->merge(['ten_hang' => 'GHN']);

        return $this->kiemTraKetNoiHangVanChuyen($request, $carrierManager);
    }

    /**
     * Uu tien token tu form, neu rong thi lay token mac dinh.
     */
    private function resolveToken(array $validated, array $defaults): ?string
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang cau hinh API hang van chuyen.
        return $validated['token'] ?: ($defaults['token'] ?? null);
    }

    /**
     * Uu tien shop_id tu form, neu rong thi lay shop_id mac dinh.
     */
    private function resolveShopId(array $validated, array $defaults): int
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang cau hinh API hang van chuyen.
        return (int) ($validated['shop_id'] ?: ($defaults['shop_id'] ?? 0));
    }

    /**
     * Tim shop trong danh sach GHN theo shop_id.
     */
    private function findMatchedShop(array $shops, int $shopId): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang cau hinh API hang van chuyen.
        if ($shopId <= 0) {
            return null;
        }

        foreach ($shops as $shop) {
            if ((int) data_get($shop, '_id') === $shopId) {
                return $shop;
            }
        }

        return null;
    }

    /**
     * Scope query cau hinh theo quyen hien tai.
     */
    private function scopedCarriers(Request $request): Builder
    {
        // Muc tieu: Xu ly nghiep vu ham scopedCarriers trong mang cau hinh API hang van chuyen.
        $query = Hang_Van_Chuyen::query();

        if (! $this->isAdmin($request)) {
            $query->where('ma_nguoi_dung', (int) $request->user()->getAuthIdentifier());
        }

        return $query;
    }

    /**
     * Kiem tra user hien tai co quyen truy cap cau hinh nay khong.
     */
    private function authorizeCarrierAccess(Request $request, Hang_Van_Chuyen $carrier): void
    {
        // Muc tieu: Xu ly nghiep vu ham authorizeCarrierAccess trong mang cau hinh API hang van chuyen.
        if ($this->isAdmin($request)) {
            return;
        }

        if ((int) $carrier->ma_nguoi_dung !== (int) $request->user()->getAuthIdentifier()) {
            abort(403, 'Bạn không có quyền truy cập cấu hình API của shop khác.');
        }
    }

    /**
     * Xac dinh role admin.
     */
    private function isAdmin(Request $request): bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang cau hinh API hang van chuyen.
        $role = Str::of((string) ($request->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        return $role === 'admin';
    }

    /**
     * Chuan hoa ten hang ve key luu tru duy nhat trong CSDL.
     */
    private function normalizeCarrierNameForStorage(string $carrierName): string
    {
        $normalized = Str::of($carrierName)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->toString();

        if ($normalized === '' || $normalized === 'GHN') {
            return 'GHN';
        }

        if (str_contains($normalized, 'VIETTEL')) {
            return 'VIETTEL_POST';
        }

        return Str::of($carrierName)->trim()->upper()->toString();
    }
}




