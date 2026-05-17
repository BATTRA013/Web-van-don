<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Middleware/Kiem_Tra_Vai_Tro.php
| - Buoc 1: Doc thong tin user/request hien tai.
| - Buoc 2: Kiem tra dieu kien truy cap (role, trang thai tai khoan, ...).
| - Buoc 3: Tu choi hoac cho di tiep den pipeline xu ly ke tiep.
*/

/*
|--------------------------------------------------------------------------
| MIDDLEWARE KIEM TRA VAI TRO
|--------------------------------------------------------------------------
| Chot bao ve truoc controller: user co dung vai tro duoc phep hay khong.
| Neu khong du quyen se chan va redirect/bao loi.
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class Kiem_Tra_Vai_Tro
{
    /**
     * Kiem tra user hien tai co nam trong danh sach vai tro duoc phep hay khong.
     *
    * @param string ...$roles Danh sach vai tro duoc truyen tu route middleware, vd: role:admin,chu_shop
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Ham chuan hoa vai tro de tranh sai lech do dau/case/khoang trang.
        $normalizeRole = static fn (?string $role): string => Str::of((string) $role)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        // Vai tro cua user dang dang nhap.
        $userRole = $normalizeRole($request->user()?->vai_tro);
        // Danh sach vai tro duoc phep sau khi chuan hoa.
        $allowedRoles = array_map($normalizeRole, $roles);

        // Neu user khong co vai tro hoac vai tro khong nam trong danh sach cho phep -> chan.
        if ($userRole === '' || ! in_array($userRole, $allowedRoles, true)) {
            $message = 'Bạn không có quyền truy cập chức năng này.';

            // Neu request la API/JSON thi tra loi 403 de frontend xu ly.
            if ($request->expectsJson()) {
                abort(403, $message);
            }

            // Neu la web request thi dieu huong user ve dashboard/hoac trang goc.
            $fallback = in_array($userRole, ['admin', 'chushop', 'quanlychanhxe'], true)
                ? route('dashboard')
                : url('/');

            return redirect()->to($fallback)->with('error', $message);
        }

        // Du quyen -> cho phep di tiep den controller.
        return $next($request);
    }
}



