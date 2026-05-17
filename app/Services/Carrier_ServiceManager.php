<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/Carrier_ServiceManager.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

/*
|--------------------------------------------------------------------------
| SERVICE MANAGER CHO DA HANG VAN CHUYEN
|--------------------------------------------------------------------------
| Dieu phoi adapter theo ten hang (GHN, Viettel Post...).
| Giup controller khong can viet logic if/else phu thuoc tung hang.
*/

namespace App\Services;

use Illuminate\Support\Str;

class Carrier_ServiceManager
{
    /**
     * Inject gateway trung tam de manager chi con vai tro dieu phoi.
     */
    public function __construct(
        private readonly Carrier_GatewayService $gateway,
    ) {
        // Muc tieu: Xu ly nghiep vu ham __construct trong mang dieu phoi da hang van chuyen.
    }

    /**
     * Danh sach ten hang duoc ho tro boi he thong.
     */
    public function supportedCarriers(): array
    {
        // Muc tieu: Tra ve danh sach hang van chuyen duoc he thong ho tro trong mang dieu phoi da hang van chuyen.
        return ['GHN', 'VIETTELPOST'];
    }

    /**
     * Chuan hoa ten hang de resolve adapter on dinh.
     */
    public function normalizeCarrierName(string $carrierName): string
    {
        // Muc tieu: Chuan hoa ten hang van chuyen de dieu huong dung luong trong mang dieu phoi da hang van chuyen.
        $normalized = Str::of($carrierName)
            ->trim()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->toString();

        return $normalized !== '' ? $normalized : 'GHN';
    }

    /**
     * Lay credential mac dinh theo tung hang.
     */
    public function getDefaultCredentials(string $carrierName): array
    {
        // Muc tieu: Lay credential mac dinh phuc vu mang dieu phoi da hang van chuyen.
        return $this->gateway->getDefaultCredentials($this->normalizeCarrierName($carrierName));
    }

    /**
     * Test ket noi theo adapter cua hang duoc chi dinh.
     */
    public function testConnection(string $carrierName, array $credentials = []): array
    {
        // Muc tieu: Kiem tra ket noi he thong doi tac phuc vu mang dieu phoi da hang van chuyen.
        $normalized = $this->normalizeCarrierName($carrierName);

        if (! in_array($normalized, $this->supportedCarriers(), true)) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Hãng vận chuyển chưa được hỗ trợ: '.$carrierName,
                'data' => null,
            ];
        }

        return $this->gateway->testConnection(
            $normalized,
            $credentials['token'] ?? null,
            $credentials['base_url'] ?? null
        );
    }
}




