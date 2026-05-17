<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Auth/Dang_NhapController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER DANG NHAP / DANG XUAT
|--------------------------------------------------------------------------
| Hien thi form dang nhap, xu ly xac thuc va huy session khi dang xuat.
| Day la diem vao auth chinh cho nguoi dung web.
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Dang_NhapRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class Dang_NhapController extends Controller
{
    /**
     * Hien thi giao dien dang nhap cho nguoi dung chua dang nhap.
     */
    public function create(): View
    {
        // Tra ve view Blade: resources/views/auth/login.blade.php
        return view('auth.login');
    }

    /**
     * Xu ly submit form dang nhap.
     *
     * Luong:
     * 1) Goi FormRequest de validate + authenticate.
     * 2) Regenerate session ID de chong session fixation.
     * 3) Redirect den trang duoc yeu cau truoc do, neu khong co thi ve dashboard.
     */
    public function store(Dang_NhapRequest $request): RedirectResponse
    {
        // Chay toan bo logic xac thuc da dong goi trong Dang_NhapRequest.
        $request->authenticate();

        // Tao session ID moi sau dang nhap de tang bao mat.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Dang xuat nguoi dung khoi he thong.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Xoa trang thai dang nhap o guard web.
        Auth::guard('web')->logout();

        // Huy session hien tai.
        $request->session()->invalidate();

        // Tao CSRF token moi cho request tiep theo.
        $request->session()->regenerateToken();

        // Dua user ve trang chu.
        return redirect('/');
    }
}



