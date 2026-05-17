<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/ViettelPost_ShippingService.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

/*
|--------------------------------------------------------------------------
| SERVICE KET NOI VIETTEL POST
|--------------------------------------------------------------------------
| Gom logic goi API Viettel Post: test ket noi, tao van don, theo doi don.
| Duoc dung cho luong da hang ben canh GHN.
*/

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class ViettelPost_ShippingService extends ShippingService
{
    /**
     * Inject gateway core de dung chung transport layer.
     */
    public function __construct(
        private readonly Carrier_GatewayService $gateway,
    ) {
        // Muc tieu: Xu ly nghiep vu ham __construct trong mang kenh ket noi Viettel Post.
    }

    /**
     * Lay token/cau hinh Viettel Post mac dinh tu DB, fallback sang config.
     */
    public function getDefaultCredentials(): array
    {
        // Muc tieu: Lay credential mac dinh phuc vu mang kenh ket noi Viettel Post.
        return $this->gateway->getDefaultCredentials('VIETTELPOST');
    }

    /**
     * Kiem tra token bang endpoint inventory (yeu cau header Token).
     */
    public function testConnection(?string $token = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Kiem tra ket noi he thong doi tac phuc vu mang kenh ket noi Viettel Post.
        return $this->gateway->testConnection('VIETTELPOST', $token, $baseUrl);
    }

    /**
     * Tao van don Viettel Post.
     */
    public function createShipment(array $payload): array
    {
        // Muc tieu: Dieu phoi tao van don theo hang van chuyen trong mang kenh ket noi Viettel Post.
        return $this->gateway->createShipment('VIETTELPOST', $payload);
    }

    /**
     * Theo doi thong tin don theo ma van don Viettel Post.
     */
    public function trackShipment(string $trackingCode): array
    {
        // Muc tieu: Theo doi trang thai van don theo ma van don trong mang kenh ket noi Viettel Post.
        return $this->gateway->trackShipment('VIETTELPOST', $trackingCode);
    }

    /**
     * Chua su dung tinh phi trong pham vi thay doi hien tai.
     */
    public function calculateShippingFee(array $payload): float
    {
        // Muc tieu: Tinh phi van chuyen theo tham so dau vao cua mang kenh ket noi Viettel Post.
        return 0.0;
    }

    /**
     * Dang nhap Viettel Post de lay token moi khi can.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function login(string $username, string $password, ?string $baseUrl = null): array
    {
        // Muc tieu: Xu ly nghiep vu ham login trong mang kenh ket noi Viettel Post.
        return $this->gateway->loginViettel($username, $password, $baseUrl);
    }

    /**
     * Kiem tra ward co thuoc district Viettel hay khong.
     */
    public function isValidWardForDistrict(int $districtId, string $wardId, ?string $baseUrl = null): ?bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang kenh ket noi Viettel Post.
        return $this->gateway->isValidViettelWardForDistrict($districtId, $wardId, $baseUrl);
    }

    /**
     * Tim province_id Viettel theo ten tinh/thanh.
     */
    public function findProvinceIdByName(string $provinceName, ?string $baseUrl = null): ?int
    {
        return $this->gateway->findViettelProvinceIdByName($provinceName, $baseUrl);
    }

    /**
     * Tim district_id Viettel theo ten quan/huyen va province.
     */
    public function findDistrictIdByName(int $provinceId, string $districtName, ?string $baseUrl = null): ?int
    {
        return $this->gateway->findViettelDistrictIdByName($provinceId, $districtName, $baseUrl);
    }

    /**
     * Tim ward_id Viettel theo ten phuong/xa va district.
     */
    public function findWardIdByName(int $districtId, string $wardName, ?string $baseUrl = null): ?string
    {
        return $this->gateway->findViettelWardIdByName($districtId, $wardName, $baseUrl);
    }
}




