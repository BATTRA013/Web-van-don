<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/ProfileController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER THONG TIN CA NHAN
|--------------------------------------------------------------------------
| Nhan request lien quan profile (xem/sua/xoa tai khoan).
| Controller goi Request de validate, sau do cap nhat Model va redirect.
*/

namespace App\Http\Controllers;

use App\Http\Requests\Cap_Nhat_Thong_Tin_Ca_NhanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Hien thi trang chinh sua thong tin ca nhan.
     */
    public function edit(Request $request): View
    {
        // Truyen user dang dang nhap qua view de hien thi du lieu.
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Cap nhat thong tin profile sau khi form duoc validate.
     */
    public function update(Cap_Nhat_Thong_Tin_Ca_NhanRequest $request): RedirectResponse
    {
        // fill() gan cac field hop le tu validated data vao model user.
        $request->user()->fill($request->validated());

        // Luu thay doi xuong database.
        $request->user()->save();

        // Redirect ve lai trang profile kem trang thai cap nhat.
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Xoa tai khoan nguoi dung dang dang nhap.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Bat buoc nhap lai mat khau de xac nhan thao tac nguy hiem.
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        // Luu user truoc khi logout de co doi tuong de xoa.
        $user = $request->user();

        // Dang xuat user ra khoi he thong.
        Auth::logout();

        // Xoa ban ghi user khoi database.
        $user->delete();

        // Huy session cu va tao csrf token moi.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Dua ve trang chu.
        return Redirect::to('/');
    }
}



