<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/Auth/Password_ResetTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST QUEN/DAT LAI MAT KHAU
|--------------------------------------------------------------------------
| Kiem tra gui link reset, mo form reset va dat lai mat khau bang token.
*/

namespace Tests\Feature\Auth;

use App\Models\Nguoi_Dung;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Password_ResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trang nhap email quen mat khau hien thi duoc.
     */
    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    /**
     * User co the yeu cau gui link dat lai mat khau.
     */
    public function test_reset_password_link_can_be_requested(): void
    {
        // Fake thong bao de test khong gui email that.
        Notification::fake();

        $user = Nguoi_Dung::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Form reset-password co the mo bang token tu email.
     */
    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = Nguoi_Dung::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    /**
     * User co the dat lai mat khau voi token hop le.
     */
    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = Nguoi_Dung::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}



