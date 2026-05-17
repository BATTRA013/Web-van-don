<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Auth/Dang_KyController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER DANG KY
|--------------------------------------------------------------------------
| Xu ly dang ky tai khoan moi: validate form, tao user, tra thong bao ket qua.
| User tao moi thuong o trang thai cho duyet theo nghiep vu cua du an.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Nguoi_Dung;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class Dang_KyController extends Controller
{
    /**
     * Hien thi form dang ky.
     */
    public function create(): View
    {
        // Tra ve giao dien dang ky.
        return view('auth.register');
    }

    /**
     * Xu ly submit form dang ky tai khoan.
     *
     * Buoc xu ly:
     * 1) Validate du lieu dau vao.
     * 2) Tao user moi vao bang nguoi_dung.
     * 3) Phat su kien Registered.
     * 4) Chuyen ve trang dang nhap kem thong bao.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate tung truong dang ky.
        $request->validate([
            'ho_ten' => ['required', 'string', 'max:255'],
            'ten_don_vi' => ['required', 'string', 'max:150'],
            'vai_tro' => ['required', 'string', Rule::in(['chu_shop', 'quan_ly_chanh_xe'])],
            'login' => ['required', 'string', 'max:100', 'unique:nguoi_dung,ten_dang_nhap'],
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150', 'unique:nguoi_dung,email'],
            'dia_chi' => ['required', 'string', 'max:255'],
            'mst' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'dong_y_dieu_khoan' => ['accepted'],
        ]);

        // Tao ban ghi user moi.
        $user = Nguoi_Dung::create([
            'ho_ten' => $request->string('ho_ten')->toString(),
            'ten_don_vi' => $request->string('ten_don_vi')->toString(),
            'ten_dang_nhap' => $request->string('login')->toString(),
            'so_dien_thoai' => $request->string('so_dien_thoai')->toString(),
            'email' => $request->string('email')->toString(),
            'dia_chi' => $request->string('dia_chi')->toString(),
            'mst' => $request->filled('mst') ? $request->string('mst')->toString() : null,
            'mat_khau' => $request->string('password')->toString(),
            'vai_tro' => $request->string('vai_tro')->toString(),
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_CHO_DUYET,
        ]);

        // Ban su kien cho cac listener (neu co) cua he thong auth.
        event(new Registered($user));

        // Chuyen huong sau dang ky thanh cong.
        return redirect()->route('login')->with('status', 'Đăng ký thành công. Tài khoản đang chờ quản trị viên duyệt.');
    }
}



