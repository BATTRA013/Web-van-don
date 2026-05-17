<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Auth/Cap_Nhat_Mat_KhauController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER: CAP NHAT MAT KHAU
|--------------------------------------------------------------------------
| Xu ly thao tac doi mat khau khi nguoi dung da dang nhap.
| File nay validate mat khau cu, mat khau moi va cap nhat vao database.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Cap_Nhat_Mat_KhauController extends Controller
{
    /**
     * Doi mat khau khi user da dang nhap trong trang profile.
     */
    public function update(Request $request): RedirectResponse
    {
        // Validate mat khau hien tai + mat khau moi (co xac nhan).
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Luu mat khau moi da duoc hash vao database.
        $request->user()->update([
            'mat_khau' => Hash::make($validated['password']),
        ]);

        // Quay lai trang truoc va thong bao thanh cong.
        return back()->with('status', 'password-updated');
    }
}



