<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/ShipmentMultiCarrierSyncTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST SYNC DA HANG VAN CHUYEN
|--------------------------------------------------------------------------
| Kiem tra dong bo trang thai theo tung hang trong luong generic.
*/

namespace Tests\Feature;

use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use App\Services\Ghn_ShippingService;
use App\Services\ViettelPost_ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentMultiCarrierSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dong bo 1 don Viettel thanh cong se map ve trang thai da_giao.
     */
    public function test_sync_one_viettel_updates_internal_status(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $viettelCarrier = $this->createCarrier('VIETTEL_POST', null, 'vtp_token', 'VTP001');
        $order = $this->createOrder($admin, $viettelCarrier, 'VTP_SYNC_ONE_001');

        $this->mock(ViettelPost_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->once()
                ->andReturnUsing(function (string $trackingCode): array {
                    return [
                        'ok' => $trackingCode === 'VTP_SYNC_ONE_001',
                        'status' => 200,
                        'message' => 'Success',
                        'data' => [
                            'data' => [
                                'ORDER_STATUS' => 'DELIVERED',
                            ],
                        ],
                    ];
                });
        });

        $response = $this->actingAs($admin)
            ->from(route('orders.index'))
            ->post(route('orders.shipments.sync-one', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $order->ma_don_hang,
            'trang_thai' => 'da_giao',
        ]);
    }

    /**
     * Sync-all generic phai dong bo dung theo tung hang va tong ket so luong dung.
     */
    public function test_bulk_sync_generic_handles_ghn_viettel_and_failures(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $ghnCarrier = $this->createCarrier('GHN', null, 'ghn_token', 'GHN001');
        $viettelCarrier = $this->createCarrier('VIETTEL_POST', null, 'vtp_token', 'VTP001');
        $unsupportedCarrier = $this->createCarrier('GHTK', null, 'other_token', 'OTH001');

        $ghnOk = $this->createOrder($admin, $ghnCarrier, 'GHN_GENERIC_OK_001');
        $viettelOk = $this->createOrder($admin, $viettelCarrier, 'VTP_GENERIC_OK_001');
        $viettelFail = $this->createOrder($admin, $viettelCarrier, 'VTP_GENERIC_FAIL_001');
        $otherCarrierOrder = $this->createOrder($admin, $unsupportedCarrier, 'OTHER_GENERIC_001');

        $this->mock(Ghn_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->once()
                ->andReturnUsing(function (string $trackingCode): array {
                    return [
                        'ok' => $trackingCode === 'GHN_GENERIC_OK_001',
                        'status' => 200,
                        'message' => 'Success',
                        'data' => [
                            'data' => [
                                'status' => 'picked',
                            ],
                        ],
                    ];
                });
        });

        $this->mock(ViettelPost_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->twice()
                ->andReturnUsing(function (string $trackingCode): array {
                    if ($trackingCode === 'VTP_GENERIC_OK_001') {
                        return [
                            'ok' => true,
                            'status' => 200,
                            'message' => 'Success',
                            'data' => [
                                'data' => [
                                    'ORDER_STATUS' => 'DELIVERED',
                                ],
                            ],
                        ];
                    }

                    return [
                        'ok' => false,
                        'status' => 503,
                        'message' => 'Viettel gateway timeout',
                        'data' => null,
                    ];
                });
        });

        $response = $this->actingAs($admin)
            ->from(route('orders.index'))
            ->post(route('orders.shipments.sync-all'));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');

        $summary = (string) session('success');
        $this->assertStringContainsString('2 đơn thành công', $summary);
        $this->assertStringContainsString('2 đơn lỗi', $summary);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $ghnOk->ma_don_hang,
            'trang_thai' => 'dang_van_chuyen',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $viettelOk->ma_don_hang,
            'trang_thai' => 'da_giao',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $viettelFail->ma_don_hang,
            'trang_thai' => 'moi',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $otherCarrierOrder->ma_don_hang,
            'trang_thai' => 'moi',
        ]);
    }

    /**
     * Tao nhanh order de phuc vu test sync.
     */
    private function createOrder(Nguoi_Dung $owner, Hang_Van_Chuyen $carrier, string $trackingCode): Order
    {
        return Order::query()->create([
            'ma_nguoi_dung' => $owner->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Test',
            'sdt_nguoi_nhan' => '0901234567',
            'dia_chi_chi_tiet' => '123 Duong Test',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => $trackingCode,
            'trang_thai' => 'moi',
        ]);
    }

    /**
     * Tao nhanh carrier theo ten hang.
     */
    private function createCarrier(string $name, ?int $userId, string $token, string $shopId): Hang_Van_Chuyen
    {
        return Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => $userId,
            'ten_hang' => $name,
            'api_token' => $token,
            'shop_id' => $shopId,
            'moi_truong' => 1,
        ]);
    }
}



