<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/View/Components/AppLayout.php
| - Buoc 1: Xu ly input theo trach nhiem cua file.
| - Buoc 2: Thuc hien nghiep vu trung tam.
| - Buoc 3: Tra ket qua cho lop su dung tiep theo.
*/

/*
|--------------------------------------------------------------------------
| VIEW COMPONENT: APP LAYOUT
|--------------------------------------------------------------------------
| Component nay bao boc giao dien chinh sau khi dang nhap.
| Cac trang dashboard/quan ly thuong su dung layout nay.
*/

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Tra ve view layout chinh duoc su dung khi user da dang nhap.
     */
    public function render(): View
    {
        // resources/views/layouts/app.blade.php
        return view('layouts.app');
    }
}



