<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/CodAutoReconcileCommandTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

namespace Tests\Feature;

use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use App\Services\Carrier_GatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodAutoReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_reconciliation_with_auto_status_when_carrier_returns_cod_amount(): void
    {
        [$order] = $this->seedDeliveredOrder(100000, 'AUTO_COD_001');

        $this->mock(Carrier_GatewayService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'status' => 200,
                    'data' => [
                        'data' => [
                            'cod_amount_collect' => 110000,
                        ],
                    ],
                ]);
        });

        $this->artisan('cod:auto-reconcile --limit=50')
            ->assertExitCode(0);

        $this->assertDatabaseHas('doi_soat_cod', [
            'ma_don_hang' => $order->ma_don_hang,
            'cod_ky_vong' => 100000,
            'cod_thuc_nhan' => 110000,
            'chenhlech' => 10000,
            'trang_thai' => 'du',
        ]);
    }

    public function test_command_marks_pending_when_carrier_response_has_no_received_cod_field(): void
    {
        [$order] = $this->seedDeliveredOrder(250000, 'AUTO_COD_002');

        $this->mock(Carrier_GatewayService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'status' => 200,
                    'data' => [
                        'data' => [
                            'ORDER_STATUS' => 'DELIVERED',
                        ],
                    ],
                ]);
        });

        $this->artisan('cod:auto-reconcile --limit=50')
            ->assertExitCode(0);

        $this->assertDatabaseHas('doi_soat_cod', [
            'ma_don_hang' => $order->ma_don_hang,
            'cod_ky_vong' => 250000,
            'cod_thuc_nhan' => 250000,
            'chenhlech' => 0,
            'trang_thai' => 'cho_xac_nhan',
        ]);
    }

    /**
     * @return array{0: Order, 1: Hang_Van_Chuyen, 2: Nguoi_Dung}
     */
    private function seedDeliveredOrder(float $codValue, string $trackingCode): array
    {
        $owner = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $carrier = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'ghn_token_test',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $owner->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Auto COD',
            'sdt_nguoi_nhan' => '0901001001',
            'dia_chi_chi_tiet' => 'Dia chi auto cod',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => $codValue,
            'ma_tracking' => $trackingCode,
            'trang_thai' => 'da_giao',
        ]);

        return [$order, $carrier, $owner];
    }
}



