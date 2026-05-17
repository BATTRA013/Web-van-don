<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/GhnOrderSyncTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST DONG BO TRANG THAI GHN
|--------------------------------------------------------------------------
| Kiem tra sync 1 don, sync hang loat, map status, va gioi han quyen shop.
*/

namespace Tests\Feature;

use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use App\Services\Ghn_ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhnOrderSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sync tung don se cap nhat trang thai noi bo theo mapping GHN.
     */
    public function test_sync_one_updates_order_status_using_mapped_ghn_status(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $ghnCarrier = $this->createCarrier('GHN', null, 'sync_token', '1001');
        $order = $this->createOrder($admin, $ghnCarrier, 'GHN_SYNC_ONE_001');

        $this->mock(Ghn_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'status' => 200,
                    'message' => 'Success',
                    'data' => [
                        'data' => [
                            'status' => 'delivered',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($admin)
            ->from(route('orders.index'))
            ->post(route('orders.ghn.sync-one', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $order->ma_don_hang,
            'trang_thai' => 'da_giao',
        ]);
    }

    /**
     * Don khong co tracking thi bi chan sync va tra message ro rang.
     */
    public function test_sync_one_returns_error_when_order_has_no_tracking_code(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $ghnCarrier = $this->createCarrier('GHN', null, 'sync_token', '1002');
        $order = $this->createOrder($admin, $ghnCarrier, '');

        $this->mock(Ghn_ShippingService::class, function ($mock): void {
            $mock->shouldNotReceive('trackShipment');
        });

        $response = $this->actingAs($admin)
            ->from(route('orders.index'))
            ->post(route('orders.ghn.sync-one', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('chưa có mã tracking', session('error'));
    }

    /**
     * Chu shop khong duoc sync don thuoc shop khac.
     */
    public function test_shop_cannot_sync_order_of_another_shop(): void
    {
        $shopA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $shopB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $ghnCarrier = $this->createCarrier('GHN', null, 'sync_token', '1003');
        $orderOfShopA = $this->createOrder($shopA, $ghnCarrier, 'GHN_SHOP_A_001');

        $response = $this->actingAs($shopB)
            ->post(route('orders.ghn.sync-one', $orderOfShopA));

        $response->assertForbidden();
    }

    /**
     * Sync hang loat chi dong bo don GHN co tracking va tong ket dung so luong.
     */
    public function test_bulk_sync_updates_only_ghn_orders_and_reports_success_failure_counts(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $ghnCarrier = $this->createCarrier('GHN', null, 'bulk_token', '2001');
        $otherCarrier = $this->createCarrier('GHTK', null, 'other_token', '2002');

        $orderSuccess = $this->createOrder($admin, $ghnCarrier, 'GHN_BULK_OK_001');
        $orderFailed = $this->createOrder($admin, $ghnCarrier, 'GHN_BULK_FAIL_001');
        $orderNoTracking = $this->createOrder($admin, $ghnCarrier, '');
        $orderOtherCarrier = $this->createOrder($admin, $otherCarrier, 'OTHER_BULK_001');

        $this->mock(Ghn_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->twice()
                ->andReturnUsing(function (string $trackingCode): array {
                    if ($trackingCode === 'GHN_BULK_OK_001') {
                        return [
                            'ok' => true,
                            'status' => 200,
                            'message' => 'Success',
                            'data' => [
                                'data' => [
                                    'status' => 'transporting',
                                ],
                            ],
                        ];
                    }

                    return [
                        'ok' => false,
                        'status' => 503,
                        'message' => 'Gateway timeout',
                        'data' => null,
                    ];
                });
        });

        $response = $this->actingAs($admin)
            ->from(route('orders.index'))
            ->post(route('orders.ghn.sync-all'));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');

        $summary = (string) session('success');
        $this->assertStringContainsString('1 đơn thành công', $summary);
        $this->assertStringContainsString('2 đơn lỗi', $summary);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $orderSuccess->ma_don_hang,
            'trang_thai' => 'dang_van_chuyen',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $orderFailed->ma_don_hang,
            'trang_thai' => 'moi',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $orderNoTracking->ma_don_hang,
            'trang_thai' => 'moi',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $orderOtherCarrier->ma_don_hang,
            'trang_thai' => 'moi',
        ]);
    }

    /**
     * Shop sync hang loat chi anh huong don cua chinh shop do.
     */
    public function test_shop_bulk_sync_only_updates_its_own_orders(): void
    {
        $shopA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $shopB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $ghnCarrier = $this->createCarrier('GHN', null, 'shop_bulk_token', '3001');
        $orderOfShopA = $this->createOrder($shopA, $ghnCarrier, 'GHN_SHOP_A_BULK_001');
        $orderOfShopB = $this->createOrder($shopB, $ghnCarrier, 'GHN_SHOP_B_BULK_001');

        $this->mock(Ghn_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('trackShipment')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'status' => 200,
                    'message' => 'Success',
                    'data' => [
                        'data' => [
                            'status' => 'ready_to_pick',
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($shopA)
            ->from(route('orders.index'))
            ->post(route('orders.ghn.sync-all'));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $orderOfShopA->ma_don_hang,
            'trang_thai' => 'cho_lay_hang',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $orderOfShopB->ma_don_hang,
            'trang_thai' => 'moi',
        ]);
    }

    /**
     * Tao nhanh order phuc vu cac test sync.
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
     * Tao nhanh carrier theo ten hang de loc trong sync-all.
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



