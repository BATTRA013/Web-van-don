<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/View/Components/GuestLayout.php
| - Buoc 1: Xu ly input theo trach nhiem cua file.
| - Buoc 2: Thuc hien nghiep vu trung tam.
| - Buoc 3: Tra ket qua cho lop su dung tiep theo.
*/

/*
|--------------------------------------------------------------------------
| VIEW COMPONENT: GUEST LAYOUT
|--------------------------------------------------------------------------
| Layout cho khu vuc chua dang nhap (guest), vi du trang dang nhap/dang ky.
| Giup tach giao dien guest voi giao dien nguoi dung da dang nhap.
*/

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Tra ve layout guest (chua dang nhap).
     */
    public function render(): View
    {
        // resources/views/layouts/guest.blade.php
        return view('layouts.guest');
    }
}



