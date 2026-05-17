<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/Auth/RegistrationTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST DANG KY
|--------------------------------------------------------------------------
| Kiem tra giao dien dang ky va luong tao tai khoan moi.
*/

namespace Tests\Feature\Auth;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trang dang ky co the hien thi.
     */
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * User moi co the dang ky thanh cong.
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'ho_ten' => 'Test User',
            'ten_don_vi' => 'Test Shop',
            'vai_tro' => 'quan_ly_chanh_xe',
            'login' => 'testuser',
            'so_dien_thoai' => '0912345678',
            'email' => 'test@example.com',
            'dia_chi' => '123 Test Address',
            'mst' => null,
            'password' => 'password',
            'password_confirmation' => 'password',
            'dong_y_dieu_khoan' => '1',
        ]);

        // Dang ky xong khong auto login, redirect ve login.
        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));

        // Kiem tra ban ghi user da duoc tao voi trang thai cho duyet.
        $this->assertDatabaseHas('nguoi_dung', [
            'ten_dang_nhap' => 'testuser',
            'email' => 'test@example.com',
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai_duyet' => Nguoi_Dung::DUYET_CHO_DUYET,
        ]);
    }
}



