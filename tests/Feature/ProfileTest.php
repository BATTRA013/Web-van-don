<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/ProfileTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST PROFILE
|--------------------------------------------------------------------------
| Kiem tra cac hanh vi: xem profile, cap nhat profile, xoa tai khoan.
*/

namespace Tests\Feature;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User dang nhap co the mo trang profile.
     */
    public function test_profile_page_is_displayed(): void
    {
        // Tao user gia lap trong DB test.
        $user = Nguoi_Dung::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        // Ky vong request thanh cong.
        $response->assertOk();
    }

    /**
     * User co the cap nhat thong tin profile hop le.
     */
    public function test_profile_information_can_be_updated(): void
    {
        $user = Nguoi_Dung::factory()->create();

        // Gui request cap nhat profile.
        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'ho_ten' => 'Test User',
                'email' => 'test@example.com',
            ]);

        // Ky vong khong loi validate va redirect ve profile.
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        // Xac nhan du lieu da thay doi trong DB.
        $this->assertSame('Test User', $user->ho_ten);
        $this->assertSame('test@example.com', $user->email);
    }

    /**
     * User co the xoa tai khoan khi nhap dung mat khau.
     */
    public function test_user_can_delete_their_account(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        // Sau khi xoa phai bi logout va ve trang chu.
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    /**
     * Neu nhap sai mat khau thi khong duoc xoa tai khoan.
     */
    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        // Ky vong bao loi validate trong bag userDeletion.
        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}



