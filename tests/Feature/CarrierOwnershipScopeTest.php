<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/CarrierOwnershipScopeTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

namespace Tests\Feature;

use App\Models\Nha_Xe;
use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierOwnershipScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_only_sees_their_own_linked_carrier_in_index(): void
    {
        $managerA = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => 'Chanh Xe A',
            'so_dien_thoai' => '0901001001',
        ]);

        Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => 'Chanh Xe B',
            'so_dien_thoai' => '0901001002',
        ]);

        Nha_Xe::query()->create([
            'ten_nha_xe' => 'Chanh Xe A',
            'so_dien_thoai' => '0901001001',
            'tuyen_duong' => 'A-B',
        ]);

        Nha_Xe::query()->create([
            'ten_nha_xe' => 'Chanh Xe B',
            'so_dien_thoai' => '0901001002',
            'tuyen_duong' => 'B-C',
        ]);

        $response = $this->actingAs($managerA)->get(route('carriers.index'));

        $response->assertOk();
        $response->assertSee('Chanh Xe A');
        $response->assertDontSee('Chanh Xe B');
    }

    public function test_manager_cannot_open_other_manager_carrier_detail(): void
    {
        Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => 'Chanh Xe A',
            'so_dien_thoai' => '0901001001',
        ]);

        $managerB = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ten_don_vi' => 'Chanh Xe B',
            'so_dien_thoai' => '0901001002',
        ]);

        $carrierA = Nha_Xe::query()->create([
            'ten_nha_xe' => 'Chanh Xe A',
            'so_dien_thoai' => '0901001001',
            'tuyen_duong' => 'A-B',
        ]);

        $response = $this->actingAs($managerB)->get(route('carriers.show', $carrierA));

        $response->assertForbidden();
    }
}



