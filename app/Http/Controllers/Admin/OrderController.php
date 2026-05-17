<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/OrderController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| FILE CU - ORDER CONTROLLER
|--------------------------------------------------------------------------
| Neu file nay con ton tai, no la ban ten cu cua controller don hang.
| Co the dung de doi chieu, nhung route hien tai dang tro den Don_HangController.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Store_OrderRequest;
use App\Http\Requests\Admin\Tao_Don_GhnRequest;
use App\Models\Hang_Van_Chuyen;
use App\Models\Order;
use App\Models\Order_Detail;
use App\Services\Ghn_ShippingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LUU Y CHO NGUOI MOI HOC
    |--------------------------------------------------------------------------
    | File nay la ban cu (legacy), logic gan nhu trung voi Don_HangController.
    | Neu ban dang hoc luong hien tai, uu tien doc Don_HangController truoc.
    */

    /**
     * Danh sach don hang theo quyen user trong phien ban controller cu.
     */
    public function index(Request $request)
    {
        // Muc tieu: Tai danh sach ban ghi theo bo loc va pham vi quyen cua mang don hang va van don.
        return view('admin.orders.index', [
            'orders' => $this->scopeOrders($request)
                ->with('hangVanChuyen')
                ->orderByDesc('ma_don_hang')
                ->limit(50)
                ->get(),
        ]);
    }

    /**
     * Hien thi form tao don noi bo (legacy).
     */
    public function create()
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang don hang va van don.
        return view('admin.orders.create');
    }

    /**
     * Xem chi tiet don hang va nap cac quan he phuc vu giao dien.
     */
    public function show(Request $request, Order $order)
    {
        // Muc tieu: Tai chi tiet ban ghi de hien thi theo mang don hang va van don.
        $this->authorizeOrderAccess($request, $order);

        $order->load(['hangVanChuyen', 'orderDetails', 'nguoiDung']);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Form sua dung chung giao dien voi trang show.
     */
    public function edit(Request $request, Order $order)
    {
        // Muc tieu: Nap du lieu hien tai de chinh sua theo mang don hang va van don.
        return $this->show($request, $order);
    }

    /**
     * Tao don noi bo moi va dong chi tiet dau tien.
     */
    public function store(Store_OrderRequest $request)
    {
        // Muc tieu: Luu du lieu nghiep vu theo quy tac cua mang don hang va van don.
        $validated = $request->validated();

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

        return redirect()
            ->route('orders.index')
            ->with('success', 'Đã tạo đơn mới trong cơ sở dữ liệu.');
    }

    /**
     * Cap nhat don noi bo va cap nhat/tao chi tiet dau tien.
     */
    public function update(Store_OrderRequest $request, Order $order)
    {
        // Muc tieu: Cap nhat du lieu da validate theo quy tac cua mang don hang va van don.
        $this->authorizeOrderAccess($request, $order);

        $validated = $request->validated();

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

        return redirect()->route('orders.show', $order)->with('success', 'Đã cập nhật đơn hàng.');
    }

    /**
     * Xoa don hang sau khi kiem tra quyen.
     */
    public function destroy(Request $request, Order $order)
    {
        // Muc tieu: Xoa ban ghi sau khi kiem tra rang buoc cua mang don hang va van don.
        $this->authorizeOrderAccess($request, $order);

        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Đã xóa đơn hàng.');
    }

    /**
     * Hien thi form tao GHN va prefill tu don nguon neu co.
     */
    public function createGhn(Request $request, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang don hang va van don.
        $selectedOrder = null;
        $prefill = [];

        if ($request->filled('order_id')) {
            $selectedOrder = $this->scopeOrders($request)
                ->with('orderDetails')
                ->find((int) $request->integer('order_id'));

            if ($selectedOrder) {
                $firstDetail = $selectedOrder->orderDetails->first();

                $prefill = [
                    'receiver_name' => $selectedOrder->ten_nguoi_nhan,
                    'receiver_phone' => $selectedOrder->sdt_nguoi_nhan,
                    'receiver_address' => $selectedOrder->dia_chi_chi_tiet,
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

        $shopCarrier = $this->resolveGhnCarrierForUser($request);

        return view('admin.orders.create-ghn', [
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
     * Tao van don GHN va luu vao DB theo luong legacy.
     */
    public function storeGhn(Tao_Don_GhnRequest $request, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Luu du lieu nghiep vu theo quy tac cua mang don hang va van don.
        $validated = $request->validated();
        $carrier = $this->resolveGhnCarrierForUser($request);

        if ($this->isShopRole($request) && (! $carrier || ! $carrier->api_token || ! $carrier->shop_id)) {
            return back()
                ->withErrors(['ghn' => 'Shop chưa cấu hình API GHN hợp lệ. Vui lòng vào mục Cấu hình API để khai báo token và shop_id trước.'])
                ->withInput();
        }

        $payload = $this->buildShipmentPayload($validated);

        if ($carrier && $carrier->api_token && $carrier->shop_id) {
            $payload['__token'] = $carrier->api_token;
            $payload['__shop_id'] = (int) $carrier->shop_id;
        }

        if (! $this->hasEnoughSenderInfo($payload)) {
            return back()
                ->withErrors(['ghn' => 'Thiếu thông tin người gửi GHN. Vui lòng nhập tại form hoặc cấu hình GHN_FROM_* trong .env'])
                ->withInput();
        }

        $result = $ghnService->createShipment($payload);

        if (! $result['ok']) {
            return back()
                ->withErrors(['ghn' => 'GHN trả về lỗi: '.($result['message'] ?? 'Unknown')])
                ->withInput()
                ->with('ghn_create_order_result', $result);
        }

        try {
            $savedOrder = $this->persistShipmentToDatabase(
                $request,
                $validated,
                $result,
                $carrier?->ma_hang_van_chuyen ? (int) $carrier->ma_hang_van_chuyen : $this->resolveDefaultCarrierId($request)
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
     * Dong bo trang thai GHN cho mot don.
     */
    public function syncGhnStatus(Request $request, Order $order, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang don hang va van don.
        $this->authorizeOrderAccess($request, $order);

        if (! $order->ma_tracking) {
            return back()->with('error', 'Đơn chưa có mã tracking để đồng bộ GHN.');
        }

        $order->loadMissing('hangVanChuyen');

        $result = $ghnService->trackShipment(
            (string) $order->ma_tracking,
            $order->hangVanChuyen?->api_token,
            $order->hangVanChuyen?->shop_id ? (int) $order->hangVanChuyen->shop_id : null
        );

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', 'Không thể đồng bộ đơn '.$order->ma_tracking.': '.($result['message'] ?? 'Unknown'));
        }

        $ghnStatus = $this->extractGhnStatus($result);
        $mappedStatus = $this->mapGhnStatusToInternal($ghnStatus);

        $order->update([
            'trang_thai' => $mappedStatus,
        ]);

        return back()->with('success', 'Đã đồng bộ GHN cho đơn '.$order->ma_tracking.' ('.$mappedStatus.').');
    }

    /**
     * Dong bo GHN hang loat cho cac don gan day.
     */
    public function syncGhnStatuses(Request $request, Ghn_ShippingService $ghnService)
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang don hang va van don.
        $orders = $this->scopeOrders($request)
            ->with('hangVanChuyen')
            ->whereNotNull('ma_tracking')
            ->whereHas('hangVanChuyen', function ($query) {
                $query->whereRaw('LOWER(ten_hang) = ?', ['ghn']);
            })
            ->orderByDesc('ma_don_hang')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Không có đơn GHN nào để đồng bộ.');
        }

        $synced = 0;
        $failed = 0;

        foreach ($orders as $order) {
            /** @var Order $order */
            $result = $ghnService->trackShipment(
                (string) $order->ma_tracking,
                $order->hangVanChuyen?->api_token,
                $order->hangVanChuyen?->shop_id ? (int) $order->hangVanChuyen->shop_id : null
            );

            if (! ($result['ok'] ?? false)) {
                $failed++;
                continue;
            }

            $ghnStatus = $this->extractGhnStatus($result);
            $mappedStatus = $this->mapGhnStatusToInternal($ghnStatus);

            $order->update([
                'trang_thai' => $mappedStatus,
            ]);

            $synced++;
        }

        return back()->with('success', 'Đồng bộ GHN xong: '.$synced.' đơn thành công, '.$failed.' đơn lỗi.');
    }

    /**
     * Resolve carrier mac dinh de gan vao don moi.
     */
    private function resolveDefaultCarrierId(Request $request): int
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang don hang va van don.
        if ($this->isShopRole($request)) {
            $shopCarrier = $this->resolveGhnCarrierForUser($request);

            if ($shopCarrier) {
                return (int) $shopCarrier->ma_hang_van_chuyen;
            }
        }

        $carrier = Hang_Van_Chuyen::query()->firstOrCreate(
            ['ten_hang' => 'GHN', 'ma_nguoi_dung' => null],
            [
                'api_token' => trim((string) config('services.ghn.token')),
                'shop_id' => config('services.ghn.shop_id') ? (string) config('services.ghn.shop_id') : null,
                'moi_truong' => 1,
            ]
        );

        return (int) $carrier->ma_hang_van_chuyen;
    }

    /**
     * Sinh ma tracking noi bo duy nhat.
     */
    private function generateTrackingCode(): string
    {
        // Muc tieu: Xu ly nghiep vu ham generateTrackingCode trong mang don hang va van don.
        do {
            $code = 'VD'.now()->format('ymdHis').random_int(100, 999);
        } while (Order::query()->where('ma_tracking', $code)->exists());

        return $code;
    }

    /**
     * Build payload GHN tu input da validate.
     */
    private function buildShipmentPayload(array $validated): array
    {
        // Muc tieu: Dong goi du lieu gui di theo dinh dang yeu cau cua mang don hang va van don.
        return [
            'payment_type_id' => (int) $validated['payment_type_id'],
            'required_note' => $validated['required_note'],
            'from_name' => $this->valueOrConfig($validated, 'sender_name', 'services.ghn.from_name'),
            'from_phone' => $this->valueOrConfig($validated, 'sender_phone', 'services.ghn.from_phone'),
            'from_address' => $this->valueOrConfig($validated, 'sender_address', 'services.ghn.from_address'),
            'from_district_id' => (int) $this->valueOrConfig($validated, 'from_district_id', 'services.ghn.from_district_id'),
            'from_ward_code' => $this->valueOrConfig($validated, 'from_ward_code', 'services.ghn.from_ward_code'),
            'return_phone' => $this->valueOrConfig($validated, 'return_phone', 'services.ghn.return_phone'),
            'return_address' => $this->valueOrConfig($validated, 'return_address', 'services.ghn.return_address'),
            'return_district_id' => (int) $this->valueOrConfig($validated, 'return_district_id', 'services.ghn.return_district_id'),
            'return_ward_code' => $this->valueOrConfig($validated, 'return_ward_code', 'services.ghn.return_ward_code'),
            'to_name' => $validated['receiver_name'],
            'to_phone' => $validated['receiver_phone'],
            'to_address' => $validated['receiver_address'],
            'to_ward_code' => $validated['to_ward_code'],
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
     * Build item duy nhat trong payload GHN.
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
     * Luu ket qua tao GHN vao don_hang va chi_tiet_don_hang.
     */
    private function persistShipmentToDatabase(Request $request, array $validated, array $result, int $carrierId): Order
    {
        // Muc tieu: Xu ly nghiep vu ham persistShipmentToDatabase trong mang don hang va van don.
        $ghnData = (array) data_get($result, 'data.data', []);
        $trackingCode = (string) ($ghnData['order_code'] ?? $validated['client_order_code'] ?? $this->generateTrackingCode());
        $shippingFee = (float) ($ghnData['total_fee'] ?? 0);
        $sourceOrderId = (int) ($validated['source_order_id'] ?? 0);

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

        if ($sourceOrderId > 0) {
            $order = $this->scopeOrders($request)->find($sourceOrderId);
            if ($order) {
                $order->update($attributes);
            }
        }

        if (! isset($order)) {
            $order = Order::query()->where('ma_tracking', $trackingCode)->first();
            if ($order) {
                $order->update($attributes);
            } else {
                $order = Order::query()->create($attributes);
            }
        }

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
    private function hasEnoughSenderInfo(array $payload): bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang don hang va van don.
        return ! empty($payload['from_name'])
            && ! empty($payload['from_phone'])
            && ! empty($payload['from_address'])
            && ! empty($payload['from_district_id'])
            && ! empty($payload['from_ward_code']);
    }

    /**
     * Uu tien gia tri tu form, neu trong thi fallback sang config.
     */
    private function valueOrConfig(array $validated, string $key, string $configKey): mixed
    {
        // Muc tieu: Xu ly nghiep vu ham valueOrConfig trong mang don hang va van don.
        return ($validated[$key] ?? null) ?: config($configKey);
    }

    /**
     * Trich status GHN tu cac field du phong trong response.
     */
    private function extractGhnStatus(array $result): ?string
    {
        // Muc tieu: Trich xuat thong tin can dung tu response trong mang don hang va van don.
        return data_get($result, 'data.data.status')
            ?: data_get($result, 'data.data.status_name')
            ?: data_get($result, 'data.data.current_status');
    }

    /**
     * Map status GHN sang status noi bo he thong.
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
     * Scope query don hang theo quyen user hien tai.
     */
    private function scopeOrders(Request $request): Builder
    {
        // Muc tieu: Xu ly nghiep vu ham scopeOrders trong mang don hang va van don.
        $query = Order::query();

        if ($this->isShopRole($request)) {
            $query->where('ma_nguoi_dung', (int) $request->user()->getAuthIdentifier());
        }

        return $query;
    }

    /**
     * Chan thao tac don cua shop khac khi user la role shop.
     */
    private function authorizeOrderAccess(Request $request, Order $order): void
    {
        // Muc tieu: Xu ly nghiep vu ham authorizeOrderAccess trong mang don hang va van don.
        if (! $this->isShopRole($request)) {
            return;
        }

        if ((int) $order->ma_nguoi_dung !== (int) $request->user()->getAuthIdentifier()) {
            abort(403, 'Bạn không có quyền thao tác đơn hàng của shop khác.');
        }
    }

    /**
     * Tim cau hinh GHN theo ngu canh role hien tai.
     */
    private function resolveGhnCarrierForUser(Request $request): ?Hang_Van_Chuyen
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang don hang va van don.
        if ($this->isShopRole($request)) {
            return Hang_Van_Chuyen::query()
                ->where('ma_nguoi_dung', (int) $request->user()->getAuthIdentifier())
                ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
                ->latest('ma_hang_van_chuyen')
                ->first();
        }

        return Hang_Van_Chuyen::query()
            ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
            ->whereNull('ma_nguoi_dung')
            ->latest('ma_hang_van_chuyen')
            ->first();
    }

    /**
     * Xac dinh user hien tai co thuoc nhom role shop hay khong.
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
}




