<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/ExternalRouteBillWorkflowTest.php
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

class ExternalRouteBillWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_sends_request_and_manager_can_accept_and_submit_receipt(): void
    {
        $shop = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $manager = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => 'Chanh Xe A',
            'so_dien_thoai' => '0903003003',
        ]);

        $nhaXe = Nha_Xe::query()->create([
            'ten_nha_xe' => 'Chanh Xe A',
            'so_dien_thoai' => '0903003003',
            'tuyen_duong' => 'Da Nang - HCM',
        ]);

        $carrierConfig = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ten_hang' => 'GHN',
            'api_token' => 'ghn_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrierConfig->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan 1',
            'sdt_nguoi_nhan' => '0904004004',
            'dia_chi_chi_tiet' => 'Dia chi 1',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 450000,
            'ma_tracking' => 'FLOW-BILL-001',
            'trang_thai' => 'dang_van_chuyen',
        ]);

        $sendResponse = $this->actingAs($shop)->post(route('orders.external-route-bills.store', $order), [
            'ma_nha_xe' => $nhaXe->ma_nha_xe,
        ]);

        $sendResponse->assertRedirect(route('orders.show', $order));

        $bill = External_Route_Bill::query()->where('ma_don_hang', $order->ma_don_hang)->firstOrFail();
        $this->assertSame('cho_nhan', (string) $bill->trang_thai);
        $this->assertStringStartsWith('YC-', (string) $bill->ma_bien_lai);

        $acceptResponse = $this->actingAs($manager)->post(route('orders.external-route-bills.accept', [
            'order' => $order,
            'bill' => $bill,
        ]));

        $acceptResponse->assertRedirect(route('orders.show', $order));

        $bill->refresh();
        $this->assertSame('da_nhan', (string) $bill->trang_thai);

        $receiptResponse = $this->actingAs($manager)->put(route('orders.external-route-bills.receipt', [
            'order' => $order,
            'bill' => $bill,
        ]), [
            'ma_bien_lai' => 'BL-REAL-0001',
            'anh_chup_bien_lai' => 'https://example.com/receipt-1.jpg',
        ]);

        $receiptResponse->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('van_don_ngoai_tuyen', [
            'ma_van_don_ngoai_tuyen' => $bill->ma_van_don_ngoai_tuyen,
            'ma_bien_lai' => 'BL-REAL-0001',
            'trang_thai' => 'da_gui_bien_lai',
        ]);
    }

    public function test_manager_cannot_approve_bill_not_assigned_to_their_carrier(): void
    {
        $shop = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $manager = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => 'Chanh Xe B',
            'so_dien_thoai' => '0905005005',
        ]);

        $nhaXeA = Nha_Xe::query()->create([
            'ten_nha_xe' => 'Chanh Xe A',
            'so_dien_thoai' => '0906006006',
            'tuyen_duong' => 'A-B',
        ]);

        $carrierConfig = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ten_hang' => 'GHN',
            'api_token' => 'ghn_token',
            'shop_id' => '12345',
            'moi_truong' => 1,
        ]);

        $order = Order::query()->create([
            'ma_nguoi_dung' => $shop->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $carrierConfig->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Nguoi Nhan 2',
            'sdt_nguoi_nhan' => '0907007007',
            'dia_chi_chi_tiet' => 'Dia chi 2',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 220000,
            'ma_tracking' => 'FLOW-BILL-002',
            'trang_thai' => 'dang_van_chuyen',
        ]);

        $bill = External_Route_Bill::query()->create([
            'ma_don_hang' => $order->ma_don_hang,
            'ma_nha_xe' => $nhaXeA->ma_nha_xe,
            'ma_bien_lai' => 'YC-2-20260413',
            'anh_chup_bien_lai' => null,
            'trang_thai' => 'cho_nhan',
        ]);

        $response = $this->actingAs($manager)->post(route('orders.external-route-bills.accept', [
            'order' => $order,
            'bill' => $bill,
        ]));

        $response->assertForbidden();
    }
}



