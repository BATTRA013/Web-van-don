<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/UserApprovalFlowTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST DUYET/TU CHOI TAI KHOAN
|--------------------------------------------------------------------------
| Kiem tra luong approve/reject trong module quan ly user cua admin.
*/

namespace Tests\Feature;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User do admin tao moi luon mac dinh da duyet, bo qua input trang_thai_duyet tu client.
     */
    public function test_admin_created_user_is_forced_to_approved_status(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $payload = [
            'ho_ten' => 'Nguyen Van A',
            'ten_don_vi' => 'Shop A',
            'ten_dang_nhap' => 'shop_a_01',
            'so_dien_thoai' => '0900000001',
            'email' => 'shop-a@example.com',
            'mst' => '0312345678',
            'dia_chi' => 'TP HCM',
            'mat_khau' => 'password123',
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            // Co tinh gui gia tri xau de dam bao backend bo qua.
            'trang_thai_duyet' => Nguoi_Dung::DUYET_TU_CHOI,
            'ly_do_tu_choi' => 'Khong du dieu kien',
        ];

        $response = $this->actingAs($admin)->post(route('users.store'), $payload);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $createdUser = Nguoi_Dung::query()->where('ten_dang_nhap', 'shop_a_01')->firstOrFail();

        $this->assertSame(Nguoi_Dung::DUYET_DA_DUYET, (int) $createdUser->trang_thai_duyet);
        $this->assertNull($createdUser->ly_do_tu_choi);
    }

    /**
     * Admin duyet tai khoan quan ly chanh xe se cap nhat trang thai va xoa ly do tu choi.
     */
    public function test_admin_can_approve_user_account(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $pendingUser = Nguoi_Dung::factory()->create([
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_CHO_DUYET,
            'ly_do_tu_choi' => 'Thong tin chua day du',
        ]);

        $response = $this->actingAs($admin)->post(route('users.approve', $pendingUser));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendingUser->refresh();
        $this->assertSame(Nguoi_Dung::DUYET_DA_DUYET, (int) $pendingUser->trang_thai_duyet);
        $this->assertNull($pendingUser->ly_do_tu_choi);
    }

    /**
     * Admin tu choi tai khoan phai luu duoc ly do tu choi.
     */
    public function test_admin_can_reject_user_with_reason(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $pendingUser = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_CHO_DUYET,
            'ly_do_tu_choi' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('users.reject', $pendingUser), [
            'ly_do_tu_choi' => 'Giay to dang ky khong hop le',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendingUser->refresh();
        $this->assertSame(Nguoi_Dung::DUYET_TU_CHOI, (int) $pendingUser->trang_thai_duyet);
        $this->assertSame('Giay to dang ky khong hop le', $pendingUser->ly_do_tu_choi);
    }

    /**
     * Tu choi ma khong co ly do se bi validate chan.
     */
    public function test_reject_requires_reason(): void
    {
        $admin = Nguoi_Dung::factory()->create([
            'vai_tro' => 'admin',
        ]);

        $pendingUser = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_CHO_DUYET,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('users.show', $pendingUser))
            ->post(route('users.reject', $pendingUser), [
                'ly_do_tu_choi' => '',
            ]);

        $response->assertRedirect(route('users.show', $pendingUser));
        $response->assertSessionHasErrors(['ly_do_tu_choi']);

        $pendingUser->refresh();
        $this->assertSame(Nguoi_Dung::DUYET_CHO_DUYET, (int) $pendingUser->trang_thai_duyet);
    }

    /**
     * Nguoi khong phai admin khong duoc duyet tai khoan nguoi khac.
     */
    public function test_non_admin_cannot_approve_user(): void
    {
        $shopUser = Nguoi_Dung::factory()->create([
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $pendingUser = Nguoi_Dung::factory()->create([
            'trang_thai_duyet' => Nguoi_Dung::DUYET_CHO_DUYET,
        ]);

        $response = $this->actingAs($shopUser)->post(route('users.approve', $pendingUser));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');

        $pendingUser->refresh();
        $this->assertSame(Nguoi_Dung::DUYET_CHO_DUYET, (int) $pendingUser->trang_thai_duyet);
    }
}



