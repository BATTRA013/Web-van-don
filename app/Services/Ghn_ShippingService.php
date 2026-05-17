<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/Ghn_ShippingService.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

/*
|--------------------------------------------------------------------------
| SERVICE KET NOI GHN
|--------------------------------------------------------------------------
| Gom logic goi API GHN: test ket noi, tao van don, theo doi trang thai.
| Controller Don_HangController se goi service nay khi xu ly luong GHN.
*/

namespace App\Services;

use App\Models\Hang_Van_Chuyen;

class Ghn_ShippingService extends ShippingService
{
    /**
     * Inject gateway core de dung chung logic ket noi.
     */
    public function __construct(
        private readonly Carrier_GatewayService $gateway,
    ) {
        // Muc tieu: Xu ly nghiep vu ham __construct trong mang kenh ket noi GHN.
    }

    /**
     * Lay token/shop_id mac dinh tu DB truoc, neu khong co thi fallback sang config .env.
     */
    public function getDefaultCredentials(): array
    {
        // Muc tieu: Lay credential mac dinh phuc vu mang kenh ket noi GHN.
        return $this->gateway->getDefaultCredentials('GHN');
    }

    /**
     * Luu nhanh token/shop_id GHN vao bang hang_van_chuyen.
     */
    public function saveCredentials(string $token, ?int $shopId = null): Hang_Van_Chuyen
    {
        // Muc tieu: Luu du lieu nghiep vu theo quy tac cua mang kenh ket noi GHN.
        return $this->gateway->saveGhnCredentials($token, $shopId);
    }

    /**
     * Goi endpoint lay danh sach shop de kiem tra token co hop le hay khong.
     */
    public function testConnection(?string $token = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Kiem tra ket noi he thong doi tac phuc vu mang kenh ket noi GHN.
        return $this->gateway->testConnection('GHN', $token, $baseUrl);
    }

    /**
     * Tao van don GHN tu payload da duoc controller build.
     */
    public function createShipment(array $payload): array
    {
        // Muc tieu: Dieu phoi tao van don theo hang van chuyen trong mang kenh ket noi GHN.
        return $this->gateway->createShipment('GHN', $payload);
    }

    /**
     * Kiem tra ward co thuoc district GHN hay khong.
     * null = khong the xac minh (thieu credential/loi mang/API), true/false = ket qua xac minh.
     */
    public function isValidWardForDistrict(int $districtId, string $wardCode, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang kenh ket noi GHN.
        return $this->gateway->isValidGhnWardForDistrict($districtId, $wardCode, $token, $shopId, $baseUrl);
    }

    /**
     * Lay danh sach district GHN cho combobox tren form.
     */
    public function listDistricts(?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang kenh ket noi GHN.
        return $this->gateway->listGhnDistricts($token, $shopId, $baseUrl);
    }

    /**
     * Lay danh sach province GHN cho combobox tren form.
     */
    public function listProvinces(?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang kenh ket noi GHN.
        return $this->gateway->listGhnProvinces($token, $shopId, $baseUrl);
    }

    /**
     * Lay danh sach district theo province GHN cho combobox tren form.
     */
    public function listDistrictsByProvince(int $provinceId, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang kenh ket noi GHN.
        return $this->gateway->listGhnDistrictsByProvince($provinceId, $token, $shopId, $baseUrl);
    }

    /**
     * Lay danh sach ward theo district GHN cho combobox tren form.
     */
    public function listWardsByDistrict(int $districtId, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang kenh ket noi GHN.
        return $this->gateway->listGhnWardsByDistrict($districtId, $token, $shopId, $baseUrl);
    }

    /**
     * Lay chi tiet/trang thai van don theo ma tracking.
     */
    public function trackShipment(string $trackingCode, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Theo doi trang thai van don theo ma van don trong mang kenh ket noi GHN.
        return $this->gateway->trackShipment('GHN', $trackingCode, $token, $shopId, $baseUrl);
    }
}




