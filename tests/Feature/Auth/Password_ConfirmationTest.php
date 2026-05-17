<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/Auth/Password_ConfirmationTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST XAC NHAN MAT KHAU
|--------------------------------------------------------------------------
| Kiem tra luong xac nhan mat khau truoc thao tac nhay cam.
*/

namespace Tests\Feature\Auth;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Password_ConfirmationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trang confirm-password hien thi duoc cho user dang nhap.
     */
    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertStatus(200);
    }

    /**
     * Nhap dung mat khau thi xac nhan thanh cong.
     */
    public function test_password_can_be_confirmed(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /**
     * Nhap sai mat khau thi he thong tra loi validate error.
     */
    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }
}



