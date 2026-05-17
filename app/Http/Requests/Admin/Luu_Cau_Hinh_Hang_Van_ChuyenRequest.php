<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Admin/Luu_Cau_Hinh_Hang_Van_ChuyenRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST LUU CAU HINH HANG VAN CHUYEN
|--------------------------------------------------------------------------
| Validate ten hang, token, shop_id truoc khi luu cau hinh API vao database.
| Dung cho man hinh cau hinh ket noi voi don vi van chuyen.
*/

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Luu_Cau_Hinh_Hang_Van_ChuyenRequest extends FormRequest
{
    /**
     * Cho phep request di tiep; quyen duoc kiem soat o route/middleware.
     */
    public function authorize(): bool
    {
        // Muc tieu: Xac thuc request co du quyen thuc hien nghiep vu module_chung.
        return true;
    }

    /**
     * Rule validate cho form luu token/shop_id hang van chuyen.
     */
    public function rules(): array
    {
        // Muc tieu: Dinh nghia bo luat validate phu hop du lieu gui di cua mang validate request.
        return [
            'ten_hang' => ['nullable', 'string', 'max:150'],
            'token' => ['required', 'string'],
            'shop_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}




