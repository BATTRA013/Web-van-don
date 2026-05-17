<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/GhnOrderCreationTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST TAO VAN DON GHN
|--------------------------------------------------------------------------
| Kiem tra message loi ro rang va tranh phat sinh du lieu fallback rac.
*/

namespace Tests\Feature;

use App\Models\Nguoi_Dung;
use App\Services\Ghn_ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhnOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin se nhan loi ro rang neu thieu token/shop_id GHN.
     */
    public function test_admin_gets_clear_error_when_ghn_credentials_are_missing(): void
    {
        config([
            'services.ghn.token' => null,
            'services.ghn.shop_id' => null,
        ]);

        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('orders.ghn.create'))
            ->post(route('orders.ghn.store'), $this->validGhnPayload([
                'token' => null,
                'shop_id' => null,
            ]));

        $response->assertRedirect(route('orders.ghn.create'));
        $response->assertSessionHasErrors(['ghn']);
        $this->assertStringContainsString('token', session('errors')->first('ghn'));
        $this->assertStringContainsString('shop_id', session('errors')->first('ghn'));
    }

    /**
     * Neu thieu thong tin nguoi gui thi message loi phai chi ra cac truong thieu.
     */
    public function test_sender_validation_message_lists_missing_fields(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('orders.ghn.create'))
            ->post(route('orders.ghn.store'), $this->validGhnPayload([
                'token' => 'override_token',
                'shop_id' => 98765,
                'sender_name' => '',
                'sender_phone' => '',
                'sender_address' => '',
                'from_district_id' => null,
                'from_ward_code' => '',
            ]));

        $response->assertRedirect(route('orders.ghn.create'));
        $response->assertSessionHasErrors(['ghn']);

        $message = session('errors')->first('ghn');
        $this->assertStringContainsString('ten_nguoi_gui', $message);
        $this->assertStringContainsString('sdt_nguoi_gui', $message);
        $this->assertStringContainsString('from_district_id', $message);
    }

    /**
     * Tao GHN bang credential override se luu carrier thuc te, khong tao pending_token.
     */
    public function test_successful_ghn_creation_with_override_does_not_create_pending_token_carrier(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $this->mock(Ghn_ShippingService::class, function ($mock): void {
            $mock->shouldReceive('getDefaultCredentials')
                ->andReturn([
                    'token' => null,
                    'shop_id' => null,
                ]);

            $mock->shouldReceive('isValidWardForDistrict')
                ->twice()
                ->andReturn(true);

            $mock->shouldReceive('createShipment')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'status' => 200,
                    'message' => 'Success',
                    'data' => [
                        'data' => [
                            'order_code' => 'GHN_ORDER_0001',
                            'total_fee' => 25000,
                        ],
                    ],
                ]);
        });

        $response = $this->actingAs($admin)
            ->from(route('orders.ghn.create'))
            ->post(route('orders.ghn.store'), $this->validGhnPayload([
                'token' => 'override_token_real',
                'shop_id' => 54321,
            ]));

        $response->assertRedirect(route('orders.ghn.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hang_van_chuyen', [
            'ten_hang' => 'GHN',
            'ma_nguoi_dung' => null,
            'api_token' => 'override_token_real',
            'shop_id' => '54321',
        ]);

        $this->assertDatabaseMissing('hang_van_chuyen', [
            'api_token' => 'pending_token',
        ]);

        $this->assertDatabaseHas('don_hang', [
            'ma_tracking' => 'GHN_ORDER_0001',
            'ten_nguoi_nhan' => 'Nguoi Nhan GHN',
        ]);
    }

    /**
     * Payload GHN hop le dung chung cho cac test.
     */
    private function validGhnPayload(array $overrides = []): array
    {
        return array_merge([
            'payment_type_id' => 1,
            'required_note' => 'KHONGCHOXEMHANG',
            'sender_name' => 'Kho Quan 10',
            'sender_phone' => '0901234567',
            'sender_address' => '72 Thanh Thai',
            'from_district_id' => 760,
            'from_ward_code' => '20208',
            'return_phone' => '0901234567',
            'return_address' => '72 Thanh Thai',
            'return_district_id' => 760,
            'return_ward_code' => '20208',
            'receiver_name' => 'Nguoi Nhan GHN',
            'receiver_phone' => '0912345678',
            'receiver_address' => '123 Duong ABC',
            'to_district_id' => 760,
            'to_ward_code' => '20208',
            'item_name' => 'My pham',
            'item_weight' => 1000,
            'item_quantity' => 1,
            'item_price' => 150000,
            'cod_value' => 150000,
            'length' => 20,
            'width' => 20,
            'height' => 20,
            'service_type_id' => 2,
            'note' => 'Test tao don GHN',
            'token' => null,
            'shop_id' => null,
        ], $overrides);
    }
}



