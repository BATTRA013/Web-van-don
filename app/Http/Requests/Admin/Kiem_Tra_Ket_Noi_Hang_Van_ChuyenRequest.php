<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Admin/Kiem_Tra_Ket_Noi_Hang_Van_ChuyenRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST KIEM TRA KET NOI HANG VAN CHUYEN
|--------------------------------------------------------------------------
| Validate du lieu dau vao truoc khi test ket noi API cua hang van chuyen.
| Giup tranh goi API voi thong tin thieu hoac sai dinh dang.
*/

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Kiem_Tra_Ket_Noi_Hang_Van_ChuyenRequest extends FormRequest
{
    /**
     * Cho phep request test ket noi API.
     */
    public function authorize(): bool
    {
        // Muc tieu: Xac thuc request co du quyen thuc hien nghiep vu module_chung.
        return true;
    }

    /**
     * Rule validate thong tin test ket noi (co the de trong de lay tu config).
     */
    public function rules(): array
    {
        // Muc tieu: Dinh nghia bo luat validate phu hop du lieu gui di cua mang validate request.
        return [
            'ten_hang' => ['nullable', 'string', 'max:150'],
            'token' => ['nullable', 'string'],
            'shop_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}




