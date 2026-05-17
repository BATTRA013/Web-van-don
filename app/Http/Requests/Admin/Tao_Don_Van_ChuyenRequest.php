<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Admin/Tao_Don_Van_ChuyenRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST TAO DON VAN CHUYEN DA HANG
|--------------------------------------------------------------------------
| Validate payload cho man hinh tao don tong quat (GHN, Viettel Post...).
| Ke thua rule GHN hien co de giu tuong thich, bo sung truong chon hang.
*/

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class Tao_Don_Van_ChuyenRequest extends Tao_Don_GhnRequest
{
    /**
     * Bo sung rule carrier de ho tro man hinh tao don da hang.
     */
    public function rules(): array
    {
        // Muc tieu: Dinh nghia bo luat validate phu hop du lieu gui di cua mang validate request.
        return array_merge(parent::rules(), [
            'carrier_name' => ['required', 'string', Rule::in(['GHN', 'VIETTELPOST'])],
            'viettel_groupaddress_id' => ['nullable', 'integer', 'min:1'],
            'viettel_customer_id' => ['nullable', 'integer', 'min:1'],
            'viettel_order_payment' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'viettel_sender_province' => ['nullable', 'integer', 'min:1'],
            'viettel_receiver_province' => ['nullable', 'integer', 'min:1'],
            'viettel_order_service' => ['nullable', 'string', 'max:20'],
            'viettel_product_type' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /**
     * Dat hang mac dinh la GHN de khong vo luong cu.
     */
    protected function prepareForValidation(): void
    {
        // Muc tieu: Chuan hoa input truoc validate theo quy uoc cua mang validate request.
        parent::prepareForValidation();

        $this->merge([
            'carrier_name' => strtoupper((string) $this->input('carrier_name', 'GHN')),
            'viettel_order_payment' => (int) $this->input('viettel_order_payment', 3),
            'viettel_order_service' => (string) $this->input('viettel_order_service', 'PHS'),
            'viettel_product_type' => (string) $this->input('viettel_product_type', 'HH'),
        ]);
    }
}




