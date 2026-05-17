<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/TransportManagerOrderScopeTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

namespace Tests\Feature;

use App\Models\External_Route_Bill;
use App\Models\Hang_Van_Chuyen;
use App\Models\Nha_Xe;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportManagerOrderScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_manager_only_sees_assigned_orders_in_index(): void
    {
        [$manager, $managerCarrier] = $this->seedTransportManagerWithCarrier('Chanh Xe A', '0901001001');

        [$shopOrderAssigned, $shopOrderNotAssigned] = $this->seedShopOrders();

        External_Route_Bill::query()->create([
            'ma_don_hang' => $shopOrderAssigned->ma_don_hang,
            'ma_nha_xe' => $managerCarrier->ma_nha_xe,
            'ma_bien_lai' => 'BL-TM-001',
            'anh_chup_bien_lai' => null,
        ]);

        $response = $this->actingAs($manager)->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee((string) $shopOrderAssigned->ma_tracking);
        $response->assertDontSee((string) $shopOrderNotAssigned->ma_tracking);
    }

    public function test_transport_manager_cannot_open_unassigned_order_detail(): void
    {
        [$manager] = $this->seedTransportManagerWithCarrier('Chanh Xe A', '0901001001');

        [, $shopOrderNotAssigned] = $this->seedShopOrders();

        $response = $this->actingAs($manager)->get(route('orders.show', $shopOrderNotAssigned));

        $response->assertForbidden();
    }

    public function test_transport_manager_cannot_access_order_creation_route(): void
    {
        [$manager] = $this->seedTransportManagerWithCarrier('Chanh Xe A', '0901001001');

        $response = $this->actingAs($manager)->get(route('orders.create'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * @return array{0: Nguoi_Dung, 1: Nha_Xe}
     */
    private function seedTransportManagerWithCarrier(string $unitName, string $phone): array
    {
        $manager = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => $unitName,
            'so_dien_thoai' => $phone,
        ]);

        $carrier = Nha_Xe::query()->create([
            'ten_nha_xe' => $unitName,
            'so_dien_thoai' => $phone,
            'tuyen_duong' => 'A-B',
        ]);

        return [$manager, $carrier];
    }

    /**
     * @return array{0: Order, 1: Order}
     */
    private function seedShopOrders(): array
    {
        $shop = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $carrierConfig = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ten_hang' => 'GHN',
            'api_token' => 'token-test',
            'shop_id' => '10001',
            'moi_truong' => 1,
        ]);

        $assigned = Order::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrierConfig->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan A',
            'sdt_nguoi_nhan' => '0902002001',
            'dia_chi_chi_tiet' => 'Dia chi A',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 120000,
            'ma_tracking' => 'TM-ASSIGNED-001',
            'trang_thai' => 'dang_van_chuyen',
        ]);

        $notAssigned = Order::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrierConfig->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan B',
            'sdt_nguoi_nhan' => '0902002002',
            'dia_chi_chi_tiet' => 'Dia chi B',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 150000,
            'ma_tracking' => 'TM-NOT-ASSIGNED-001',
            'trang_thai' => 'dang_van_chuyen',
        ]);

        return [$assigned, $notAssigned];
    }
}



