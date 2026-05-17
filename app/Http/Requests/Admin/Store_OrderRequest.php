<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Admin/Store_OrderRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST TAO/SUA DON NOI BO
|--------------------------------------------------------------------------
| Validate du lieu don hang noi bo truoc khi controller tao/sua don.
| Muc tieu: dam bao du lieu dau vao dung dinh dang va day du.
*/

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Store_OrderRequest extends FormRequest
{
    /**
     * Chuan hoa input de giam loi do khoang trang/ky tu thua.
     */
    protected function prepareForValidation(): void
    {
        // Muc tieu: Chuan hoa input truoc validate theo quy uoc cua mang validate request.
        $normalizedPhone = preg_replace('/\D+/', '', (string) $this->input('receiver_phone', ''));

        $this->merge([
            'receiver_name' => trim((string) $this->input('receiver_name', '')),
            'receiver_phone' => $normalizedPhone,
            'receiver_address' => trim((string) $this->input('receiver_address', '')),
            'to_ward_code' => strtoupper(trim((string) $this->input('to_ward_code', ''))),
            'item_name' => trim((string) $this->input('item_name', '')),
        ]);
    }

    /**
     * Cho phep request di tiep (phan quyen da duoc xu ly o middleware).
     */
    public function authorize(): bool
    {
        // Muc tieu: Xac thuc request co du quyen thuc hien nghiep vu module_chung.
        return true;
    }

    /**
     * Rule validate cho form tao/sua don noi bo.
     */
    public function rules(): array
    {
        // Muc tieu: Dinh nghia bo luat validate phu hop du lieu gui di cua mang validate request.
        return [
            'receiver_name' => ['required', 'string', 'max:150'],
            'receiver_phone' => ['required', 'digits_between:9,11'],
            'receiver_address' => ['required', 'string', 'max:255'],
            'to_province_id' => ['required', 'integer', 'min:1'],
            'to_district_id' => ['required', 'integer', 'min:1'],
            'to_ward_code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/'],
            'item_name' => ['required', 'string', 'max:255'],
            'item_weight' => ['required', 'integer', 'min:1', 'max:500000'],
            'item_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'item_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'cod_value' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'length' => ['nullable', 'integer', 'between:0,300'],
            'width' => ['nullable', 'integer', 'between:0,300'],
            'height' => ['nullable', 'integer', 'between:0,300'],
        ];
    }

    /**
     * Thong bao loi ngan gon de nguoi dung de sua.
     */
    public function messages(): array
    {
        // Muc tieu: Tuy bien thong diep loi validate de user de xu ly theo mang validate request.
        return [
            'receiver_phone.digits_between' => 'So dien thoai nguoi nhan phai co 9-11 chu so.',
            'to_ward_code.regex' => 'Ma phuong/xa chi duoc chua chu in hoa va chu so.',
            'length.between' => 'Chieu dai phai trong khoang 0-300 cm.',
            'width.between' => 'Chieu rong phai trong khoang 0-300 cm.',
            'height.between' => 'Chieu cao phai trong khoang 0-300 cm.',
        ];
    }
}




