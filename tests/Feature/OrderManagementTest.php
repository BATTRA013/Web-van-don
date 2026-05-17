<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/OrderManagementTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST CRUD DON HANG NOI BO
|--------------------------------------------------------------------------
| Kiem tra luong tao don va bao ve quyen truy cap don hang.
*/

namespace Tests\Feature;

use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use App\Models\Order_Detail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin co the tao don noi bo va sinh dong chi tiet dau tien.
     */
    public function test_admin_can_create_internal_order_with_first_detail(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'test_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('orders.store'), $this->validPayload([
            'receiver_phone' => '0901 234 567',
        ]));

        $response->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('don_hang', [
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ten_nguoi_nhan' => 'Nguyen Van A',
            'sdt_nguoi_nhan' => '0901234567',
        ]);

        $order = Order::query()->where('ten_nguoi_nhan', 'Nguyen Van A')->first();

        $this->assertNotNull($order);
        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'ma_don_hang' => $order->ma_don_hang,
            'ten_san_pham' => 'My pham',
        ]);
    }

    /**
     * Du lieu so dien thoai sai dinh dang bi chan tai validate.
     */
    public function test_order_creation_rejects_invalid_receiver_phone(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $response = $this->actingAs($admin)->from(route('orders.create'))->post(route('orders.store'), $this->validPayload([
            'receiver_phone' => 'abc-xyz',
        ]));

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors(['receiver_phone']);

        $this->assertDatabaseMissing('don_hang', [
            'ten_nguoi_nhan' => 'Nguyen Van A',
        ]);
    }

    /**
     * Chu shop khong duoc sua don cua shop khac.
     */
    public function test_shop_cannot_update_order_of_another_shop(): void
    {
        $shopA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $shopB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $carrier = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'test_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $shopA->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Cu',
            'sdt_nguoi_nhan' => '0900000000',
            'dia_chi_chi_tiet' => 'Dia chi cu',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDTEST0001',
            'trang_thai' => 'moi',
        ]);

        $response = $this->actingAs($shopB)->put(route('orders.update', $order), $this->validPayload());

        $response->assertForbidden();
    }

    /**
     * Admin cap nhat don se cap nhat luon dong chi tiet dau tien.
     */
    public function test_admin_can_update_order_and_first_detail(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $carrier = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'test_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Cu',
            'sdt_nguoi_nhan' => '0900000000',
            'dia_chi_chi_tiet' => 'Dia chi cu',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDTEST0002',
            'trang_thai' => 'moi',
        ]);

        $detail = Order_Detail::query()->create([
            'ma_don_hang' => $order->ma_don_hang,
            'ten_san_pham' => 'San pham cu',
            'so_luong' => 1,
            'gia_ban' => 10000,
            'khoi_luong_sp' => 500,
        ]);

        $payload = $this->validPayload([
            'receiver_name' => 'Nguoi Nhan Moi',
            'item_name' => 'San pham moi',
            'item_quantity' => 3,
            'item_price' => 250000,
            'item_weight' => 1800,
        ]);

        $response = $this->actingAs($admin)->put(route('orders.update', $order), $payload);

        $response->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('don_hang', [
            'ma_don_hang' => $order->ma_don_hang,
            'ten_nguoi_nhan' => 'Nguoi Nhan Moi',
            'trong_luong' => 1800,
        ]);

        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'id' => $detail->id,
            'ten_san_pham' => 'San pham moi',
            'so_luong' => 3,
        ]);
    }

    /**
     * Admin co the xoa don, keo theo chi tiet don bi xoa.
     */
    public function test_admin_can_delete_order_and_cascade_details(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $carrier = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'test_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Xoa',
            'sdt_nguoi_nhan' => '0900000001',
            'dia_chi_chi_tiet' => 'Dia chi xoa',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDTEST0003',
            'trang_thai' => 'moi',
        ]);

        $detail = Order_Detail::query()->create([
            'ma_don_hang' => $order->ma_don_hang,
            'ten_san_pham' => 'San pham xoa',
            'so_luong' => 1,
            'gia_ban' => 10000,
            'khoi_luong_sp' => 500,
        ]);

        $response = $this->actingAs($admin)->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $this->assertDatabaseMissing('don_hang', [
            'ma_don_hang' => $order->ma_don_hang,
        ]);
        $this->assertDatabaseMissing('chi_tiet_don_hang', [
            'id' => $detail->id,
        ]);
    }

    /**
     * Chu shop khong duoc xoa don cua shop khac.
     */
    public function test_shop_cannot_delete_order_of_another_shop(): void
    {
        $shopA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $shopB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
        ]);

        $carrier = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'test_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $shopA->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan Shop A',
            'sdt_nguoi_nhan' => '0900000002',
            'dia_chi_chi_tiet' => 'Dia chi A',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDTEST0004',
            'trang_thai' => 'moi',
        ]);

        $response = $this->actingAs($shopB)->delete(route('orders.destroy', $order));

        $response->assertForbidden();
    }

    /**
     * Payload hop le dung lai cho nhieu case test.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'receiver_name' => 'Nguyen Van A',
            'receiver_phone' => '0901234567',
            'receiver_address' => '123 Duong ABC',
            'to_province_id' => 79,
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
        ], $overrides);
    }
}



