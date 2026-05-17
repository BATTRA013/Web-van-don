<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/DashboardController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER TONG QUAN DASHBOARD
|--------------------------------------------------------------------------
| Tong hop KPI va danh sach don gan day de hien thi tren dashboard.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cod_Reconciliation;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Hien thi dashboard theo scope quyen cua user dang dang nhap.
     */
    public function index(Request $request): View
    {
        $orderQuery = $this->scopeOrders($request);

        $totalOrders = (clone $orderQuery)->count();
        $inTransitOrders = (clone $orderQuery)
            ->whereIn('trang_thai', ['cho_lay_hang', 'dang_van_chuyen'])
            ->count();

        $deliveredOrders = (clone $orderQuery)
            ->where('trang_thai', 'da_giao')
            ->count();

        $closedOrders = (clone $orderQuery)
            ->whereIn('trang_thai', ['da_giao', 'hoan'])
            ->count();

        $deliveryRate = $closedOrders > 0
            ? round(($deliveredOrders / $closedOrders) * 100, 1)
            : 0.0;

        $codPendingQuery = Cod_Reconciliation::query()->whereIn('trang_thai', ['cho_doi_soat', 'pending']);

        if ($this->isShopScoped($request)) {
            $userId = (int) $request->user()->getAuthIdentifier();
            $codPendingQuery->whereHas('order', function (Builder $query) use ($userId): void {
                $query->where('ma_nguoi_dung', $userId);
            });
        }

        $codPendingCount = $codPendingQuery->count();

        $recentOrders = (clone $orderQuery)
            ->with('hangVanChuyen')
            ->orderByDesc('ma_don_hang')
            ->limit(5)
            ->get();

        $recentActivities = [
            'Đã ghi nhận '.$inTransitOrders.' đơn đang trong quá trình vận chuyển.',
            'Đang có '.$codPendingCount.' bản ghi đối soát COD chờ xử lý.',
            'Tỷ lệ giao thành công hiện tại đạt '.$deliveryRate.'% trên nhóm đơn đã chốt trạng thái.',
        ];

        return view('admin.dashboard.index', [
            'kpis' => [
                'total_orders' => $totalOrders,
                'in_transit_orders' => $inTransitOrders,
                'cod_pending' => $codPendingCount,
                'delivery_rate' => $deliveryRate,
            ],
            'recentOrders' => $recentOrders,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Scope don hang theo vai tro truy cap.
     */
    private function scopeOrders(Request $request): Builder
    {
        $query = Order::query();

        if ($this->isShopScoped($request)) {
            $query->where('ma_nguoi_dung', (int) $request->user()->getAuthIdentifier());
        }

        return $query;
    }

    /**
     * Kiem tra role co bi gioi han du lieu theo user hay khong.
     */
    private function isShopScoped(Request $request): bool
    {
        $role = strtolower((string) $request->user()?->vai_tro);

        return in_array($role, ['chu_shop', 'quan_ly_chanh_xe'], true);
    }
}



