<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: routes/auth.php
| - Buoc 1: Dinh nghia endpoint/command can ho tro.
| - Buoc 2: Gan middleware va anh xa den controller/handler tuong ung.
| - Buoc 3: Thiet lap boundary quyen truy cap theo module nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| ROUTE XAC THUC (AUTH)
|--------------------------------------------------------------------------
| File nay chua cac route dang ky, dang nhap, quen mat khau, dang xuat.
| Middleware guest/auth o day quyet dinh ai duoc vao route nao.
*/

use App\Http\Controllers\Auth\Dang_NhapController;
use App\Http\Controllers\Auth\Cap_Nhat_Mat_KhauController;
use App\Http\Controllers\Auth\Dat_Lai_Mat_KhauController;
use App\Http\Controllers\Auth\Dang_KyController;
use App\Http\Controllers\Auth\Quen_Mat_KhauController;
use App\Http\Controllers\Auth\Xac_Nhan_Mat_KhauController;
use Illuminate\Support\Facades\Route;

// Cac route chi danh cho khach chua dang nhap.
Route::middleware('guest')->group(function () {
    // Hien thi va xu ly dang ky.
    Route::get('register', [Dang_KyController::class, 'create'])
        ->name('register');

    Route::post('register', [Dang_KyController::class, 'store']);

    // Hien thi va xu ly dang nhap.
    Route::get('login', [Dang_NhapController::class, 'create'])
        ->name('login');

    Route::post('login', [Dang_NhapController::class, 'store']);

    // Buoc 1 quen mat khau: nhap email nhan link reset.
    Route::get('forgot-password', [Quen_Mat_KhauController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [Quen_Mat_KhauController::class, 'store'])
        ->name('password.email');

    // Buoc 2 reset mat khau tu token trong email.
    Route::get('reset-password/{token}', [Dat_Lai_Mat_KhauController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [Dat_Lai_Mat_KhauController::class, 'store'])
        ->name('password.store');
});

// Cac route chi danh cho user da dang nhap.
Route::middleware('auth')->group(function () {
    // Xac nhan lai mat khau truoc thao tac nhay cam.
    Route::get('confirm-password', [Xac_Nhan_Mat_KhauController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [Xac_Nhan_Mat_KhauController::class, 'store']);

    // Doi mat khau trong profile.
    Route::put('password', [Cap_Nhat_Mat_KhauController::class, 'update'])->name('password.update');

    // Dang xuat khoi he thong.
    Route::post('logout', [Dang_NhapController::class, 'destroy'])
        ->name('logout');
});



