<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: routes/web.php
| - Buoc 1: Dinh nghia endpoint/command can ho tro.
| - Buoc 2: Gan middleware va anh xa den controller/handler tuong ung.
| - Buoc 3: Thiet lap boundary quyen truy cap theo module nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| ROUTE WEB CUA DU AN
|--------------------------------------------------------------------------
| File nay anh xa URL -> Controller method + middleware.
| Nhin file nay de biet user vao duong dan nao se chay logic nao.
*/

use App\Http\Controllers\Admin\Api_ConfigController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Doi_Soat_CodController;
use App\Http\Controllers\Admin\Don_HangController;
use App\Http\Controllers\Admin\Nha_XeController;
use App\Http\Controllers\Admin\Quan_Ly_Nguoi_DungController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Trang chao ban dau cua he thong.
    return view('welcome');
});

// Cac route can user da dang nhap va da verify email (neu bat verify).
Route::middleware(['auth', 'verified'])->group(function () {
    // Nhom route dashboard chung cho 3 vai tro, co them middleware kiem tra tai khoan nghiep vu da duyet.
    Route::middleware(['role:admin,chu_shop,quan_ly_chanh_xe', 'shop.approved'])->group(function () {
        // Trang tong quan sau khi dang nhap thanh cong.
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // CRUD don hang noi bo.
        Route::get('/dashboard/orders', [Don_HangController::class, 'index'])->name('orders.index');
        Route::get('/dashboard/orders/create', [Don_HangController::class, 'create'])->middleware('role:admin,chu_shop')->name('orders.create');
        Route::post('/dashboard/orders', [Don_HangController::class, 'store'])->middleware('role:admin,chu_shop')->name('orders.store');
        Route::get('/dashboard/orders/{order}', [Don_HangController::class, 'show'])->name('orders.show');
        Route::get('/dashboard/orders/{order}/edit', [Don_HangController::class, 'edit'])->middleware('role:admin,chu_shop')->name('orders.edit');
        Route::put('/dashboard/orders/{order}', [Don_HangController::class, 'update'])->middleware('role:admin,chu_shop')->name('orders.update');
        Route::delete('/dashboard/orders/{order}', [Don_HangController::class, 'destroy'])->middleware('role:admin,chu_shop')->name('orders.destroy');

        // Tao van don da hang (GHN/Viettel) va dong bo trang thai.
        Route::get('/dashboard/orders/shipments/create', [Don_HangController::class, 'createShipment'])->middleware('role:admin,chu_shop')->name('orders.shipments.create');
        Route::post('/dashboard/orders/shipments', [Don_HangController::class, 'storeShipment'])->middleware('role:admin,chu_shop')->name('orders.shipments.store');
        Route::post('/dashboard/orders/shipments/sync', [Don_HangController::class, 'syncShipmentStatuses'])->middleware('role:admin,chu_shop')->name('orders.shipments.sync-all');

        // Compatibility route cho luong GHN cu.
        Route::get('/dashboard/orders/ghn/create', [Don_HangController::class, 'createGhn'])->middleware('role:admin,chu_shop')->name('orders.ghn.create');
        Route::get('/dashboard/orders/ghn/meta/provinces', [Don_HangController::class, 'ghnProvinceOptions'])->middleware('role:admin,chu_shop')->name('orders.ghn.meta.provinces');
        Route::get('/dashboard/orders/ghn/meta/districts', [Don_HangController::class, 'ghnDistrictOptions'])->middleware('role:admin,chu_shop')->name('orders.ghn.meta.districts');
        Route::get('/dashboard/orders/ghn/meta/wards', [Don_HangController::class, 'ghnWardOptions'])->middleware('role:admin,chu_shop')->name('orders.ghn.meta.wards');
        Route::post('/dashboard/orders/ghn', [Don_HangController::class, 'storeGhn'])->middleware('role:admin,chu_shop')->name('orders.ghn.store');
        Route::post('/dashboard/orders/ghn/sync', [Don_HangController::class, 'syncGhnStatuses'])->middleware('role:admin,chu_shop')->name('orders.ghn.sync-all');

        // Gui don qua chanh xe (van don ngoai tuyen) theo tung don.
        Route::post('/dashboard/orders/{order}/external-route-bills', [Don_HangController::class, 'storeExternalRouteBill'])->middleware('role:admin,chu_shop')->name('orders.external-route-bills.store');
        Route::delete('/dashboard/orders/{order}/external-route-bills/{bill}', [Don_HangController::class, 'destroyExternalRouteBill'])->middleware('role:admin,chu_shop')->name('orders.external-route-bills.destroy');
        Route::post('/dashboard/orders/{order}/external-route-bills/{bill}/accept', [Don_HangController::class, 'acceptExternalRouteBill'])->middleware('role:quan_ly_chanh_xe')->name('orders.external-route-bills.accept');
        Route::post('/dashboard/orders/{order}/external-route-bills/{bill}/reject', [Don_HangController::class, 'rejectExternalRouteBill'])->middleware('role:quan_ly_chanh_xe')->name('orders.external-route-bills.reject');
        Route::put('/dashboard/orders/{order}/external-route-bills/{bill}/receipt', [Don_HangController::class, 'updateExternalRouteBillReceipt'])->middleware('role:quan_ly_chanh_xe')->name('orders.external-route-bills.receipt');

        // Dong bo trang thai theo tung don.
        Route::post('/dashboard/orders/{order}/shipments/sync', [Don_HangController::class, 'syncShipmentStatus'])->middleware('role:admin,chu_shop')->name('orders.shipments.sync-one');
        Route::post('/dashboard/orders/{order}/ghn/sync', [Don_HangController::class, 'syncGhnStatus'])->middleware('role:admin,chu_shop')->name('orders.ghn.sync-one');
    });

    // Nhom route cau hinh API: chi admin va chu shop duoc su dung.
    Route::middleware(['role:admin,chu_shop', 'shop.approved'])->group(function () {
        Route::get('/dashboard/api-config', [Api_ConfigController::class, 'index'])->name('api-config.index');
        Route::get('/dashboard/api-config/create', [Api_ConfigController::class, 'create'])->name('api-config.create');
        Route::post('/dashboard/api-config', [Api_ConfigController::class, 'store'])->name('api-config.store');
        Route::get('/dashboard/api-config/{carrier}', [Api_ConfigController::class, 'show'])->name('api-config.show');
        Route::get('/dashboard/api-config/{carrier}/edit', [Api_ConfigController::class, 'edit'])->name('api-config.edit');
        Route::put('/dashboard/api-config/{carrier}', [Api_ConfigController::class, 'update'])->name('api-config.update');
        Route::delete('/dashboard/api-config/{carrier}', [Api_ConfigController::class, 'destroy'])->name('api-config.destroy');
        Route::post('/dashboard/api-config/hang/save', [Api_ConfigController::class, 'luuCauHinhHangVanChuyen'])->name('api-config.carrier.save');
        Route::post('/dashboard/api-config/hang/test-connection', [Api_ConfigController::class, 'kiemTraKetNoiHangVanChuyen'])->name('api-config.carrier.test-connection');
        Route::post('/dashboard/api-config/ghn/save', [Api_ConfigController::class, 'saveGhnConfig'])->name('api-config.ghn.save');
        Route::post('/dashboard/api-config/ghn/test-connection', [Api_ConfigController::class, 'testGhnConnection'])->name('api-config.ghn.test-connection');
    });

    // Nhom route quan ly nha xe + doi soat COD.
    Route::middleware('role:admin,quan_ly_chanh_xe,chu_shop')->group(function () {
        // Danh muc nha xe/chanh xe.
        Route::get('/dashboard/carriers', [Nha_XeController::class, 'index'])->name('carriers.index');
        Route::get('/dashboard/carriers/create', [Nha_XeController::class, 'create'])->name('carriers.create');
        Route::post('/dashboard/carriers', [Nha_XeController::class, 'store'])->name('carriers.store');
        Route::get('/dashboard/carriers/{carrier}', [Nha_XeController::class, 'show'])->name('carriers.show');
        Route::get('/dashboard/carriers/{carrier}/edit', [Nha_XeController::class, 'edit'])->name('carriers.edit');
        Route::put('/dashboard/carriers/{carrier}', [Nha_XeController::class, 'update'])->name('carriers.update');
        Route::delete('/dashboard/carriers/{carrier}', [Nha_XeController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('carriers.destroy');

        // Doi soat COD theo don hang.
        Route::get('/dashboard/cod-reconciliation', [Doi_Soat_CodController::class, 'index'])->name('cod.index');
        Route::post('/dashboard/cod-reconciliation/auto-run', [Doi_Soat_CodController::class, 'autoReconcile'])->name('cod.auto');
        Route::get('/dashboard/cod-reconciliation/create', [Doi_Soat_CodController::class, 'create'])->name('cod.create');
        Route::post('/dashboard/cod-reconciliation', [Doi_Soat_CodController::class, 'store'])->name('cod.store');
        Route::get('/dashboard/cod-reconciliation/{cod}', [Doi_Soat_CodController::class, 'show'])->name('cod.show');
        Route::get('/dashboard/cod-reconciliation/{cod}/edit', [Doi_Soat_CodController::class, 'edit'])->name('cod.edit');
        Route::put('/dashboard/cod-reconciliation/{cod}', [Doi_Soat_CodController::class, 'update'])->name('cod.update');
        Route::delete('/dashboard/cod-reconciliation/{cod}', [Doi_Soat_CodController::class, 'destroy'])->name('cod.destroy');
    });

    // Nhom route quan tri user chi danh cho admin.
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard/users', [Quan_Ly_Nguoi_DungController::class, 'index'])->name('users.index');
        Route::get('/dashboard/users/create', [Quan_Ly_Nguoi_DungController::class, 'create'])->name('users.create');
        Route::post('/dashboard/users', [Quan_Ly_Nguoi_DungController::class, 'store'])->name('users.store');
        Route::post('/dashboard/users/{user}/approve', [Quan_Ly_Nguoi_DungController::class, 'approve'])->name('users.approve');
        Route::post('/dashboard/users/{user}/reject', [Quan_Ly_Nguoi_DungController::class, 'reject'])->name('users.reject');
        Route::get('/dashboard/users/{user}', [Quan_Ly_Nguoi_DungController::class, 'show'])->name('users.show');
        Route::get('/dashboard/users/{user}/edit', [Quan_Ly_Nguoi_DungController::class, 'edit'])->name('users.edit');
        Route::put('/dashboard/users/{user}', [Quan_Ly_Nguoi_DungController::class, 'update'])->name('users.update');
        Route::delete('/dashboard/users/{user}', [Quan_Ly_Nguoi_DungController::class, 'destroy'])->name('users.destroy');
    });
});

// Nap them cac route auth tu file rieng.
require __DIR__ . '/auth.php';

// Nhom route profile: can dang nhap.
Route::middleware(['auth'])->group(function () {
    // Thong tin tai khoan ca nhan cua user hien tai.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



