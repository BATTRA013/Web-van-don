<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/Auth/Password_UpdateTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST DOI MAT KHAU
|--------------------------------------------------------------------------
| Kiem tra luong doi mat khau trong profile khi da dang nhap.
*/

namespace Tests\Feature\Auth;

use App\Models\Nguoi_Dung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Password_UpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User co the doi mat khau khi nhap dung mat khau hien tai.
     */
    public function test_password_can_be_updated(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        // Ky vong khong loi validate va redirect ve profile.
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        // Kiem tra mat khau moi da duoc hash va luu vao DB.
        $this->assertTrue(Hash::check('new-password', $user->refresh()->mat_khau));
    }

    /**
     * Neu sai mat khau hien tai thi khong duoc doi mat khau.
     */
    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = Nguoi_Dung::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}



