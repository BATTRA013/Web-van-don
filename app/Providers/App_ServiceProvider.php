<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Providers/App_ServiceProvider.php
| - Buoc 1: Dang ky binding/cau hinh can thiet cho he thong.
| - Buoc 2: Boot cac thiet lap runtime dung chung.
*/

/*
|--------------------------------------------------------------------------
| SERVICE PROVIDER CHINH
|--------------------------------------------------------------------------
| Noi dang ky binding/logic khoi tao chung cho toan bo ung dung.
| Neu can bind interface -> implementation, thuong dat o day.
*/

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class App_ServiceProvider extends ServiceProvider
{
    /**
     * Dang ky cac service vao container (bind interface -> implementation).
     */
    public function register(): void
    {
        // Vi du: $this->app->bind(Interface::class, Implementation::class);
    }

    /**
     * Khoi tao cac logic can chay khi app boot xong.
     */
    public function boot(): void
    {
        // Vi du: dat locale, cau hinh mac dinh, chia se du lieu cho view.
    }
}



