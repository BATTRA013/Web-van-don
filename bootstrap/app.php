<?php

/*
|--------------------------------------------------------------------------
| HUONG DAN CHO NGUOI MOI
|--------------------------------------------------------------------------
| File nay khoi tao ung dung Laravel.
| - withRouting: khai bao file route cua app.
| - withMiddleware->alias: dat ten ngan cho middleware de goi trong route.
| - withExceptions: noi mo rong xu ly loi toan he thong.
*/

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    // Khai bao file route web, route console va endpoint health-check.
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Dang ky alias middleware de su dung gon trong routes.
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\Kiem_Tra_Vai_Tro::class,
            'shop.approved' => \App\Http\Middleware\Dam_Bao_Tai_Khoan_Shop_Da_Duyet::class,
        ]);
    })
    // Noi dat cac tuy bien xu ly exception toan app (hien de trong).
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
