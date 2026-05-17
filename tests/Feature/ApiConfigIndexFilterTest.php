<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/ApiConfigIndexFilterTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

namespace Tests\Feature;

use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiConfigIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_used_only_returns_referenced_configs(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $ghn = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'ghn-token',
            'shop_id' => '199363',
            'moi_truong' => 0,
        ]);

        $viettel = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'VIETTEL_POST',
            'api_token' => 'vtp-token',
            'shop_id' => '18088142',
            'moi_truong' => 0,
        ]);

        Order::query()->create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $ghn->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Filter Target',
            'sdt_nguoi_nhan' => '0901000000',
            'dia_chi_chi_tiet' => 'Dia chi test',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1200,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDFILTER'.random_int(10000, 99999),
            'trang_thai' => 'moi',
        ]);

        $response = $this->actingAs($admin)->get(route('api-config.index', [
            'usage' => 'used',
        ]));

        $response->assertOk();
        $response->assertViewHas('carriers', function ($carriers) use ($ghn, $viettel) {
            return $carriers->count() === 1
                && (int) $carriers->first()->ma_hang_van_chuyen === (int) $ghn->ma_hang_van_chuyen
                && ! $carriers->contains('ma_hang_van_chuyen', $viettel->ma_hang_van_chuyen);
        });
    }

    public function test_filter_by_carrier_and_unused_returns_expected_records(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'ghn-token',
            'shop_id' => '199363',
            'moi_truong' => 0,
        ]);

        $viettelUnused = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'VIETTEL_POST',
            'api_token' => 'vtp-token',
            'shop_id' => '18088142',
            'moi_truong' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('api-config.index', [
            'carrier' => 'VIETTEL_POST',
            'usage' => 'unused',
        ]));

        $response->assertOk();
        $response->assertViewHas('carriers', function ($carriers) use ($viettelUnused) {
            return $carriers->count() === 1
                && (int) $carriers->first()->ma_hang_van_chuyen === (int) $viettelUnused->ma_hang_van_chuyen;
        });
    }

    public function test_sort_by_impact_returns_highest_reference_first(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $lowImpact = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'low-token',
            'shop_id' => '111',
            'moi_truong' => 0,
        ]);

        $highImpact = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'VIETTEL_POST',
            'api_token' => 'high-token',
            'shop_id' => '222',
            'moi_truong' => 0,
        ]);

        Order::query()->create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $lowImpact->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Low Impact',
            'sdt_nguoi_nhan' => '0901000011',
            'dia_chi_chi_tiet' => 'Dia chi test 1',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1000,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDLOW'.random_int(10000, 99999),
            'trang_thai' => 'moi',
        ]);

        Order::query()->create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $highImpact->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'High Impact 1',
            'sdt_nguoi_nhan' => '0901000021',
            'dia_chi_chi_tiet' => 'Dia chi test 2',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1100,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDHIGH'.random_int(10000, 99999),
            'trang_thai' => 'moi',
        ]);

        Order::query()->create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $highImpact->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'High Impact 2',
            'sdt_nguoi_nhan' => '0901000022',
            'dia_chi_chi_tiet' => 'Dia chi test 3',
            'ma_tinh_thanh' => 79,
            'ma_quan_huyen' => 760,
            'ma_phuong_xa' => '20208',
            'trong_luong' => 1200,
            'chieu_dai' => 20,
            'chieu_rong' => 20,
            'chieu_cao' => 20,
            'tien_cod' => 0,
            'ma_tracking' => 'VDHIGHX'.random_int(10000, 99999),
            'trang_thai' => 'moi',
        ]);

        $response = $this->actingAs($admin)->get(route('api-config.index', [
            'sort' => 'impact',
        ]));

        $response->assertOk();
        $response->assertViewHas('carriers', function ($carriers) use ($highImpact, $lowImpact) {
            return $carriers->count() >= 2
                && (int) $carriers->first()->ma_hang_van_chuyen === (int) $highImpact->ma_hang_van_chuyen
                && (int) $carriers->last()->ma_hang_van_chuyen === (int) $lowImpact->ma_hang_van_chuyen;
        });
    }
}



