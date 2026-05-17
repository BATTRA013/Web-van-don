<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Admin/Tao_Don_GhnRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST TAO DON GHN
|--------------------------------------------------------------------------
| Validate payload gui sang GHN (ten nguoi nhan, dia chi, can nang, dich vu...).
| Day la request chuyen biet cho luong tao van don GHN.
*/

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Tao_Don_GhnRequest extends FormRequest
{
    /**
     * Cho phep gui request tao don GHN.
     */
    public function authorize(): bool
    {
        // Muc tieu: Xac thuc request co du quyen thuc hien nghiep vu module_chung.
        return true;
    }

    /**
     * Rule validate payload truoc khi goi API GHN.
     */
    public function rules(): array
    {
        // Muc tieu: Dinh nghia bo luat validate phu hop du lieu gui di cua mang validate request.
        return [
            'payment_type_id' => ['required', 'integer', 'in:1,2'],
            'required_note' => ['required', 'string', 'max:50'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_phone' => ['nullable', 'string', 'max:20'],
            'sender_address' => ['nullable', 'string', 'max:500'],
            'from_province_id' => ['nullable', 'integer', 'min:1'],
            'from_province_name' => ['nullable', 'string', 'max:255'],
            'from_district_id' => ['nullable', 'integer', 'min:1'],
            'from_district_name' => ['nullable', 'string', 'max:255'],
            'from_ward_code' => ['nullable', 'string', 'max:20'],
            'from_ward_name' => ['nullable', 'string', 'max:255'],
            'return_phone' => ['nullable', 'string', 'max:20'],
            'return_address' => ['nullable', 'string', 'max:500'],
            'return_district_id' => ['nullable', 'integer', 'min:1'],
            'return_ward_code' => ['nullable', 'string', 'max:20'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'receiver_address' => ['required', 'string', 'max:500'],
            'to_province_id' => ['nullable', 'integer', 'min:1'],
            'to_province_name' => ['nullable', 'string', 'max:255'],
            'to_district_id' => ['required', 'integer', 'min:1'],
            'to_district_name' => ['nullable', 'string', 'max:255'],
            'to_ward_code' => ['required', 'string', 'max:20'],
            'to_ward_name' => ['nullable', 'string', 'max:255'],
            'item_name' => ['required', 'string', 'max:255'],
            'item_weight' => ['required', 'integer', 'min:1'],
            'item_quantity' => ['required', 'integer', 'min:1'],
            'item_price' => ['required', 'integer', 'min:0'],
            'cod_value' => ['nullable', 'numeric', 'min:0'],
            'length' => ['required', 'integer', 'min:1'],
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
            'service_type_id' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
            'token' => ['nullable', 'string'],
            'shop_id' => ['nullable', 'integer'],
            'source_order_id' => ['nullable', 'integer', 'exists:don_hang,ma_don_hang'],
        ];
    }

    /**
     * Dat gia tri mac dinh cho mot so truong truoc khi validate.
     */
    protected function prepareForValidation(): void
    {
        // Neu khong truyen cod_value thi xem nhu 0.
        $codValue = (int) ($this->input('cod_value') ?? 0);

        // Tu dong bo sung gia tri default de form don gian hon cho nguoi dung.
        $this->merge([
            'payment_type_id' => $this->input('payment_type_id', 1),
            'required_note' => $this->input('required_note', 'KHONGCHOXEMHANG'),
            'item_price' => $this->input('item_price', $codValue),
        ]);
    }
}




