<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/Auth/AuthenticationTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST DANG NHAP / DANG XUAT
|--------------------------------------------------------------------------
| Kiem tra man hinh dang nhap, dang nhap dung/sai va dang xuat.
*/

namespace Tests\Feature\Auth;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trang dang nhap co the truy cap duoc.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * User co the dang nhap voi thong tin dung.
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->ten_dang_nhap,
            'password' => 'password',
        ]);

        // Ky vong da duoc xac thuc va redirect dashboard.
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * User khong the dang nhap neu sai mat khau.
     */
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $this->post('/login', [
            'login' => $user->ten_dang_nhap,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * User dang nhap co the dang xuat.
     */
    public function test_users_can_logout(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}



