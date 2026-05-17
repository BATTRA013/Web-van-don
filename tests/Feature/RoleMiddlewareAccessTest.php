<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/RoleMiddlewareAccessTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST PHAN QUYEN VA MIDDLEWARE
|--------------------------------------------------------------------------
| Kiem tra truy cap route theo role va middleware duyet tai khoan.
*/

namespace Tests\Feature;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin truy cap route quan tri user duoc phep.
     */
    public function test_admin_can_access_user_management_route(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
    }

    /**
     * Chu shop khong duoc truy cap route quan tri user.
     */
    public function test_shop_is_redirected_when_accessing_admin_only_route(): void
    {
        $shop = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => 1,
        ]);

        $response = $this->actingAs($shop)->get(route('users.index'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * Quan ly chanh xe duoc vao module carriers/cod theo phan quyen.
     */
    public function test_manager_can_access_carrier_and_cod_routes(): void
    {
        $manager = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => 1,
        ]);

        $carriersResponse = $this->actingAs($manager)->get(route('carriers.index'));
        $codResponse = $this->actingAs($manager)->get(route('cod.index'));

        $carriersResponse->assertOk();
        $codResponse->assertOk();
    }

    /**
     * Quan ly chanh xe khong duoc truy cap module cau hinh API.
     */
    public function test_manager_is_redirected_when_accessing_api_config_route(): void
    {
        $manager = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => 1,
        ]);

        $response = $this->actingAs($manager)->get(route('api-config.index'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * Tai khoan shop chua duyet bi middleware shop.approved chan va buoc dang xuat.
     */
    public function test_unapproved_shop_is_logged_out_by_shop_approved_middleware(): void
    {
        $shop = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => 0,
        ]);

        $response = $this->actingAs($shop)->get(route('orders.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
    }

    /**
     * Quan ly chanh xe chua duyet cung bi chan boi middleware shop.approved.
     */
    public function test_unapproved_manager_is_logged_out_by_shop_approved_middleware(): void
    {
        $manager = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => 0,
        ]);

        $response = $this->actingAs($manager)->get(route('orders.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
    }
}



