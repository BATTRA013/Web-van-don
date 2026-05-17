<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Cap_Nhat_Thong_Tin_Ca_NhanRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST CAP NHAT THONG TIN CA NHAN
|--------------------------------------------------------------------------
| Validate du lieu profile (ho ten, email) truoc khi cap nhat.
| Tach validate ra khoi controller de code de doc, de bao tri hon.
*/

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Cap_Nhat_Thong_Tin_Ca_NhanRequest extends FormRequest
{
    /**
     * Dinh nghia rule validate cho form cap nhat profile.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Email bat buoc duy nhat, bo qua chinh user hien tai.
        return [
            'ho_ten' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique('nguoi_dung', 'email')->ignore($this->user()->getAuthIdentifier(), 'ma_nguoi_dung'),
            ],
        ];
    }
}



