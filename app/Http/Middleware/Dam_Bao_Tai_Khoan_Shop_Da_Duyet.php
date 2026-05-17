<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Middleware/Dam_Bao_Tai_Khoan_Shop_Da_Duyet.php
| - Buoc 1: Doc thong tin user/request hien tai.
| - Buoc 2: Kiem tra dieu kien truy cap (role, trang thai tai khoan, ...).
| - Buoc 3: Tu choi hoac cho di tiep den pipeline xu ly ke tiep.
*/

/*
|--------------------------------------------------------------------------
| MIDDLEWARE DAM BAO TAI KHOAN SHOP DA DUYET
|--------------------------------------------------------------------------
| Kiem tra user role chu shop/quan ly chanh xe da duoc duyet va dang hoat dong hay chua.
| Neu chua dat dieu kien se logout va dua ve man hinh dang nhap.
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class Dam_Bao_Tai_Khoan_Shop_Da_Duyet
{
    /**
     * Ham trung gian kiem tra shop account truoc khi cho di tiep.
     *
     * Luong xu ly:
     * 1) Lay user dang dang nhap tu request.
     * 2) Neu chua dang nhap thi bo qua middleware nay.
    * 3) Neu role khong phai chu shop/quan ly chanh xe thi bo qua.
    * 4) Neu la role nghiep vu ma chua duyet/bi khoa thi dang xuat + huy session.
     * 5) Hop le thi cho request di tiep vao controller.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lay thong tin user hien tai (neu da dang nhap).
        $user = $request->user();

        // Chua co user thi khong can kiem tra gi them.
        if (! $user) {
            return $next($request);
        }

        // Chuan hoa chuoi vai tro de so sanh an toan (bo dau, viet thuong, bo ky tu dac biet).
        $normalizedRole = Str::of((string) $user->vai_tro)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        // Chi ap dung cho role chu shop va quan ly chanh xe.
        if (! in_array($normalizedRole, ['chushop', 'quanlychanhxe'], true)) {
            return $next($request);
        }

        // Neu la role nghiep vu ma tai khoan khong hoat dong hoac chua duyet thi chan truy cap.
        if ((int) $user->trang_thai !== 1 || (int) ($user->trang_thai_duyet ?? 0) !== 1) {
            // Dang xuat khoi guard web de cat phien dang nhap.
            auth()->guard('web')->logout();

            // Huy session cu va tao CSRF token moi de dam bao an toan.
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Dua ve trang dang nhap kem thong bao ro ly do bi chan.
            return redirect()->route('login')->with('status', 'Tài khoản chủ shop/quản lý chành xe chưa được duyệt hoặc đã bị khóa.');
        }

        // Dat dieu kien -> cho request di tiep.
        return $next($request);
    }
}



