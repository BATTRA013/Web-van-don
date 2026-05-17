<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Auth/Quen_Mat_KhauController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER QUEN MAT KHAU
|--------------------------------------------------------------------------
| Nhan email nguoi dung va gui link dat lai mat khau.
| Day la buoc 1 trong luong khoi phuc mat khau.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class Quen_Mat_KhauController extends Controller
{
    /**
     * Hien thi form yeu cau gui link dat lai mat khau.
     */
    public function create(): View
    {
        // Tra ve giao dien nhap email quen mat khau.
        return view('auth.forgot-password');
    }

    /**
     * Xu ly request gui email reset password.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Bat buoc email hop le truoc khi gui link.
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Thu gui link reset password toi email vua nhap.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Gui thanh cong thi bao status, that bai thi tra loi loi cho field email.
        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}



