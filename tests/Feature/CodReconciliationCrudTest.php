<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/CodReconciliationCrudTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST CRUD DOI SOAT COD
|--------------------------------------------------------------------------
| Kiem tra tao/sua/xoa doi soat COD va tinh dung truong chenh lech.
*/

namespace Tests\Feature;

use App\Models\Cod_Reconciliation;
use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodReconciliationCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin tao doi soat COD thanh cong va chenh lech duoc tinh dung.
     */
    public function test_admin_can_create_cod_reconciliation_and_compute_difference(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        [$order, $carrier] = $this->seedOrderAndCarrier($admin);

        $payload = [
            'ma_don_hang' => $order->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 200000,
            'cod_thuc_nhan' => 185000,
            'ngay_doi_soat' => '2026-03-29 09:00:00',
            'trang_thai' => 'lech',
        ];

        $response = $this->actingAs($admin)->post(route('cod.store'), $payload);

        $response->assertRedirect(route('cod.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('doi_soat_cod', [
            'ma_don_hang' => $order->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 200000,
            'cod_thuc_nhan' => 185000,
            'chenhlech' => -15000,
            'trang_thai' => 'lech',
        ]);
    }

    /**
     * Cap nhat doi soat COD se tinh lai chenh lech theo gia tri moi.
     */
    public function test_admin_can_update_cod_reconciliation_and_recompute_difference(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        [$order, $carrier] = $this->seedOrderAndCarrier($admin);

        $item = Cod_Reconciliation::query()->create([
            'ma_don_hang' => $order->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 300000,
            'cod_thuc_nhan' => 300000,
            'chenhlech' => 0,
            'ngay_doi_soat' => now(),
            'trang_thai' => 'khop',
        ]);

        $payload = [
            'ma_don_hang' => $order->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 300000,
            'cod_thuc_nhan' => 320000,
            'ngay_doi_soat' => '2026-03-29 10:00:00',
            'trang_thai' => 'du',
        ];

        $response = $this->actingAs($admin)->put(route('cod.update', $item), $payload);

        $response->assertRedirect(route('cod.show', $item));
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('20000.00', (string) $item->chenhlech);
        $this->assertSame('du', $item->trang_thai);
    }

    /**
     * Admin co the xoa ban ghi doi soat COD.
     */
    public function test_admin_can_delete_cod_reconciliation(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        [$order, $carrier] = $this->seedOrderAndCarrier($admin);

        $item = Cod_Reconciliation::query()->create([
            'ma_don_hang' => $order->ma_don_hang,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'cod_ky_vong' => 120000,
            'cod_thuc_nhan' => 120000,
            'chenhlech' => 0,
            'ngay_doi_soat' => now(),
            'trang_thai' => 'khop',
        ]);

        $response = $this->actingAs($admin)->delete(route('cod.destroy', $item));

        $response->assertRedirect(route('cod.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('doi_soat_cod', [
            'ma_doi_soat' => $item->ma_doi_soat,
        ]);
    }

    /**
     * Validate chan cod_thuc_nhan am.
     */
    public function test_create_cod_reconciliation_rejects_negative_received_cod(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        [$order, $carrier] = $this->seedOrderAndCarrier($admin);

        $response = $this->actingAs($admin)
            ->from(route('cod.create'))
            ->post(route('cod.store'), [
                'ma_don_hang' => $order->ma_don_hang,
                'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
                'cod_ky_vong' => 100000,
                'cod_thuc_nhan' => -1000,
                'trang_thai' => 'lech',
            ]);

        $response->assertRedirect(route('cod.create'));
        $response->assertSessionHasErrors(['cod_thuc_nhan']);
    }

    /**
     * Tao du lieu don + hang van chuyen toi thieu de test doi soat COD.
     *
     * @return array{0: Order, 1: Hang_Van_Chuyen}
     */
    private function seedOrderAndCarrier(Nguoi_Dung $owner): array
    {
        $carrier = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'test_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $owner->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan COD',
            'sdt_nguoi_nhan' => '0900000099',
            'dia_chi_chi_tiet' => 'Dia chi test COD',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 120000,
            'ma_tracking' => 'VDCOD'.random_int(10000, 99999),
            'trang_thai' => 'moi',
        ]);

        return [$order, $carrier];
    }
}



