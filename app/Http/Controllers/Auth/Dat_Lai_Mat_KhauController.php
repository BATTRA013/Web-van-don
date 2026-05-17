<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Auth/Dat_Lai_Mat_KhauController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER DAT LAI MAT KHAU
|--------------------------------------------------------------------------
| Nhan token + email + mat khau moi de cap nhat mat khau tai khoan.
| Day la buoc 2 cua luong khoi phuc mat khau.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Nguoi_Dung;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class Dat_Lai_Mat_KhauController extends Controller
{
    /**
     * Hien thi form dat lai mat khau (co token tu email).
     */
    public function create(Request $request): View
    {
        // Truyen request de view lay token/email tu query string khi can.
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Xu ly dat lai mat khau bang token reset.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate day du token + email + mat khau moi.
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Goi co che reset password cua Laravel.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Nguoi_Dung $user) use ($request) {
                // Hash mat khau moi truoc khi luu vao DB.
                $user->forceFill([
                    'mat_khau' => Hash::make($request->password),
                ])->save();

                // Ban su kien thong bao da reset mat khau thanh cong.
                event(new PasswordReset($user));
            }
        );

        // Thanh cong -> ve trang dang nhap; that bai -> quay lai kem loi.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}



