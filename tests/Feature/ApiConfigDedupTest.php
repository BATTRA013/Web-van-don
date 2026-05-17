<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/ApiConfigDedupTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

namespace Tests\Feature;

use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiConfigDedupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_store_upserts_existing_shared_carrier(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $existing = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'VIETTEL_POST',
            'api_token' => 'old-token',
            'shop_id' => '111',
            'moi_truong' => 0,
        ]);

        $response = $this->actingAs($admin)->post(route('api-config.store'), [
            'ten_hang' => 'Viettel Post',
            'api_token' => 'new-token',
            'shop_id' => '18088142',
            'moi_truong' => 0,
        ]);

        $response->assertRedirect(route('api-config.index'));
        $response->assertSessionHas('success');

        $this->assertSame(1, Hang_Van_Chuyen::query()->where('ten_hang', 'VIETTEL_POST')->whereNull('ma_nguoi_dung')->count());

        $existing->refresh();
        $this->assertSame('new-token', $existing->api_token);
        $this->assertSame('18088142', $existing->shop_id);
    }

    public function test_update_blocks_duplicate_carrier_name_for_same_owner(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $keep = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'VIETTEL_POST',
            'api_token' => 'token-a',
            'shop_id' => '18088142',
            'moi_truong' => 0,
        ]);

        $target = Hang_Van_Chuyen::query()->create([
            'ma_nguoi_dung' => null,
            'ten_hang' => 'GHN',
            'api_token' => 'token-b',
            'shop_id' => '199363',
            'moi_truong' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('api-config.edit', $target))
            ->put(route('api-config.update', $target), [
                'ten_hang' => 'Viettel Post',
                'api_token' => 'token-c',
                'shop_id' => '20000000',
                'moi_truong' => 0,
            ]);

        $response->assertRedirect(route('api-config.edit', $target));
        $response->assertSessionHasErrors(['ten_hang']);

        $this->assertSame(1, Hang_Van_Chuyen::query()->where('ten_hang', 'VIETTEL_POST')->whereNull('ma_nguoi_dung')->count());

        $target->refresh();
        $this->assertSame('GHN', $target->ten_hang);
        $this->assertSame('token-b', $target->api_token);
        $this->assertSame('199363', $target->shop_id);

        $this->assertDatabaseHas('hang_van_chuyen', [
            'ma_hang_van_chuyen' => $keep->ma_hang_van_chuyen,
            'ten_hang' => 'VIETTEL_POST',
        ]);
    }
}



