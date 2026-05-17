<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/DashboardKpiTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST KPI DASHBOARD
|--------------------------------------------------------------------------
| Kiem tra dashboard hien thi KPI dung theo pham vi quyen user.
*/

namespace Tests\Feature;

use App\Models\Cod_Reconciliation;
use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardKpiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin nhin duoc tong KPI toan he thong.
     */
    public function test_admin_can_view_system_wide_kpi(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $shopA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $shopB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $carrier = $this->createCarrier();

        $orderA = $this->createOrder($shopA, $carrier, 'da_giao', 'DASH-ADMIN-001');
        $orderB = $this->createOrder($shopB, $carrier, 'dang_van_chuyen', 'DASH-ADMIN-002');

        Cod_Reconciliation::query()->create([
            'ma_don_hang' => $orderA->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 100000,
            'cod_thuc_nhan' => 100000,
            'chenhlech' => 0,
            'trang_thai' => 'cho_doi_soat',
        ]);

        Cod_Reconciliation::query()->create([
            'ma_don_hang' => $orderB->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 200000,
            'cod_thuc_nhan' => 0,
            'chenhlech' => 200000,
            'trang_thai' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpis', function (array $kpis): bool {
            return (int) ($kpis['total_orders'] ?? 0) === 2
                && (int) ($kpis['in_transit_orders'] ?? 0) === 1
                && (int) ($kpis['cod_pending'] ?? 0) === 2
                && (float) ($kpis['delivery_rate'] ?? 0.0) === 100.0;
        });
    }

    /**
     * Chu shop chi nhin thay KPI cua chinh minh.
     */
    public function test_shop_only_sees_its_own_kpi_scope(): void
    {
        $shopA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $shopB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $carrier = $this->createCarrier();

        $orderOfShopA = $this->createOrder($shopA, $carrier, 'dang_van_chuyen', 'DASH-SHOPA-001');
        $this->createOrder($shopB, $carrier, 'da_giao', 'DASH-SHOPB-001');

        Cod_Reconciliation::query()->create([
            'ma_don_hang' => $orderOfShopA->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 300000,
            'cod_thuc_nhan' => 0,
            'chenhlech' => 300000,
            'trang_thai' => 'cho_doi_soat',
        ]);

        $response = $this->actingAs($shopA)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpis', function (array $kpis): bool {
            return (int) ($kpis['total_orders'] ?? 0) === 1
                && (int) ($kpis['in_transit_orders'] ?? 0) === 1
                && (int) ($kpis['cod_pending'] ?? 0) === 1
                && (float) ($kpis['delivery_rate'] ?? 0.0) === 0.0;
        });
    }

    private function createCarrier(): Hang_Van_Chuyen
    {
        return Hang_Van_Chuyen::query()->create([
            'ten_hang' => 'GHN',
            'api_token' => 'dashboard-token',
            'shop_id' => 'DASH001',
            'moi_truong' => 1,
        ]);
    }

    private function createOrder(Nguoi_Dung $owner, Hang_Van_Chuyen $carrier, string $status, string $trackingCode): Order
    {
        return Order::query()->create([
            'ma_nguoi_dung' => $owner->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Dashboard',
            'sdt_nguoi_nhan' => '0901234567',
            'dia_chi_chi_tiet' => '123 Dashboard Street',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 100000,
            'ma_tracking' => $trackingCode,
            'trang_thai' => $status,
        ]);
    }
}



