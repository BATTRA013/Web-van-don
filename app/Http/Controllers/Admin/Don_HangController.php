<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/Don_HangController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER DON HANG
|--------------------------------------------------------------------------
| Quan ly don hang noi bo va luong tao/dong bo don GHN.
| Day la file nghiep vu trung tam cho module orders.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Store_OrderRequest;
use App\Http\Requests\Admin\Tao_Don_Van_ChuyenRequest;
use App\Models\External_Route_Bill;
use App\Models\Hang_Van_Chuyen;
use App\Models\Nha_Xe;
use App\Models\Order;
use App\Models\Order_Detail;
use App\Services\Carrier_ServiceManager;
use App\Services\Ghn_ShippingService;
use App\Services\ViettelPost_ShippingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class Don_HangController extends Controller
{
    /**
     * Danh sach don hang theo pham vi quyen.
     */
    public function index(Request $request)
    {
        // Buoc 1: Doc bo loc tu query string va chuan hoa ve dang an toan de query.
        $filters = [
            'status' => $this->normalizeOrderStatusFilter((string) $request->input('status', '')),
            'order_code' => trim((string) $request->input('order_code', '')),
            'receiver_phone' => preg_replace('/\D+/', '', (string) $request->input('receiver_phone', '')),
        ];

        $isTransportManager = $this->isTransportManagerRole($request);
        $managedCarrierIds = $isTransportManager ? $this->resolveManagedCarrierIds($request) : [];

        $ordersQuery = $this->scopeOrders($request)
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('trang_thai', $filters['status']);
            })
            ->when($filters['order_code'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('ma_tracking', 'like', '%'.$filters['order_code'].'%')
                        ->orWhere('ma_don_hang', 'like', '%'.$filters['order_code'].'%');
                });
            })
            ->when($filters['receiver_phone'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('sdt_nguoi_nhan', 'like', '%'.$filters['receiver_phone'].'%')
                        ->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sdt_nguoi_nhan, ' ', ''), '.', ''), '-', ''), '(', ''), ')', '') like ?",
                            ['%'.$filters['receiver_phone'].'%']
                        );
                });
            })
            ->orderByDesc('ma_don_hang')
            ->limit(50);

        if ($isTransportManager) {
            $ordersQuery->with([
                'externalRouteBills' => function ($billQuery) use ($managedCarrierIds): void {
                    $billQuery
                        ->whereIn('ma_nha_xe', $managedCarrierIds)
                        ->with('nhaXe')
                        ->orderByDesc('ma_van_don_ngoai_tuyen');
                },
            ]);
        } else {
            $ordersQuery->with('hangVanChuyen');
        }

        // Buoc 2: Lap query theo scope quyen + bo loc nguoi dung, sau do tra ve 50 don moi nhat.
        return view('admin.orders.index', [
            'orders' => $ordersQuery->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Hien thi form tao don noi bo.
     */
    public function create()
    {
        // View chi chua form nhap don noi bo, chua tao du lieu.
        return view('admin.orders.create');
    }

    /**
     * Xem chi tiet don hang.
     */
    public function show(Request $request, Order $order)
    {
        // Kiem tra quyen truy cap don cua shop khac.
        $this->authorizeOrderAccess($request, $order);

        // Nap cac quan he can hien thi chi tiet.
        $order->load([
            'hangVanChuyen',
            'orderDetails',
            'nguoiDung',
            'externalRouteBills.nhaXe',
        ]);

        if ($this->isTransportManagerRole($request)) {
            $carrierIds = $this->resolveManagedCarrierIds($request);

            $order->setRelation(
                'externalRouteBills',
                $order->externalRouteBills
                    ->filter(fn (External_Route_Bill $bill): bool => in_array((int) $bill->ma_nha_xe, $carrierIds, true))
                    ->values()
            );
        }

        return view('admin.orders.show', [
            'order' => $order,
            'carriers' => Nha_Xe::query()->orderBy('ten_nha_xe')->get(['ma_nha_xe', 'ten_nha_xe']),
        ]);
    }

    /**
     * Gui don qua chanh xe: luu bien lai van don ngoai tuyen vao DB.
     */
    public function storeExternalRouteBill(Request $request, Order $order)
    {
        // Dam bao shop chi duoc thao tac tren don cua minh.
        $this->authorizeOrderAccess($request, $order);

        $validated = $request->validate([
            'ma_nha_xe' => ['required', 'integer', 'exists:nha_xe,ma_nha_xe'],
        ]);

        $existingOpenRequest = External_Route_Bill::query()
            ->where('ma_don_hang', (int) $order->ma_don_hang)
            ->where('ma_nha_xe', (int) $validated['ma_nha_xe'])
            ->whereIn('trang_thai', ['cho_nhan', 'da_nhan'])
            ->exists();

        if ($existingOpenRequest) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Đơn này đã có yêu cầu gửi chành xe đang chờ xử lý.');
        }

        External_Route_Bill::query()->create([
            'ma_don_hang' => (int) $order->ma_don_hang,
            'ma_nha_xe' => (int) $validated['ma_nha_xe'],
            'ma_bien_lai' => 'YC-'.$order->ma_don_hang.'-'.now()->format('YmdHis'),
            'anh_chup_bien_lai' => null,
            'trang_thai' => 'cho_nhan',
            'ly_do_tu_choi' => null,
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Đã gửi yêu cầu vận chuyển cho chành xe. Chờ chành xe xác nhận.');
    }

    /**
     * Chanh xe xac nhan nhan don van chuyen.
     */
    public function acceptExternalRouteBill(Request $request, Order $order, External_Route_Bill $bill)
    {
        $this->authorizeExternalRouteBillForManager($request, $order, $bill);

        $bill->update([
            'trang_thai' => 'da_nhan',
            'ly_do_tu_choi' => null,
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Đã xác nhận nhận đơn. Vui lòng cập nhật biên lai khi có.');
    }

    /**
     * Chanh xe tu choi nhan don.
     */
    public function rejectExternalRouteBill(Request $request, Order $order, External_Route_Bill $bill)
    {
        $this->authorizeExternalRouteBillForManager($request, $order, $bill);

        $validated = $request->validate([
            'ly_do_tu_choi' => ['required', 'string', 'max:500'],
        ]);

        $bill->update([
            'trang_thai' => 'tu_choi',
            'ly_do_tu_choi' => trim((string) $validated['ly_do_tu_choi']),
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Đã từ chối nhận đơn và gửi lý do cho chủ shop.');
    }

    /**
     * Chanh xe cap nhat bien lai thuc te sau khi da nhan don.
     */
    public function updateExternalRouteBillReceipt(Request $request, Order $order, External_Route_Bill $bill)
    {
        $this->authorizeExternalRouteBillForManager($request, $order, $bill);

        $validated = $request->validate([
            'ma_bien_lai' => [
                'required',
                'string',
                'max:100',
                Rule::unique('van_don_ngoai_tuyen', 'ma_bien_lai')->ignore($bill->ma_van_don_ngoai_tuyen, 'ma_van_don_ngoai_tuyen'),
            ],
            'anh_bien_lai_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $imagePath = $bill->anh_chup_bien_lai;

        if ($request->hasFile('anh_bien_lai_file')) {
            if ($bill->anh_chup_bien_lai && ! Str::startsWith((string) $bill->anh_chup_bien_lai, ['http://', 'https://'])) {
                Storage::disk('public')->delete((string) $bill->anh_chup_bien_lai);
            }

            $imagePath = (string) $request->file('anh_bien_lai_file')->store('external-route-bills', 'public');
        }

        $bill->update([
            'ma_bien_lai' => trim((string) $validated['ma_bien_lai']),
            'anh_chup_bien_lai' => $imagePath,
            'trang_thai' => 'da_gui_bien_lai',
            'ly_do_tu_choi' => null,
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Đã cập nhật biên lai từ chành xe.');
    }

    /**
     * Xoa bien lai van don ngoai tuyen da tao nham.
     */
    public function destroyExternalRouteBill(Request $request, Order $order, External_Route_Bill $bill)
    {
        $this->authorizeOrderAccess($request, $order);

        if ((int) $bill->ma_don_hang !== (int) $order->ma_don_hang) {
            abort(404);
        }

        if ($bill->anh_chup_bien_lai && ! Str::startsWith((string) $bill->anh_chup_bien_lai, ['http://', 'https://'])) {
            Storage::disk('public')->delete((string) $bill->anh_chup_bien_lai);
        }

        $bill->delete();

        return redirect()->route('orders.show', $order)->with('success', 'Đã xóa biên lai gửi chành xe.');
    }

    /**
     * Form sua don dung chung giao dien voi trang chi tiet.
     */
    public function edit(Request $request, Order $order)
    {
        // Tai duong dung chung view show de sua nhanh tren cung giao dien.
        return $this->show($request, $order);
    }

    /**
     * Tao don noi bo moi.
     */
    public function store(Store_OrderRequest $request)
    {
        // Du lieu da validate tu FormRequest.
        $validated = $request->validated();

        // Boi giao dich de tranh luu nua don khi phat sinh loi o bang chi tiet.
        $order = DB::transaction(function () use ($request, $validated): Order {
            $order = Order::query()->create([
                'ma_nguoi_dung' => (int) ($request->user()->getAuthIdentifier()),
                'ma_hang_van_chuyen' => $this->resolveDefaultCarrierId($request),
                'ten_nguoi_nhan' => $validated['receiver_name'],
                'sdt_nguoi_nhan' => $validated['receiver_phone'],
                'dia_chi_chi_tiet' => $validated['receiver_address'],
                'ma_tinh_thanh' => (int) $validated['to_province_id'],
                'ma_quan_huyen' => (int) $validated['to_district_id'],
                'ma_phuong_xa' => $validated['to_ward_code'],
                'trong_luong' => (int) $validated['item_weight'],
                'chieu_dai' => (int) ($validated['length'] ?? 0),
                'chieu_rong' => (int) ($validated['width'] ?? 0),
                'chieu_cao' => (int) ($validated['height'] ?? 0),
                'tien_cod' => (float) ($validated['cod_value'] ?? 0),
                'ma_tracking' => $this->generateTrackingCode(),
                'trang_thai' => 'moi',
            ]);

            Order_Detail::query()->create([
                'ma_don_hang' => $order->ma_don_hang,
                'ten_san_pham' => $validated['item_name'],
                'so_luong' => (int) $validated['item_quantity'],
                'gia_ban' => (float) $validated['item_price'],
                'khoi_luong_sp' => (int) $validated['item_weight'],
            ]);

            return $order;
        });

        return redirect()
            ->route('orders.index')
            ->with('success', 'Đã tạo đơn mới trong cơ sở dữ liệu.');
    }

    /**
     * Cap nhat thong tin don noi bo.
     */
    public function update(Store_OrderRequest $request, Order $order)
    {
        // Muc tieu: Cap nhat du lieu da validate theo quy tac cua mang don hang va van don.
        $this->authorizeOrderAccess($request, $order);

        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated): void {
            // Cap nhat thong tin chung cua don.
            $order->update([
                'ten_nguoi_nhan' => $validated['receiver_name'],
                'sdt_nguoi_nhan' => $validated['receiver_phone'],
                'dia_chi_chi_tiet' => $validated['receiver_address'],
                'ma_tinh_thanh' => (int) $validated['to_province_id'],
                'ma_quan_huyen' => (int) $validated['to_district_id'],
                'ma_phuong_xa' => $validated['to_ward_code'],
                'trong_luong' => (int) $validated['item_weight'],
                'chieu_dai' => (int) ($validated['length'] ?? 0),
                'chieu_rong' => (int) ($validated['width'] ?? 0),
                'chieu_cao' => (int) ($validated['height'] ?? 0),
                'tien_cod' => (float) ($validated['cod_value'] ?? 0),
            ]);

            // Cap nhat dong chi tiet dau tien, neu chua co thi tao moi.
            $detail = $order->orderDetails()->first();
            if ($detail) {
                $detail->update([
                    'ten_san_pham' => $validated['item_name'],
                    'so_luong' => (int) $validated['item_quantity'],
                    'gia_ban' => (float) $validated['item_price'],
                    'khoi_luong_sp' => (int) $validated['item_weight'],
                ]);
            } else {
                Order_Detail::query()->create([
                    'ma_don_hang' => $order->ma_don_hang,
                    'ten_san_pham' => $validated['item_name'],
                    'so_luong' => (int) $validated['item_quantity'],
                    'gia_ban' => (float) $validated['item_price'],
                    'khoi_luong_sp' => (int) $validated['item_weight'],
                ]);
            }
        });

        return redirect()->route('orders.show', $order)->with('success', 'Đã cập nhật đơn hàng.');
    }

    /**
     * Xoa don hang.
     */
    public function destroy(Request $request, Order $order)
    {
        // Muc tieu: Xoa ban ghi sau khi kiem tra rang buoc cua mang don hang va van don.
        $this->authorizeOrderAccess($request, $order);

        DB::transaction(function () use ($order): void {
            $order->delete();
        });

        return redirect()->route('orders.index')->with('success', 'Đã xóa đơn hàng.');
    }

    /**
     * Hien thi form tao van don GHN (co the prefill tu don co san).
     */
    public function createGhn(Request $request, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang don hang va van don.
        return $this->createShipment($request, $ghnService, app(Carrier_ServiceManager::class));
    }

    /**
     * Hien thi form tao van don tong quat cho da hang van chuyen.
     */
    public function createShipment(Request $request, Ghn_ShippingService $ghnService, Carrier_ServiceManager $carrierManager)
    {
        // Muc tieu: Dieu phoi tao van don theo hang van chuyen trong mang don hang va van don.
        $selectedOrder = null;
        $prefill = [];

        // Neu co order_id thi lay don de dien san vao form GHN.
        if ($request->filled('order_id')) {
            $selectedOrder = $this->scopeOrders($request)
                ->with('orderDetails')
                ->find((int) $request->integer('order_id'));

            // Trich thong tin don de prefill form tao GHN.
            if ($selectedOrder) {
                $firstDetail = $selectedOrder->orderDetails->first();

                $prefill = [
                    'receiver_name' => $selectedOrder->ten_nguoi_nhan,
                    'receiver_phone' => $selectedOrder->sdt_nguoi_nhan,
                    'receiver_address' => $selectedOrder->dia_chi_chi_tiet,
                    'to_province_id' => $selectedOrder->ma_tinh_thanh,
                    'to_district_id' => $selectedOrder->ma_quan_huyen,
                    'to_ward_code' => $selectedOrder->ma_phuong_xa,
                    'item_name' => $firstDetail?->ten_san_pham,
                    'item_weight' => $firstDetail?->khoi_luong_sp ?: $selectedOrder->trong_luong,
                    'item_quantity' => $firstDetail?->so_luong ?: 1,
                    'cod_value' => $selectedOrder->tien_cod,
                    'length' => $selectedOrder->chieu_dai,
                    'width' => $selectedOrder->chieu_rong,
                    'height' => $selectedOrder->chieu_cao,
                ];
            }
        }

        // Tim cau hinh GHN cua user hien tai (hoac cau hinh he thong).
        $shopCarrier = $this->resolveGhnCarrierForUser($request);
        $viettelCarrier = $this->resolveCarrierByNameForUser($request, 'VIETTELPOST');
        $carrierName = $carrierManager->normalizeCarrierName((string) $request->input('carrier_name', 'GHN'));

        $carrierDefaults = [
            'GHN' => [
                'token' => $shopCarrier?->api_token ?: data_get($ghnService->getDefaultCredentials(), 'token'),
                'shop_id' => $shopCarrier?->shop_id ?: data_get($ghnService->getDefaultCredentials(), 'shop_id'),
            ],
            'VIETTELPOST' => [
                'token' => $viettelCarrier?->api_token ?: data_get($carrierManager->getDefaultCredentials('VIETTELPOST'), 'token'),
                'shop_id' => $viettelCarrier?->shop_id ?: data_get($carrierManager->getDefaultCredentials('VIETTELPOST'), 'shop_id'),
                'groupaddress_id' => data_get((array) ($viettelCarrier?->config_json ?? []), 'sender_groupaddress_id') ?: data_get($carrierManager->getDefaultCredentials('VIETTELPOST'), 'sender_groupaddress_id'),
                'customer_id' => data_get((array) ($viettelCarrier?->config_json ?? []), 'customer_id') ?: data_get($carrierManager->getDefaultCredentials('VIETTELPOST'), 'customer_id'),
            ],
        ];

        return view('admin.orders.create-shipment', [
            'selectedCarrier' => $carrierName,
            'supportedCarriers' => $carrierManager->supportedCarriers(),
            'carrierDefaults' => $carrierDefaults,
            'ghnDefaults' => [
                'token' => $shopCarrier?->api_token ?: data_get($ghnService->getDefaultCredentials(), 'token'),
                'shop_id' => $shopCarrier?->shop_id ?: data_get($ghnService->getDefaultCredentials(), 'shop_id'),
            ],
            'prefill' => $prefill,
            'selectedOrder' => $selectedOrder,
            'recentOrders' => $this->scopeOrders($request)
                ->orderByDesc('ma_don_hang')
                ->limit(10)
                ->get(['ma_don_hang', 'ma_tracking', 'ten_nguoi_nhan', 'sdt_nguoi_nhan', 'tien_cod']),
        ]);
    }

    /**
     * API noi bo: lay danh sach tinh/thanh GHN cho combobox.
     */
    public function ghnProvinceOptions(Request $request, Ghn_ShippingService $ghnService)
    {
        [$token, $shopId, $baseUrl] = $this->resolveGhnMetaCredentials($request, $ghnService);

        $provinces = $ghnService->listProvinces($token, $shopId, $baseUrl);

        if ($provinces === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Khong the tai danh sach tinh/thanh GHN. Vui long kiem tra token GHN.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $provinces,
        ]);
    }

    /**
     * API noi bo: lay danh sach quan/huyen GHN cho combobox.
     */
    public function ghnDistrictOptions(Request $request, Ghn_ShippingService $ghnService)
    {
        $provinceId = (int) $request->integer('province_id');
        [$token, $shopId, $baseUrl] = $this->resolveGhnMetaCredentials($request, $ghnService);

        $districts = $provinceId > 0
            ? $ghnService->listDistrictsByProvince($provinceId, $token, $shopId, $baseUrl)
            : $ghnService->listDistricts($token, $shopId, $baseUrl);

        if ($districts === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Khong the tai danh sach quan/huyen GHN. Vui long kiem tra cau hinh token/shop_id GHN.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $districts,
        ]);
    }

    /**
     * API noi bo: lay danh sach phuong/xa GHN theo district_id cho combobox.
     */
    public function ghnWardOptions(Request $request, Ghn_ShippingService $ghnService)
    {
        $districtId = (int) $request->integer('district_id');

        if ($districtId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'district_id khong hop le.',
            ], 422);
        }

        [$token, $shopId, $baseUrl] = $this->resolveGhnMetaCredentials($request, $ghnService);

        $wards = $ghnService->listWardsByDistrict($districtId, $token, $shopId, $baseUrl);

        if ($wards === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Khong the tai danh sach phuong/xa GHN.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $wards,
        ]);
    }

    /**
     * Gui yeu cau tao van don GHN va luu ket qua vao DB.
     */
    public function storeGhn(Tao_Don_Van_ChuyenRequest $request, Ghn_ShippingService $ghnService)
    {
        // Route legacy GHN se ep carrier_name ve GHN roi di vao luong tong quat.
        $request->merge(['carrier_name' => 'GHN']);

        return $this->storeShipment($request, $ghnService, app(ViettelPost_ShippingService::class), app(Carrier_ServiceManager::class));
    }

    /**
     * Tao van don theo hang duoc chon tren form tong quat.
     */
    public function storeShipment(Tao_Don_Van_ChuyenRequest $request, Ghn_ShippingService $ghnService, ViettelPost_ShippingService $viettelService, Carrier_ServiceManager $carrierManager)
    {
        // Buoc 1: Xac dinh hang van chuyen duoc chon o form.
        $carrierName = $carrierManager->normalizeCarrierName((string) $request->input('carrier_name', 'GHN'));

        // Neu user chon Viettel thi tach sang handler rieng de payload/rule de quan ly.
        if ($carrierName === 'VIETTELPOST') {
            return $this->storeViettelShipment($request, $viettelService);
        }

        // Buoc 2: Day la luong GHN.
        $validated = $request->validated();
        $carrier = $this->resolveGhnCarrierForUser($request);

        // Shop bat buoc phai cau hinh token/shop_id rieng hop le.
        if ($this->isShopRole($request) && (! $carrier || ! $carrier->api_token || ! $carrier->shop_id)) {
            return back()
                ->withErrors(['ghn' => 'Shop chưa cấu hình API GHN hợp lệ. Vui lòng vào mục Cấu hình API để khai báo token và shop_id trước.'])
                ->withInput();
        }

        // Build payload chuan theo schema GHN.
        $payload = $this->buildShipmentPayload($validated);

        // Hop nhat credential theo thu tu uu tien de thong bao loi ro rang hon.
        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentials($carrier, $payload, $ghnService);

        if (! $resolvedToken || ! $resolvedShopId) {
            return back()
                ->withErrors(['ghn' => $this->buildMissingGhnCredentialMessage($resolvedToken, $resolvedShopId)])
                ->withInput();
        }

        $payload['__token'] = $resolvedToken;
        $payload['__shop_id'] = (int) $resolvedShopId;
        $payload['__base_url'] = $resolvedBaseUrl;

        // Kiem tra du thong tin nguoi gui truoc khi goi API.
        $missingSenderFields = $this->missingSenderFields($payload);
        if ($missingSenderFields !== []) {
            return back()
                ->withErrors(['ghn' => 'Thiếu thông tin người gửi GHN: '.implode(', ', $missingSenderFields).'. Vui lòng nhập tại form hoặc cấu hình GHN_FROM_* trong .env'])
                ->withInput();
        }

        $senderDistrict = (int) ($payload['from_district_id'] ?? 0);
        $senderWard = trim((string) ($payload['from_ward_code'] ?? ''));
        $receiverDistrict = (int) ($payload['to_district_id'] ?? 0);
        $receiverWard = trim((string) ($payload['to_ward_code'] ?? ''));

        // Validate cap district/ward truoc khi goi create-order de tranh loi 400 tu GHN.
        $senderWardOk = $ghnService->isValidWardForDistrict($senderDistrict, $senderWard, $resolvedToken, (int) $resolvedShopId, $resolvedBaseUrl);
        if ($senderWardOk === false) {
            return back()
                ->withErrors(['ghn' => 'Cặp mã lấy hàng GHN không hợp lệ: district='.$senderDistrict.', ward='.$senderWard.'. Vui lòng kiểm tra lại mã phường/xã theo đúng quận/huyện trên GHN.'])
                ->withInput();
        }

        $receiverWardOk = $ghnService->isValidWardForDistrict($receiverDistrict, $receiverWard, $resolvedToken, (int) $resolvedShopId, $resolvedBaseUrl);
        if ($receiverWardOk === false) {
            return back()
                ->withErrors(['ghn' => 'Cặp mã nhận hàng GHN không hợp lệ: district='.$receiverDistrict.', ward='.$receiverWard.'. Vui lòng kiểm tra lại mã phường/xã theo đúng quận/huyện trên GHN.'])
                ->withInput();
        }

        // Goi GHN tao van don.
        $result = $ghnService->createShipment($payload);

        // GHN bao loi -> tra thang ket qua cho UI.
        if (! ($result['ok'] ?? false)) {
            return back()
            ->withErrors(['ghn' => $this->buildGhnApiErrorMessage($result, $payload)])
                ->withInput()
                ->with('ghn_create_order_result', $result);
        }

        // Luu du lieu van don vao DB, co bat loi de tranh mat thong tin response GHN.
        try {
            // Neu don chua co carrier id hop le thi tao/cap nhat carrier record truoc khi luu don.
            $savedOrder = $this->persistShipmentToDatabase(
                $request,
                $validated,
                $result,
                $carrier?->ma_hang_van_chuyen
                    ? (int) $carrier->ma_hang_van_chuyen
                    : $this->resolveCarrierIdForGhnShipment($request, $resolvedToken, (int) $resolvedShopId, $resolvedBaseUrl)
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('ghn_create_order_result', $result)
                ->with('error', 'GHN đã tạo vận đơn thành công, nhưng lưu vào cơ sở dữ liệu thất bại. Vui lòng thử lại hoặc liên hệ quản trị.');
        }

        return back()
            ->withInput()
            ->with('ghn_create_order_result', $result)
            ->with('success', 'Đã gửi yêu cầu tạo vận đơn GHN thành công và lưu vào cơ sở dữ liệu (mã: '.$savedOrder->ma_tracking.').');
    }

    /**
     * Tao van don Viettel Post va luu vao DB theo schema don hang hien tai.
     */
    private function storeViettelShipment(Tao_Don_Van_ChuyenRequest $request, ViettelPost_ShippingService $viettelService)
    {
        // Buoc 1: Lay input da validate va resolve credential/config Viettel tu form hoac DB.
        $validated = $request->validated();
        $carrier = $this->resolveCarrierByNameForUser($request, 'VIETTELPOST');
        $token = trim((string) ($validated['token'] ?? $carrier?->api_token ?? data_get($viettelService->getDefaultCredentials(), 'token')));

        if ($token === '') {
            return back()
                ->withErrors(['shipment' => 'Thiếu token Viettel Post. Vui lòng cập nhật Cấu hình API hoặc nhập token override.'])
                ->withInput();
        }

        $groupAddressId = (int) ($validated['viettel_groupaddress_id'] ?? data_get((array) ($carrier?->config_json ?? []), 'sender_groupaddress_id') ?? data_get($viettelService->getDefaultCredentials(), 'sender_groupaddress_id'));
        $customerId = (int) ($validated['viettel_customer_id'] ?? data_get((array) ($carrier?->config_json ?? []), 'customer_id') ?? data_get($viettelService->getDefaultCredentials(), 'customer_id'));
        $orderPayment = (int) ($validated['viettel_order_payment'] ?? data_get((array) ($carrier?->config_json ?? []), 'order_payment', 3));
        $senderProvinceName = trim((string) ($validated['from_province_name'] ?? ''));
        $receiverProvinceName = trim((string) ($validated['to_province_name'] ?? ''));
        $manualSenderProvince = (int) ($validated['viettel_sender_province'] ?? 0);
        $manualReceiverProvince = (int) ($validated['viettel_receiver_province'] ?? 0);

        $senderProvince = $manualSenderProvince > 0
            ? $manualSenderProvince
            : (int) ($viettelService->findProvinceIdByName($senderProvinceName) ?? 0);

        if ($senderProvince <= 0) {
            $senderProvince = (int) data_get((array) ($carrier?->config_json ?? []), 'sender_province_id', 56);
        }

        $receiverProvince = $manualReceiverProvince > 0
            ? $manualReceiverProvince
            : (int) ($viettelService->findProvinceIdByName($receiverProvinceName) ?? 0);

        if ($receiverProvince <= 0) {
            $receiverProvince = (int) data_get((array) ($carrier?->config_json ?? []), 'receiver_province_id', $senderProvince);
        }

        $orderService = trim((string) ($validated['viettel_order_service'] ?? data_get((array) ($carrier?->config_json ?? []), 'order_service', 'PHS')));
        $productType = trim((string) ($validated['viettel_product_type'] ?? data_get((array) ($carrier?->config_json ?? []), 'product_type', 'HH')));

        if ($groupAddressId <= 0 || $customerId <= 0) {
            return back()
                ->withErrors(['shipment' => 'Thiếu cấu hình Viettel Post: sender_groupaddress_id hoặc customer_id.'])
                ->withInput();
        }

        if (! in_array($orderPayment, [1, 2, 3], true)) {
            $orderPayment = 3;
        }

        if ($senderProvince <= 0) {
            $senderProvince = 56;
        }

        if ($receiverProvince <= 0) {
            $receiverProvince = $senderProvince;
        }

        if ($orderService === '') {
            $orderService = 'PHS';
        }

        if ($productType === '') {
            $productType = 'HH';
        }

        $senderDistrictName = trim((string) ($validated['from_district_name'] ?? ''));
        $senderWardName = trim((string) ($validated['from_ward_name'] ?? ''));
        $receiverDistrictName = trim((string) ($validated['to_district_name'] ?? ''));
        $receiverWardName = trim((string) ($validated['to_ward_name'] ?? ''));

        $senderDistrict = (int) ($viettelService->findDistrictIdByName($senderProvince, $senderDistrictName) ?? 0);
        if ($senderDistrict <= 0) {
            $senderDistrict = (int) ($validated['from_district_id'] ?? 0);
        }

        $senderWard = (string) ($viettelService->findWardIdByName($senderDistrict, $senderWardName) ?? '');
        if ($senderWard === '') {
            $senderWard = (string) ($validated['from_ward_code'] ?? '');
        }

        $receiverDistrict = (int) ($viettelService->findDistrictIdByName($receiverProvince, $receiverDistrictName) ?? 0);
        if ($receiverDistrict <= 0) {
            $receiverDistrict = (int) ($validated['to_district_id'] ?? 0);
        }

        $receiverWard = (string) ($viettelService->findWardIdByName($receiverDistrict, $receiverWardName) ?? '');
        if ($receiverWard === '') {
            $receiverWard = (string) ($validated['to_ward_code'] ?? '');
        }

        $geoErrors = [];

        if ($manualSenderProvince <= 0 && $senderProvinceName !== '' && $senderProvince <= 0) {
            $geoErrors[] = 'Khong map duoc ma tinh Viettel cho dia chi gui: '.$senderProvinceName.'.';
        }

        if ($manualReceiverProvince <= 0 && $receiverProvinceName !== '' && $receiverProvince <= 0) {
            $geoErrors[] = 'Khong map duoc ma tinh Viettel cho dia chi nhan: '.$receiverProvinceName.'.';
        }

        if ($senderDistrictName !== '' && $senderDistrict <= 0) {
            $geoErrors[] = 'Khong map duoc ma quan/huyen Viettel cho dia chi gui: '.$senderDistrictName.'.';
        }

        if ($receiverDistrictName !== '' && $receiverDistrict <= 0) {
            $geoErrors[] = 'Khong map duoc ma quan/huyen Viettel cho dia chi nhan: '.$receiverDistrictName.'.';
        }

        if ($senderWardName !== '' && trim($senderWard) === '') {
            $geoErrors[] = 'Khong map duoc ma phuong/xa Viettel cho dia chi gui: '.$senderWardName.'.';
        }

        if ($receiverWardName !== '' && trim($receiverWard) === '') {
            $geoErrors[] = 'Khong map duoc ma phuong/xa Viettel cho dia chi nhan: '.$receiverWardName.'.';
        }

        if (! empty($geoErrors)) {
            return back()
                ->withErrors(['shipment' => implode(' ', $geoErrors).' Vui long kiem tra lai tinh/quan/phuong tren form hoac nhap tay ma Viettel.'])
                ->withInput();
        }

        $preValidationWarnings = [];

        // Buoc 2: Validate cap district/ward truoc khi goi API; chan som neu cap ma khong hop le.
        $senderWardOk = $viettelService->isValidWardForDistrict($senderDistrict, $senderWard);
        if ($senderWardOk === false) {
            return back()
                ->withErrors(['shipment' => 'Dia gioi Viettel khong hop le o dia chi gui (district='.$senderDistrict.', ward='.$senderWard.'). Vui long chon lai dia chi.'])
                ->withInput();
        }

        if ($senderWardOk === null) {
            $preValidationWarnings[] = 'Canh bao Viettel: tam thoi khong xac minh duoc cap ma dia chi gui (district='.$senderDistrict.', ward='.$senderWard.').';
        }

        $receiverWardOk = $viettelService->isValidWardForDistrict($receiverDistrict, $receiverWard);
        if ($receiverWardOk === false) {
            return back()
                ->withErrors(['shipment' => 'Dia gioi Viettel khong hop le o dia chi nhan (district='.$receiverDistrict.', ward='.$receiverWard.'). Vui long chon lai dia chi.'])
                ->withInput();
        }

        if ($receiverWardOk === null) {
            $preValidationWarnings[] = 'Canh bao Viettel: tam thoi khong xac minh duoc cap ma dia chi nhan (district='.$receiverDistrict.', ward='.$receiverWard.').';
        }

        $preValidationWarningText = implode(' ', $preValidationWarnings);

        // Nho lai 2 ma Viettel theo tai khoan de lan sau form tu dong dien san.
        $carrier = $this->rememberViettelDefaults(
            $request,
            $carrier,
            $token,
            $groupAddressId,
            $customerId,
            $orderPayment,
            $senderProvince,
            $receiverProvince,
            $orderService,
            $productType
        );

        // Buoc 3: Build payload Viettel theo format doi tac yeu cau.
        $orderNumber = (string) ($validated['client_order_code'] ?? $this->generateTrackingCode());
        $payload = [
            'ORDER_NUMBER' => $orderNumber,
            'GROUPADDRESS_ID' => $groupAddressId,
            'CUS_ID' => $customerId,
            'SENDER_FULLNAME' => $validated['sender_name'] ?? config('services.ghn.from_name'),
            'SENDER_PHONE' => $validated['sender_phone'] ?? config('services.ghn.from_phone'),
            'SENDER_ADDRESS' => $validated['sender_address'] ?? config('services.ghn.from_address'),
            'SENDER_PROVINCE' => $senderProvince,
            'SENDER_DISTRICT' => $senderDistrict,
            'SENDER_WARD' => $senderWard,
            'RECEIVER_FULLNAME' => $validated['receiver_name'],
            'RECEIVER_PHONE' => $validated['receiver_phone'],
            'RECEIVER_ADDRESS' => $validated['receiver_address'],
            'RECEIVER_PROVINCE' => $receiverProvince,
            'RECEIVER_DISTRICT' => $receiverDistrict,
            'RECEIVER_WARD' => $receiverWard,
            'PRODUCT_NAME' => $validated['item_name'],
            'PRODUCT_DESCRIPTION' => $validated['item_name'],
            'PRODUCT_TYPE' => $productType,
            'PRODUCT_WEIGHT' => (int) $validated['item_weight'],
            'PRODUCT_QUANTITY' => (int) $validated['item_quantity'],
            'PRODUCT_PRICE' => (int) $validated['item_price'],
            'PRODUCT_LENGTH' => (int) ($validated['length'] ?? 0),
            'PRODUCT_WIDTH' => (int) ($validated['width'] ?? 0),
            'PRODUCT_HEIGHT' => (int) ($validated['height'] ?? 0),
            'ORDER_SERVICE' => $orderService,
            'MONEY_COLLECTION' => (int) ($validated['cod_value'] ?? 0),
            'MONEY_TOTAL' => (int) (($validated['item_price'] ?? 0) * ($validated['item_quantity'] ?? 1)),
            'MONEY_TOTALFEE' => 0,
            'MONEY_FEECOD' => 0,
            'MONEY_FEEVAS' => 0,
            'MONEY_TOTALVAT' => 0,
            'ORDER_PAYMENT' => $orderPayment,
            'ORDER_NOTE' => $validated['note'] ?? 'Tao don tu Web Van Don',
            '__token' => $token,
        ];

        // Buoc 4: Goi API createOrder.
        $result = $viettelService->createShipment($payload);

        // Buoc 5: Neu API bao loi, tra thong bao day du cho UI.
        if (! ($result['ok'] ?? false)) {
            $status = (int) ($result['status'] ?? 0);
            $rawBody = trim((string) data_get($result, 'data.__raw_body', ''));
            $detail = $rawBody !== '' ? ' | '.$rawBody : '';

            $response = back()
                ->withErrors(['shipment' => 'Viettel Post trả lỗi'.($status > 0 ? ' (HTTP '.$status.')' : '').': '.($result['message'] ?? 'Không rõ nguyên nhân').$detail])
                ->withInput()
                ->with('shipment_create_order_result', $result)
                ->with('shipment_result_carrier', 'VIETTELPOST');

            if ($preValidationWarningText !== '') {
                $response->with('warning', $preValidationWarningText);
            }

            return $response;
        }

        // Buoc 5.1: Hau kiem ngay sau create de dam bao du lieu Viettel khop voi DB noi bo.
        $reconciliation = $this->reconcileViettelShipmentAfterCreate($viettelService, $result, $orderNumber);
        $effectiveResult = $reconciliation['result'];

        // Buoc 6: API thanh cong -> resolve carrier id va luu don vao DB noi bo.
        $carrierId = $carrier?->ma_hang_van_chuyen
            ? (int) $carrier->ma_hang_van_chuyen
            : $this->resolveCarrierIdByName($request, 'VIETTELPOST', $token, null);

        $savedOrder = $this->persistGenericShipmentToDatabase($request, $validated, $effectiveResult, $carrierId, $orderNumber);

        // Neu hau kiem lay duoc status thi cap nhat trang thai noi bo ngay.
        if ($reconciliation['ok']) {
            $savedOrder->update([
                'trang_thai' => $this->mapViettelStatusToInternal($effectiveResult),
            ]);
        }

        $successMessage = 'Đã gửi Viettel Post thành công và lưu vào cơ sở dữ liệu (mã: '.$savedOrder->ma_tracking.').';

        if (! $reconciliation['ok']) {
            $successMessage .= ' Đối soát sau tạo đơn chưa thành công, hệ thống đã lưu đơn và sẽ đồng bộ ở lần tiếp theo.';

            $reconciliationReason = $this->summarizeReconciliationFailure($reconciliation['track_result'] ?? null);
            if ($reconciliationReason !== '') {
                $successMessage .= ' Chi tiết: '.$reconciliationReason;
            }
        }

        $response = back()
            ->withInput()
            ->with('shipment_create_order_result', $effectiveResult)
            ->with('shipment_reconciliation_result', $reconciliation['track_result'])
            ->with('shipment_result_carrier', 'VIETTELPOST')
            ->with('success', $successMessage);

        if ($preValidationWarningText !== '') {
            $response->with('warning', $preValidationWarningText);
        }

        return $response;
    }

    /**
     * Dong bo trang thai GHN cho 1 don cu the.
     */
    public function syncGhnStatus(Request $request, Order $order, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang don hang va van don.
        return $this->syncShipmentStatus(
            $request,
            $order,
            $ghnService,
            app(ViettelPost_ShippingService::class),
            app(Carrier_ServiceManager::class));
    }

    /**
     * Dong bo trang thai theo hang van chuyen cua don hien tai.
     */
    public function syncShipmentStatus(Request $request, Order $order, Ghn_ShippingService $ghnService, ViettelPost_ShippingService $viettelService, Carrier_ServiceManager $carrierManager)
    {
        // Luon chan tu dau neu user khong co quyen tren don.
        $this->authorizeOrderAccess($request, $order);

        // loadMissing tranh query lai neu quan he da duoc nap tu truoc.
        $order->loadMissing('hangVanChuyen');
        $carrierName = $carrierManager->normalizeCarrierName((string) ($order->hangVanChuyen?->ten_hang ?? 'GHN'));

        if ($carrierName === 'GHN') {
            return $this->syncGhnStatusLegacy($request, $order, $ghnService);
        }

        if ($carrierName === 'VIETTELPOST') {
            if (! $order->ma_tracking) {
                return back()->with('error', 'Đơn chưa có mã tracking để đồng bộ Viettel Post.');
            }

            $result = $viettelService->trackShipment((string) $order->ma_tracking);

            if (! ($result['ok'] ?? false)) {
                return back()->with('error', 'Không thể đồng bộ Viettel Post cho đơn '.$order->ma_tracking.': '.($result['message'] ?? 'Không rõ nguyên nhân'));
            }

            $mappedStatus = $this->mapViettelStatusToInternal($result);
            $order->update(['trang_thai' => $mappedStatus]);

            return back()->with('success', 'Đã đồng bộ Viettel Post cho đơn '.$order->ma_tracking.' ('.$mappedStatus.').');
        }

        return back()->with('error', 'Hãng vận chuyển chưa hỗ trợ đồng bộ: '.$carrierName);
    }

    /**
     * Logic dong bo GHN cu, tach rieng de wrapper generic goi lai.
     */
    private function syncGhnStatusLegacy(Request $request, Order $order, Ghn_ShippingService $ghnService)
    {

        // Don phai co ma tracking moi dong bo duoc.
        if (! $order->ma_tracking) {
            return back()->with('error', 'Đơn chưa có mã tracking để đồng bộ GHN.');
        }

        // Nap cau hinh carrier neu chua co.
        $order->loadMissing('hangVanChuyen');

        // Goi GHN lay trang thai moi nhat, co retry 1 lan voi cau hinh hien tai neu token tren don da cu.
        $result = $this->trackGhnStatusWithFallback($request, $order, $ghnService);

        // Neu retry van loi thi thong bao ro nguyen nhan de user xu ly cau hinh.
        if (! ($result['ok'] ?? false)) {
            if ($this->isInvalidGhnTokenError($result)) {
                return back()->with('error', 'Không thể đồng bộ đơn '.$order->ma_tracking.': Token GHN không hợp lệ. Vui lòng vào Cấu hình API để cập nhật token/shop_id rồi thử lại.');
            }

            return back()->with('error', 'Không thể đồng bộ đơn '.$order->ma_tracking.': '.($result['message'] ?? 'Không rõ nguyên nhân'));
        }

        // Map trang thai GHN sang trang thai noi bo.
        $ghnStatus = $this->extractGhnStatus($result);
        $mappedStatus = $this->mapGhnStatusToInternal($ghnStatus);

        $order->update([
            'trang_thai' => $mappedStatus,
        ]);

        return back()->with('success', 'Đã đồng bộ GHN cho đơn '.$order->ma_tracking.' ('.$mappedStatus.').');
    }

    /**
     * Dong bo hang loat trang thai GHN cho cac don gan day.
     */
    public function syncGhnStatuses(Request $request, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang don hang va van don.
        return $this->syncShipmentStatuses($request, $ghnService, app(ViettelPost_ShippingService::class), app(Carrier_ServiceManager::class));
    }

    /**
     * Dong bo hang loat cho tat ca don co tracking theo hang da gan.
     */
    public function syncShipmentStatuses(Request $request, Ghn_ShippingService $ghnService, ViettelPost_ShippingService $viettelService, Carrier_ServiceManager $carrierManager)
    {
        // Lay lo don co tracking de dong bo hang loat, toi da 50 ban ghi moi nhat.
        $orders = $this->scopeOrders($request)
            ->with('hangVanChuyen')
            ->whereNotNull('ma_tracking')
            ->where('ma_tracking', '!=', '')
            ->orderByDesc('ma_don_hang')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Không có đơn nào để đồng bộ.');
        }

        $synced = 0;
        $failed = 0;

        // Chay tung don, route logic theo hang van chuyen da gan tren don.
        foreach ($orders as $order) {
            /** @var Order $order */
            $carrierName = $carrierManager->normalizeCarrierName((string) ($order->hangVanChuyen?->ten_hang ?? 'GHN'));

            if ($carrierName === 'GHN') {
                $result = $this->trackGhnStatusWithFallback($request, $order, $ghnService);
                if (! ($result['ok'] ?? false)) {
                    $failed++;
                    continue;
                }

                $ghnStatus = $this->extractGhnStatus($result);
                $order->update(['trang_thai' => $this->mapGhnStatusToInternal($ghnStatus)]);
                $synced++;
                continue;
            }

            if ($carrierName === 'VIETTELPOST') {
                $result = $viettelService->trackShipment((string) $order->ma_tracking);

                if (! ($result['ok'] ?? false)) {
                    $failed++;
                    continue;
                }

                $order->update(['trang_thai' => $this->mapViettelStatusToInternal($result)]);
                $synced++;
                continue;
            }

            $failed++;
        }

        return back()->with('success', 'Đồng bộ vận đơn xong: '.$synced.' đơn thành công, '.$failed.' đơn lỗi.');
    }

    /**
     * Theo doi don GHN va fallback sang cau hinh GHN hien tai cua user neu can.
     */
    private function trackGhnStatusWithFallback(Request $request, Order $order, Ghn_ShippingService $ghnService): array
    {
        // Lan 1: dung credential dang gan tren chinh don hang.
        $primaryToken = trim((string) ($order->hangVanChuyen?->api_token ?? ''));
        $primaryShopId = $order->hangVanChuyen?->shop_id ? (int) $order->hangVanChuyen->shop_id : null;
        $primaryBaseUrl = $this->resolveBaseUrlFromCarrier($order->hangVanChuyen);

        $result = $ghnService->trackShipment((string) $order->ma_tracking, $primaryToken !== '' ? $primaryToken : null, $primaryShopId, $primaryBaseUrl);

        if (($result['ok'] ?? false) || ! $this->isInvalidGhnTokenError($result)) {
            return $result;
        }

        // Lan 2: token tren don loi -> fallback sang cau hinh GHN moi nhat cua user.
        $fallbackCarrier = $this->resolveGhnCarrierForUser($request);
        $fallbackToken = trim((string) ($fallbackCarrier?->api_token ?? ''));
        $fallbackShopId = $fallbackCarrier?->shop_id ? (int) $fallbackCarrier->shop_id : null;
        $fallbackBaseUrl = $this->resolveBaseUrlFromCarrier($fallbackCarrier);

        if ($fallbackToken === '' || ! $fallbackShopId) {
            return $result;
        }

        if ($fallbackToken === $primaryToken && $fallbackShopId === $primaryShopId) {
            return $result;
        }

        // Chi retry khi credential fallback khac credential primary.
        $retryResult = $ghnService->trackShipment((string) $order->ma_tracking, $fallbackToken, $fallbackShopId, $fallbackBaseUrl);

        if (($retryResult['ok'] ?? false) && $fallbackCarrier) {
            // Retry thanh cong -> cap nhat carrier id tren don de lan sau dung dung credential.
            $order->ma_hang_van_chuyen = (int) $fallbackCarrier->ma_hang_van_chuyen;
            $order->save();
        }

        return $retryResult;
    }

    /**
     * Kiem tra GHN tra ve loi token het han/khong hop le.
     */
    private function isInvalidGhnTokenError(array $result): bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang don hang va van don.
        $message = Str::of((string) ($result['message'] ?? ''))
            ->ascii()
            ->lower()
            ->toString();

        return str_contains($message, 'token is not valid')
            || str_contains($message, 'invalid token')
            || str_contains($message, 'token not valid');
    }

    /**
     * Lay ma hang van chuyen mac dinh (uu tien cau hinh shop neu co).
     */
    private function resolveDefaultCarrierId(Request $request): int
    {
        // Uu tien tuyet doi cau hinh GHN theo user hien tai (neu co).
        $preferredCarrier = $this->resolveGhnCarrierForUser($request);

        if ($preferredCarrier) {
            return (int) $preferredCarrier->ma_hang_van_chuyen;
        }

        // Admin lay cau hinh GHN dung chung hop le; neu chua co thi moi tao.
        $carrier = Hang_Van_Chuyen::query()
            ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
            ->whereNull('ma_nguoi_dung')
            ->where('api_token', '!=', 'pending_token')
            ->whereNotNull('shop_id')
            ->where('shop_id', '!=', '')
            ->latest('ma_hang_van_chuyen')
            ->first();

        if (! $carrier) {
            // Neu DB chua co carrier dung chung, thu khoi tao tu .env.
            $systemToken = trim((string) config('services.ghn.token'));
            $systemShopId = trim((string) config('services.ghn.shop_id'));

            if ($systemToken === '' || $systemShopId === '' || $systemToken === 'pending_token') {
                $fallbackCarrier = Hang_Van_Chuyen::query()
                    ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
                    ->where('api_token', '!=', 'pending_token')
                    ->latest('ma_hang_van_chuyen')
                    ->first();

                if ($fallbackCarrier) {
                    // Fallback cuoi cung: dung bat ky carrier GHN hop le nao co san.
                    return (int) $fallbackCarrier->ma_hang_van_chuyen;
                }

                throw new \RuntimeException('Thiếu cấu hình GHN hợp lệ để gán hãng vận chuyển mặc định.');
            }

            $carrier = Hang_Van_Chuyen::query()->create([
                'ten_hang' => 'GHN',
                'ma_nguoi_dung' => null,
                'api_token' => $systemToken,
                'shop_id' => $systemShopId,
                'moi_truong' => $this->resolveEnvironmentFromBaseUrl((string) config('services.ghn.base_url')),
                'config_json' => null,
            ]);
        }

        return (int) $carrier->ma_hang_van_chuyen;
    }

    /**
     * Tao ma tracking noi bo duy nhat.
     */
    private function generateTrackingCode(): string
    {
        // Lap den khi sinh duoc ma chua ton tai trong DB.
        do {
            $code = 'VD'.now()->format('ymdHis').random_int(100, 999);
        } while (Order::query()->where('ma_tracking', $code)->exists());

        return $code;
    }

    /**
     * Chuyen validated data thanh payload dung format GHN.
     */
    private function buildShipmentPayload(array $validated): array
    {
        // Payload nay map 1-1 theo schema create-order cua GHN.
        return [
            'payment_type_id' => (int) $validated['payment_type_id'],
            'required_note' => $validated['required_note'],
            'from_name' => $this->valueOrConfig($validated, 'sender_name', 'services.ghn.from_name'),
            'from_phone' => $this->valueOrConfig($validated, 'sender_phone', 'services.ghn.from_phone'),
            'from_address' => $this->valueOrConfig($validated, 'sender_address', 'services.ghn.from_address'),
            'from_district_id' => (int) $this->valueOrConfig($validated, 'from_district_id', 'services.ghn.from_district_id'),
            'from_ward_code' => trim((string) $this->valueOrConfig($validated, 'from_ward_code', 'services.ghn.from_ward_code')),
            'return_phone' => $this->valueOrConfig($validated, 'return_phone', 'services.ghn.return_phone'),
            'return_address' => $this->valueOrConfig($validated, 'return_address', 'services.ghn.return_address'),
            'return_district_id' => (int) $this->valueOrConfig($validated, 'return_district_id', 'services.ghn.return_district_id'),
            'return_ward_code' => $this->valueOrConfig($validated, 'return_ward_code', 'services.ghn.return_ward_code'),
            'to_name' => $validated['receiver_name'],
            'to_phone' => $validated['receiver_phone'],
            'to_address' => $validated['receiver_address'],
            'to_ward_code' => trim((string) $validated['to_ward_code']),
            'to_district_id' => (int) $validated['to_district_id'],
            'cod_amount' => (int) ($validated['cod_value'] ?? 0),
            'content' => $validated['item_name'],
            'weight' => (int) $validated['item_weight'],
            'length' => (int) $validated['length'],
            'width' => (int) $validated['width'],
            'height' => (int) $validated['height'],
            'service_type_id' => (int) $validated['service_type_id'],
            'note' => $validated['note'] ?? 'Tạo đơn từ Web Vận Đơn',
            'items' => [$this->buildShipmentItem($validated)],
            '__token' => $validated['token'] ?? null,
            '__shop_id' => $validated['shop_id'] ?? null,
        ];
    }

    /**
     * Tao dong item duy nhat trong payload GHN.
     */
    private function buildShipmentItem(array $validated): array
    {
        // Muc tieu: Dong goi du lieu gui di theo dinh dang yeu cau cua mang don hang va van don.
        return [
            'name' => $validated['item_name'],
            'quantity' => (int) $validated['item_quantity'],
            'price' => (int) $validated['item_price'],
            'weight' => (int) $validated['item_weight'],
        ];
    }

    /**
     * Luu/Cap nhat don hang va chi tiet don tu ket qua GHN.
     */
    private function persistShipmentToDatabase(Request $request, array $validated, array $result, int $carrierId): Order
    {
        // Buoc 1: Trich xuat du lieu can luu tu response GHN.
        $ghnData = (array) data_get($result, 'data.data', []);
        $trackingCode = (string) ($ghnData['order_code'] ?? $validated['client_order_code'] ?? $this->generateTrackingCode());
        $shippingFee = (float) ($ghnData['total_fee'] ?? 0);
        $sourceOrderId = (int) ($validated['source_order_id'] ?? 0);

        // Buoc 2: Build payload don_hang theo schema hien tai.
        $attributes = [
            'ma_nguoi_dung' => (int) ($request->user()->getAuthIdentifier()),
            'ma_hang_van_chuyen' => $carrierId,
            'ten_nguoi_nhan' => $validated['receiver_name'],
            'sdt_nguoi_nhan' => $validated['receiver_phone'],
            'dia_chi_chi_tiet' => $validated['receiver_address'],
            'ma_tinh_thanh' => 0,
            'ma_quan_huyen' => (int) $validated['to_district_id'],
            'ma_phuong_xa' => $validated['to_ward_code'],
            'trong_luong' => (int) $validated['item_weight'],
            'chieu_dai' => (int) $validated['length'],
            'chieu_rong' => (int) $validated['width'],
            'chieu_cao' => (int) $validated['height'],
            'tien_cod' => (float) ($validated['cod_value'] ?? 0),
            'phi_ship_du_kien' => $shippingFee,
            'phi_ship_thuc_te' => $shippingFee,
            'phi_van_chuyen' => $shippingFee,
            'ma_tracking' => $trackingCode,
            'trang_thai' => 'cho_lay_hang',
        ];

        // Buoc 3: Neu co source_order_id thi cap nhat don do, tranh tao trung.
        if ($sourceOrderId > 0) {
            $order = $this->scopeOrders($request)->find($sourceOrderId);
            if ($order) {
                $order->update($attributes);
            }
        }

        // Buoc 4: Neu khong co source order, tim theo tracking -> update hoac create.
        if (! isset($order)) {
            $order = Order::query()->where('ma_tracking', $trackingCode)->first();
            if ($order) {
                $order->update($attributes);
            } else {
                $order = Order::query()->create($attributes);
            }
        }

        // Buoc 5: Dong bo dong chi tiet san pham dau tien cua don.
        $firstDetail = $order->orderDetails()->first();
        $detailAttributes = [
            'ten_san_pham' => $validated['item_name'],
            'so_luong' => (int) $validated['item_quantity'],
            'gia_ban' => (float) $validated['item_price'],
            'khoi_luong_sp' => (int) $validated['item_weight'],
        ];

        if ($firstDetail) {
            $firstDetail->update($detailAttributes);
        } else {
            $order->orderDetails()->create($detailAttributes);
        }

        return $order;
    }

    /**
     * Kiem tra payload da du thong tin nguoi gui GHN hay chua.
     */
    private function missingSenderFields(array $payload): array
    {
        // Mapping key -> label de thong bao loi than thien voi nguoi dung.
        $labels = [
            'from_name' => 'ten_nguoi_gui',
            'from_phone' => 'sdt_nguoi_gui',
            'from_address' => 'dia_chi_lay_hang',
            'from_district_id' => 'from_district_id',
            'from_ward_code' => 'from_ward_code',
        ];

        $missing = [];

        foreach ($labels as $key => $label) {
            $value = $payload[$key] ?? null;

            // district yeu cau la so > 0, khac voi cac field chuoi.
            if ($key === 'from_district_id') {
                if ((int) $value <= 0) {
                    $missing[] = $label;
                }

                continue;
            }

            if (trim((string) $value) === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Hop nhat credential GHN theo thu tu: carrier da luu -> override form -> config he thong.
     */
    private function resolveGhnCredentials(?Hang_Van_Chuyen $carrier, array $payload, Ghn_ShippingService $ghnService): array
    {
        // Thu tu uu tien: carrier trong DB -> override tren form -> defaults cua service/.env.
        if ($carrier && trim((string) $carrier->api_token) !== '' && trim((string) $carrier->shop_id) !== '') {
            return [trim((string) $carrier->api_token), (int) $carrier->shop_id, $this->resolveBaseUrlFromCarrier($carrier)];
        }

        $payloadToken = trim((string) ($payload['__token'] ?? ''));
        $payloadShopId = (int) ($payload['__shop_id'] ?? 0);

        if ($payloadToken !== '' && $payloadShopId > 0) {
            return [$payloadToken, $payloadShopId, $this->resolveBaseUrlFromCarrier($carrier)];
        }

        $defaults = $ghnService->getDefaultCredentials();
        $defaultToken = trim((string) ($defaults['token'] ?? ''));
        $defaultShopId = (int) ($defaults['shop_id'] ?? 0);
        $defaultBaseUrl = trim((string) ($defaults['base_url'] ?? config('services.ghn.base_url')));

        return [$defaultToken !== '' ? $defaultToken : null, $defaultShopId > 0 ? $defaultShopId : null, $defaultBaseUrl !== '' ? $defaultBaseUrl : null];
    }

    /**
     * Tao message loi ro rang khi thieu credential GHN.
     */
    private function buildMissingGhnCredentialMessage(?string $token, ?int $shopId): string
    {
        // Muc tieu: Dong goi du lieu gui di theo dinh dang yeu cau cua mang don hang va van don.
        $missing = [];

        if (! $token) {
            $missing[] = 'token';
        }

        if (! $shopId) {
            $missing[] = 'shop_id';
        }

        return 'Thiếu cấu hình GHN: '.implode(', ', $missing).'. Vui lòng cấu hình trong mục Cấu hình API hoặc nhập override trên form.';
    }

    /**
     * Resolve credential GHN cho endpoint metadata (district/ward).
     */
    private function resolveGhnMetaCredentials(Request $request, Ghn_ShippingService $ghnService): array
    {
        $carrier = $this->resolveGhnCarrierForUser($request);
        $defaults = $ghnService->getDefaultCredentials();

        $token = trim((string) (
            $carrier?->api_token
            ?: data_get($defaults, 'token', '')
            ?: config('services.ghn.token', '')
        ));
        $shopIdValue = $carrier?->shop_id ?: data_get($defaults, 'shop_id') ?: config('services.ghn.shop_id');
        $shopId = is_numeric((string) $shopIdValue) ? (int) $shopIdValue : null;
        $baseUrl = trim((string) data_get($defaults, 'base_url', config('services.ghn.base_url')));

        return [$token !== '' ? $token : null, $shopId, $baseUrl !== '' ? $baseUrl : null];
    }

    /**
     * Chuan hoa thong bao loi GHN de UI hien thi de hieu hon.
     */
    private function buildGhnApiErrorMessage(array $result, array $payload = []): string
    {
        // Muc tieu: Dong goi du lieu gui di theo dinh dang yeu cau cua mang don hang va van don.
        $status = (int) ($result['status'] ?? 0);
        $message = trim((string) ($result['message'] ?? 'Không rõ nguyên nhân'));

        $senderDistrict = (int) ($payload['from_district_id'] ?? 0);
        $senderWard = trim((string) ($payload['from_ward_code'] ?? ''));
        $receiverDistrict = (int) ($payload['to_district_id'] ?? 0);
        $receiverWard = trim((string) ($payload['to_ward_code'] ?? ''));

        $pairHint = '';
        if ($senderDistrict > 0 || $senderWard !== '' || $receiverDistrict > 0 || $receiverWard !== '') {
            $pairHint = sprintf(
                ' [Nguoi gui: district=%d, ward=%s | Nguoi nhan: district=%d, ward=%s]',
                $senderDistrict,
                $senderWard !== '' ? $senderWard : 'N/A',
                $receiverDistrict,
                $receiverWard !== '' ? $receiverWard : 'N/A'
            );
        }

        if ($status > 0) {
            return 'GHN trả về lỗi (HTTP '.$status.'): '.$message.$pairHint;
        }

        return 'GHN trả về lỗi: '.$message.$pairHint;
    }

    /**
     * Dam bao co carrier id hop le de gan cho don GHN vua tao.
     */
    private function resolveCarrierIdForGhnShipment(Request $request, string $token, int $shopId, ?string $baseUrl = null): int
    {
        // Moi_truong duoc suy ra tu base URL de tranh lech dev/prod.
        $environment = $this->resolveEnvironmentFromBaseUrl($baseUrl);

        if ($this->isShopRole($request)) {
            $carrier = Hang_Van_Chuyen::query()->updateOrCreate(
                [
                    'ten_hang' => 'GHN',
                    'ma_nguoi_dung' => (int) $request->user()->getAuthIdentifier(),
                ],
                [
                    'api_token' => $token,
                    'shop_id' => (string) $shopId,
                    'moi_truong' => $environment,
                ]
            );

            return (int) $carrier->ma_hang_van_chuyen;
        }

        $carrier = Hang_Van_Chuyen::query()->updateOrCreate(
            [
                'ten_hang' => 'GHN',
                'ma_nguoi_dung' => null,
            ],
            [
                'api_token' => $token,
                'shop_id' => (string) $shopId,
                'moi_truong' => $environment,
            ]
        );

        return (int) $carrier->ma_hang_van_chuyen;
    }

    /**
     * Uu tien gia tri tu form, neu rong thi fallback sang config.
     */
    private function valueOrConfig(array $validated, string $key, string $configKey): mixed
    {
        // Muc tieu: Xu ly nghiep vu ham valueOrConfig trong mang don hang va van don.
        return ($validated[$key] ?? null) ?: config($configKey);
    }

    /**
     * Tra ve base URL GHN theo moi truong cua carrier; null khi khong co thong tin.
     */
    private function resolveBaseUrlFromCarrier(?Hang_Van_Chuyen $carrier): ?string
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang don hang va van don.
        if (! $carrier) {
            return null;
        }

        return (int) $carrier->moi_truong === 0
            ? 'https://dev-online-gateway.ghn.vn'
            : 'https://online-gateway.ghn.vn';
    }

    /**
     * Chuyen base URL GHN thanh ma moi truong luu DB (dev=0, prod=1).
     */
    private function resolveEnvironmentFromBaseUrl(?string $baseUrl): int
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang don hang va van don.
        $normalized = Str::of((string) $baseUrl)->lower()->toString();

        return str_contains($normalized, 'dev-online-gateway.ghn.vn') ? 0 : 1;
    }

    /**
     * Trich trang thai tu response GHN o nhieu vi tri du phong.
     */
    private function extractGhnStatus(array $result): ?string
    {
        // Thu tu fallback de chiu duoc nhieu dang payload tra ve.
        return data_get($result, 'data.data.status')
            ?: data_get($result, 'data.data.status_name')
            ?: data_get($result, 'data.data.current_status');
    }

    /**
     * Map status GHN sang status noi bo cua he thong.
     */
    private function mapGhnStatusToInternal(?string $ghnStatus): string
    {
        // Muc tieu: Anh xa gia tri dau vao sang gia tri nghiep vu cua mang don hang va van don.
        $status = Str::of((string) $ghnStatus)->ascii()->lower()->replace(' ', '_')->toString();

        if ($status === '') {
            return 'moi';
        }

        if (in_array($status, ['ready_to_pick', 'picking', 'money_collect_picking'], true)) {
            return 'cho_lay_hang';
        }

        if (in_array($status, ['picked', 'storing', 'transporting', 'sorting', 'delivering', 'money_collect_delivering'], true)) {
            return 'dang_van_chuyen';
        }

        if (in_array($status, ['delivered', 'delivered_partial'], true)) {
            return 'da_giao';
        }

        if (in_array($status, ['return', 'return_sorting', 'returning', 'returned', 'delivery_fail', 'cancel'], true)) {
            return 'hoan';
        }

        return 'moi';
    }

    /**
     * Map status Viettel Post sang status noi bo cua he thong.
     */
    private function mapViettelStatusToInternal(array $result): string
    {
        // Muc tieu: Anh xa gia tri dau vao sang gia tri nghiep vu cua mang don hang va van don.
        $status = Str::of((string) data_get($result, 'data.data.ORDER_STATUS', 'dang_van_chuyen'))
            ->ascii()
            ->lower()
            ->toString();

        return str_contains($status, 'deliver') || str_contains($status, 'phat_thanh_cong')
            ? 'da_giao'
            : 'dang_van_chuyen';
    }

    /**
     * Hau kiem Viettel sau khi createOrder thanh cong de tranh case phan hoi mo ho.
     *
     * @return array{ok: bool, result: array, track_result: array|null}
     */
    private function reconcileViettelShipmentAfterCreate(ViettelPost_ShippingService $viettelService, array $createResult, string $fallbackOrderNumber): array
    {
        $createData = (array) data_get($createResult, 'data.data', []);
        $trackingCode = trim((string) (
            $createData['ORDER_NUMBER']
            ?? $createData['order_number']
            ?? $createData['order_code']
            ?? $fallbackOrderNumber
        ));

        if ($trackingCode === '') {
            return [
                'ok' => false,
                'result' => $createResult,
                'track_result' => null,
            ];
        }

        $trackResult = $viettelService->trackShipment($trackingCode);

        if (! ($trackResult['ok'] ?? false)) {
            return [
                'ok' => false,
                'result' => $createResult,
                'track_result' => $trackResult,
            ];
        }

        $trackData = (array) data_get($trackResult, 'data.data', []);
        $mergedData = array_merge($createData, $trackData);

        $reconciledResult = $createResult;
        data_set($reconciledResult, 'data.data', $mergedData);

        return [
            'ok' => true,
            'result' => $reconciledResult,
            'track_result' => $trackResult,
        ];
    }

    /**
     * Tom tat ly do hau kiem Viettel khong thanh cong de hien thi tren UI.
     */
    private function summarizeReconciliationFailure(?array $trackResult): string
    {
        if (! is_array($trackResult) || $trackResult === []) {
            return 'Chưa nhận được dữ liệu tracking ngay sau khi tạo đơn.';
        }

        $status = (int) ($trackResult['status'] ?? 0);
        $message = trim((string) ($trackResult['message'] ?? ''));

        if ($message === '') {
            $rawBody = trim((string) data_get($trackResult, 'data.__raw_body', ''));
            if ($rawBody !== '') {
                $message = $rawBody;
            }
        }

        if ($message === '') {
            return $status > 0
                ? 'Track API trả HTTP '.$status.' nhưng không có message chi tiết.'
                : 'Track API chưa trả về thông tin chi tiết.';
        }

        return $status > 0
            ? 'Track API HTTP '.$status.': '.$message
            : 'Track API: '.$message;
    }

    /**
     * Scope query don hang theo quyen user hien tai.
     */
    private function scopeOrders(Request $request): Builder
    {
        // Query goc cua tat ca luong danh sach/sync/prefill.
        $query = Order::query();

        if ($this->isTransportManagerRole($request)) {
            $carrierIds = $this->resolveManagedCarrierIds($request);

            if ($carrierIds === []) {
                // Khong co chanh xe lien ket thi tra ve tap rong.
                return $query->whereRaw('1 = 0');
            }

            // Quan ly chanh xe chi thay don da duoc shop giao cho chanh xe cua minh.
            return $query->whereHas('externalRouteBills', function (Builder $billQuery) use ($carrierIds): void {
                $billQuery->whereIn('ma_nha_xe', $carrierIds);
            });
        }

        if ($this->isShopRole($request)) {
            // Role shop chi duoc thao tac tren don cua chinh minh.
            $query->where('ma_nguoi_dung', (int) $request->user()->getAuthIdentifier());
        }

        return $query;
    }

    /**
     * Chan shop truy cap don khong thuoc ve minh.
     */
    private function authorizeOrderAccess(Request $request, Order $order): void
    {
        // Muc tieu: Xu ly nghiep vu ham authorizeOrderAccess trong mang don hang va van don.
        if ($this->isTransportManagerRole($request)) {
            $carrierIds = $this->resolveManagedCarrierIds($request);

            if ($carrierIds === []) {
                abort(403, 'Bạn chưa được liên kết chành xe để nhận đơn.');
            }

            $isAssignedToManagerCarrier = External_Route_Bill::query()
                ->where('ma_don_hang', (int) $order->ma_don_hang)
                ->whereIn('ma_nha_xe', $carrierIds)
                ->exists();

            if (! $isAssignedToManagerCarrier) {
                abort(403, 'Bạn chỉ được thao tác đơn được giao cho chành xe của mình.');
            }

            return;
        }

        if (! $this->isShopRole($request)) {
            return;
        }

        if ((int) $order->ma_nguoi_dung !== (int) $request->user()->getAuthIdentifier()) {
            abort(403, 'Bạn không có quyền thao tác đơn hàng của shop khác.');
        }
    }

    /**
     * Tim cau hinh GHN phu hop voi user hien tai.
     */
    private function resolveGhnCarrierForUser(Request $request): ?Hang_Van_Chuyen
    {
        // ownerId la user hien tai, dung de loc cau hinh theo ngu canh role.
        $ownerId = (int) $request->user()->getAuthIdentifier();

        if ($this->isShopRole($request)) {
            return Hang_Van_Chuyen::query()
                ->where('ma_nguoi_dung', $ownerId)
                ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
                ->latest('ma_hang_van_chuyen')
                ->first();
        }

        $sharedCarrier = Hang_Van_Chuyen::query()
            ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
            ->whereNull('ma_nguoi_dung')
            ->where('api_token', '!=', 'pending_token')
            ->whereNotNull('shop_id')
            ->where('shop_id', '!=', '')
            ->latest('ma_hang_van_chuyen')
            ->first();

        if ($sharedCarrier) {
            // Admin uu tien ban ghi dung chung co token/shop_id hop le.
            return $sharedCarrier;
        }

        // Neu khong co shared, fallback ve ban ghi theo owner hien tai.
        return Hang_Van_Chuyen::query()
            ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
            ->where('ma_nguoi_dung', $ownerId)
            ->where('api_token', '!=', 'pending_token')
            ->whereNotNull('shop_id')
            ->where('shop_id', '!=', '')
            ->latest('ma_hang_van_chuyen')
            ->first();
    }

    /**
     * Tim cau hinh carrier theo ten hang cho user hien tai.
     */
    private function resolveCarrierByNameForUser(Request $request, string $carrierName): ?Hang_Van_Chuyen
    {
        // Chuan hoa ten hang de de xu ly so khop Viettel/GHN.
        $normalizedCarrier = Str::of($carrierName)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->toString();
        $ownerId = (int) $request->user()->getAuthIdentifier();

        $query = Hang_Van_Chuyen::query();

        if ($normalizedCarrier === 'viettelpost') {
            // Viettel co the duoc luu voi ten bien the, nen dung like '%viettel%'.
            $query->whereRaw('LOWER(ten_hang) like ?', ['%viettel%']);
        } else {
            $query->whereRaw('LOWER(ten_hang) = ?', [Str::lower($carrierName)]);
        }

        if ($this->isShopRole($request)) {
            return $query->where('ma_nguoi_dung', $ownerId)->latest('ma_hang_van_chuyen')->first();
        }

        $shared = (clone $query)
            ->whereNull('ma_nguoi_dung')
            ->latest('ma_hang_van_chuyen')
            ->first();

        if ($shared) {
            // Admin uu tien cau hinh dung chung truoc cau hinh theo owner.
            return $shared;
        }

        if ($normalizedCarrier === 'viettelpost') {
            $ownerSpecificViettel = (clone $query)
                ->whereNotNull('ma_nguoi_dung')
                ->orderByRaw('CASE WHEN shop_id IS NULL OR shop_id = "" THEN 1 ELSE 0 END')
                ->latest('ma_hang_van_chuyen')
                ->first();

            if ($ownerSpecificViettel) {
                // Khi admin khong co ban ghi shared, uu tien dung cau hinh Viettel owner co shop_id hop le.
                return $ownerSpecificViettel;
            }
        }

        return $query
            ->where('ma_nguoi_dung', $ownerId)
            ->latest('ma_hang_van_chuyen')
            ->first();
    }

    /**
     * Tao/cap nhat carrier theo ten hang de gan vao don vua tao.
     */
    private function resolveCarrierIdByName(Request $request, string $carrierName, string $token, ?string $shopId): int
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang don hang va van don.
        $ownerId = $this->isShopRole($request) ? (int) $request->user()->getAuthIdentifier() : null;
        $tenHang = $carrierName === 'VIETTELPOST' ? 'VIETTEL_POST' : $carrierName;

        if ($tenHang === 'VIETTEL_POST' && ($shopId === null || trim($shopId) === '')) {
            $fallbackShopId = Hang_Van_Chuyen::query()
                ->whereRaw('LOWER(ten_hang) like ?', ['%viettel%'])
                ->whereNotNull('shop_id')
                ->where('shop_id', '!=', '')
                ->orderByRaw('CASE WHEN ma_nguoi_dung IS NULL THEN 1 ELSE 0 END')
                ->latest('ma_hang_van_chuyen')
                ->value('shop_id');

            $shopId = $fallbackShopId ? (string) $fallbackShopId : null;
        }

        $carrier = Hang_Van_Chuyen::query()->updateOrCreate(
            [
                'ten_hang' => $tenHang,
                'ma_nguoi_dung' => $ownerId,
            ],
            [
                'api_token' => $token,
                'shop_id' => $shopId,
                'moi_truong' => 1,
            ]
        );

        return (int) $carrier->ma_hang_van_chuyen;
    }

    /**
     * Luu token + ma customer/groupaddress cua Viettel Post theo tai khoan hien tai.
     */
    private function rememberViettelDefaults(
        Request $request,
        ?Hang_Van_Chuyen $carrier,
        string $token,
        int $groupAddressId,
        int $customerId,
        int $orderPayment,
        int $senderProvince,
        int $receiverProvince,
        string $orderService,
        string $productType
    ): Hang_Van_Chuyen
    {
        // Luu lai defaults de lan tao don sau user khong phai nhap lai thong so Viettel.
        $ownerId = $carrier?->ma_nguoi_dung;

        if ($ownerId === null && $this->isShopRole($request)) {
            $ownerId = (int) $request->user()->getAuthIdentifier();
        }

        if ($ownerId === null && ! $this->isShopRole($request)) {
            $ownerId = Hang_Van_Chuyen::query()
                ->whereRaw('LOWER(ten_hang) like ?', ['%viettel%'])
                ->whereNotNull('ma_nguoi_dung')
                ->orderByRaw('CASE WHEN shop_id IS NULL OR shop_id = "" THEN 1 ELSE 0 END')
                ->latest('ma_hang_van_chuyen')
                ->value('ma_nguoi_dung');
        }

        $resolvedShopId = $carrier?->shop_id;
        if (($resolvedShopId === null || trim((string) $resolvedShopId) === '') && $customerId > 0) {
            $resolvedShopId = (string) $customerId;
        }

        $baseConfig = (array) ($carrier?->config_json ?? []);

        $savedCarrier = Hang_Van_Chuyen::query()->updateOrCreate(
            [
                'ten_hang' => 'VIETTEL_POST',
                'ma_nguoi_dung' => $ownerId,
            ],
            [
                'api_token' => $token,
                'shop_id' => $resolvedShopId,
                'moi_truong' => $carrier?->moi_truong ?? 0,
                'config_json' => array_merge($baseConfig, [
                    'api_url' => 'https://partner.viettelpost.vn',
                    'customer_id' => $customerId,
                    'sender_groupaddress_id' => $groupAddressId,
                    'order_payment' => $orderPayment,
                    'sender_province_id' => $senderProvince,
                    'receiver_province_id' => $receiverProvince,
                    'order_service' => $orderService,
                    'product_type' => $productType,
                    'saved_at' => now()->toDateTimeString(),
                ]),
            ]
        );

        return $savedCarrier;
    }

    /**
     * Luu don hang da tao boi provider bat ky vao schema don_hang hien tai.
     */
    private function persistGenericShipmentToDatabase(Request $request, array $validated, array $result, int $carrierId, string $fallbackTracking): Order
    {
        // Tuong tu luong GHN, nhung linh hoat hon de doc du lieu tu nhieu provider.
        $dataNode = (array) data_get($result, 'data.data', []);
        $trackingCode = (string) (
            $dataNode['ORDER_NUMBER']
            ?? $dataNode['order_number']
            ?? $dataNode['order_code']
            ?? $fallbackTracking
        );

        $shippingFee = (float) (
            $dataNode['MONEY_TOTALFEE']
            ?? $dataNode['total_fee']
            ?? 0
        );

        $order = Order::query()->updateOrCreate(
            ['ma_tracking' => $trackingCode],
            [
                // updateOrCreate theo tracking giup idempotent khi goi lap lai cung ma don.
                'ma_nguoi_dung' => (int) ($request->user()->getAuthIdentifier()),
                'ma_hang_van_chuyen' => $carrierId,
                'ten_nguoi_nhan' => $validated['receiver_name'],
                'sdt_nguoi_nhan' => $validated['receiver_phone'],
                'dia_chi_chi_tiet' => $validated['receiver_address'],
                'ma_tinh_thanh' => 0,
                'ma_quan_huyen' => (int) $validated['to_district_id'],
                'ma_phuong_xa' => $validated['to_ward_code'],
                'trong_luong' => (int) $validated['item_weight'],
                'chieu_dai' => (int) $validated['length'],
                'chieu_rong' => (int) $validated['width'],
                'chieu_cao' => (int) $validated['height'],
                'tien_cod' => (float) ($validated['cod_value'] ?? 0),
                'phi_ship_du_kien' => $shippingFee,
                'phi_ship_thuc_te' => $shippingFee,
                'phi_van_chuyen' => $shippingFee,
                'trang_thai' => 'cho_lay_hang',
            ]
        );

        $detail = $order->orderDetails()->first();
        $detailPayload = [
            'ten_san_pham' => $validated['item_name'],
            'so_luong' => (int) $validated['item_quantity'],
            'gia_ban' => (float) $validated['item_price'],
            'khoi_luong_sp' => (int) $validated['item_weight'],
        ];

        if ($detail) {
            // Neu da co detail thi update de dong bo du lieu moi nhat tu form.
            $detail->update($detailPayload);
        } else {
            // Neu chua co detail thi tao moi ban ghi dau tien.
            $order->orderDetails()->create($detailPayload);
        }

        return $order;
    }

    /**
    * Xac dinh user hien tai co role nghiep vu shop/chanh xe hay khong.
     */
    private function isShopRole(Request $request): bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang don hang va van don.
        $role = Str::of((string) ($request->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        return in_array($role, ['chushop', 'quanlychanhxe'], true);
    }

    /**
     * Xac dinh user hien tai co role quan ly chanh xe hay khong.
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
     * Tim danh sach ma_nha_xe ma quan ly chanh xe hien tai duoc phep nhan don.
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

        return Nha_Xe::query()
            ->get(['ma_nha_xe', 'ten_nha_xe', 'so_dien_thoai'])
            ->filter(function (Nha_Xe $carrier) use ($normalizedUnitNames, $normalizedPhone): bool {
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

    /**
     * Dam bao manager chi thao tac duoc tren yeu cau gui chanh xe cua minh.
     */
    private function authorizeExternalRouteBillForManager(Request $request, Order $order, External_Route_Bill $bill): void
    {
        if (! $this->isTransportManagerRole($request)) {
            abort(403);
        }

        if ((int) $bill->ma_don_hang !== (int) $order->ma_don_hang) {
            abort(404);
        }

        $carrierIds = $this->resolveManagedCarrierIds($request);

        if (! in_array((int) $bill->ma_nha_xe, $carrierIds, true)) {
            abort(403, 'Bạn không có quyền xử lý yêu cầu này.');
        }
    }

    /**
     * Chuan hoa gia tri filter trang thai don.
     */
    private function normalizeOrderStatusFilter(string $status): string
    {
        // Muc tieu: Chuan hoa du lieu dau vao de xu ly on dinh trong mang don hang va van don.
        $normalized = Str::of($status)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();

        return in_array($normalized, ['moi', 'cho_lay_hang', 'dang_van_chuyen', 'da_giao', 'hoan'], true)
            ? $normalized
            : '';
    }
}




