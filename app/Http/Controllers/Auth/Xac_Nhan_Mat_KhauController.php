<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Auth/Xac_Nhan_Mat_KhauController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER XAC NHAN MAT KHAU
|--------------------------------------------------------------------------
| Xac nhan lai mat khau nguoi dang nhap truoc khi vao thao tac nhay cam.
| Neu dung, Laravel danh dau moc thoi gian xac nhan trong session.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Xac_Nhan_Mat_KhauController extends Controller
{
    /**
     * Hien thi form nhap lai mat khau de xac nhan.
     */
    public function show(): View
    {
        // Tra ve view confirm password.
        return view('auth.confirm-password');
    }

    /**
     * Kiem tra mat khau vua nhap co dung voi tai khoan dang dang nhap khong.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate mat khau bang guard web voi username hien tai.
        if (! Auth::guard('web')->validate([
            'ten_dang_nhap' => $request->user()->ten_dang_nhap,
            'password' => $request->password,
        ])) {
            // Sai mat khau -> nem ValidationException cho field password.
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // Danh dau moc thoi gian da xac nhan mat khau trong session.
        $request->session()->put('auth.password_confirmed_at', time());

        // Quay ve trang dinh den ban dau (hoac dashboard).
        return redirect()->intended(route('dashboard', absolute: false));
    }
}



