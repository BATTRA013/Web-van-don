<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/Carrier_GatewayService.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

/*
|--------------------------------------------------------------------------
| SERVICE KET NOI DA HANG VAN CHUYEN (CORE)
|--------------------------------------------------------------------------
| Gom toan bo logic ket noi GHN + Viettel Post vao mot noi.
| Cac service theo tung hang chi dong vai tro wrapper de tuong thich nguoc.
*/

namespace App\Services;

use App\Models\Hang_Van_Chuyen;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Carrier_GatewayService
{
    private const GHN_DEV_BASE_URL = 'https://dev-online-gateway.ghn.vn';
    private const GHN_PROD_BASE_URL = 'https://online-gateway.ghn.vn';
    private const GHN_SHOP_LIST_ENDPOINT = '/shiip/public-api/v2/shop/all';
    private const GHN_CREATE_ORDER_ENDPOINT = '/shiip/public-api/v2/shipping-order/create';
    private const GHN_DETAIL_ORDER_ENDPOINT = '/shiip/public-api/v2/shipping-order/detail';
    private const GHN_PROVINCE_LIST_ENDPOINT = '/shiip/public-api/master-data/province';
    private const GHN_WARD_LIST_ENDPOINT = '/shiip/public-api/master-data/ward';

    private const VTP_BASE_URL = 'https://partner.viettelpost.vn';
    private const VTP_TEST_ENDPOINT = '/v2/user/listInventory';
    private const VTP_CREATE_ORDER_ENDPOINT = '/v2/order/createOrder';
    private const VTP_TRACK_ORDER_ENDPOINT = '/api/v2/order/getOrderInfo';
    private const VTP_TRACK_ORDER_FALLBACK_ENDPOINT = '/v2/order/getOrderInfo';
    private const VTP_LOGIN_ENDPOINT = '/v2/user/Login';

    /**
     * Lay credential mac dinh theo hang van chuyen.
     */
    public function getDefaultCredentials(string $carrierName): array
    {
        // Muc tieu: Lay credential mac dinh phuc vu mang tich hop API da hang van chuyen.
        $carrier = $this->getStoredCarrierConfig($carrierName);
        $configJson = (array) ($carrier?->config_json ?? []);
        $normalized = $this->normalizeCarrierName($carrierName);

        if ($normalized === 'GHN') {
            return [
                'token' => $carrier?->api_token ?: config('services.ghn.token'),
                'shop_id' => $carrier?->shop_id ?: config('services.ghn.shop_id'),
                'base_url' => $this->resolveGhnBaseUrlFromCarrier($carrier),
            ];
        }

        return [
            'token' => $carrier?->api_token ?: config('services.viettel_post.token'),
            'shop_id' => $carrier?->shop_id ?: config('services.viettel_post.shop_id'),
            'base_url' => $this->normalizeViettelBaseUrl((string) ($configJson['api_url'] ?? config('services.viettel_post.base_url', self::VTP_BASE_URL))),
            'username' => $configJson['username'] ?? config('services.viettel_post.username'),
            'password' => $configJson['password'] ?? config('services.viettel_post.password'),
            'customer_id' => $configJson['customer_id'] ?? config('services.viettel_post.customer_id'),
            'sender_groupaddress_id' => $configJson['sender_groupaddress_id'] ?? config('services.viettel_post.sender_groupaddress_id'),
        ];
    }

    /**
     * Test ket noi theo hang.
     */
    public function testConnection(string $carrierName, ?string $token = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Kiem tra ket noi he thong doi tac phuc vu mang tich hop API da hang van chuyen.
        $normalized = $this->normalizeCarrierName($carrierName);

        if ($normalized === 'GHN') {
            return $this->testGhnConnection($token, $baseUrl);
        }

        return $this->testViettelConnection($token, $baseUrl);
    }

    /**
     * Tao van don theo hang.
     */
    public function createShipment(string $carrierName, array $payload): array
    {
        // Muc tieu: Dieu phoi tao van don theo hang van chuyen trong mang tich hop API da hang van chuyen.
        $normalized = $this->normalizeCarrierName($carrierName);

        if ($normalized === 'GHN') {
            return $this->createGhnShipment($payload);
        }

        return $this->createViettelShipment($payload);
    }

    /**
     * Theo doi van don theo hang.
     */
    public function trackShipment(string $carrierName, string $trackingCode, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Theo doi trang thai van don theo ma van don trong mang tich hop API da hang van chuyen.
        $normalized = $this->normalizeCarrierName($carrierName);

        if ($normalized === 'GHN') {
            return $this->trackGhnShipment($trackingCode, $token, $shopId, $baseUrl);
        }

        return $this->trackViettelShipment($trackingCode, $token, $baseUrl);
    }

    /**
     * Login Viettel Post lay token moi.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function loginViettel(string $username, string $password, ?string $baseUrl = null): array
    {
        // Muc tieu: Xu ly nghiep vu ham loginViettel trong mang tich hop API da hang van chuyen.
        $response = Http::baseUrl($this->normalizeBaseUrl((string) ($baseUrl ?: config('services.viettel_post.base_url', self::VTP_BASE_URL)), self::VTP_BASE_URL))
            ->acceptJson()
            ->timeout(20)
            ->post(self::VTP_LOGIN_ENDPOINT, [
                'USERNAME' => $username,
                'PASSWORD' => $password,
            ])
            ->throw();

        return (array) $response->json();
    }

    /**
     * Kiem tra ward co thuoc district Viettel hay khong.
     * null = khong the xac minh (loi mang/API), true/false = ket qua xac minh.
     */
    public function isValidViettelWardForDistrict(int $districtId, string $wardId, ?string $baseUrl = null): ?bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang tich hop API da hang van chuyen.
        if ($districtId <= 0 || trim($wardId) === '') {
            return null;
        }

        try {
            $response = Http::baseUrl($this->normalizeViettelBaseUrl((string) ($baseUrl ?: config('services.viettel_post.base_url', self::VTP_BASE_URL))))
                ->acceptJson()
                ->timeout(15)
                ->get('/v2/categories/listWards', [
                    'districtId' => $districtId,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $wards = (array) data_get($response->json(), 'data', []);
        $wardIdTrimmed = trim($wardId);

        foreach ($wards as $ward) {
            if ((string) data_get($ward, 'WARDS_ID') === $wardIdTrimmed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tim province_id Viettel theo ten tinh/thanh (khong phan biet dau/cach viet).
     */
    public function findViettelProvinceIdByName(string $provinceName, ?string $baseUrl = null): ?int
    {
        $normalizedTarget = $this->normalizeGeoName($provinceName);

        if ($normalizedTarget === '') {
            return null;
        }

        try {
            $response = Http::baseUrl($this->normalizeViettelBaseUrl((string) ($baseUrl ?: config('services.viettel_post.base_url', self::VTP_BASE_URL))))
                ->acceptJson()
                ->timeout(15)
                ->get('/v2/categories/listProvince');
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $provinces = (array) data_get($response->json(), 'data', []);

        foreach ($provinces as $province) {
            $candidateName = (string) data_get($province, 'PROVINCE_NAME', '');
            if ($this->geoNameMatches($normalizedTarget, $candidateName)) {
                $provinceId = (int) data_get($province, 'PROVINCE_ID', 0);
                return $provinceId > 0 ? $provinceId : null;
            }
        }

        return null;
    }

    /**
     * Tim district_id Viettel theo ten quan/huyen trong 1 tinh.
     */
    public function findViettelDistrictIdByName(int $provinceId, string $districtName, ?string $baseUrl = null): ?int
    {
        if ($provinceId <= 0) {
            return null;
        }

        $normalizedTarget = $this->normalizeGeoName($districtName);

        if ($normalizedTarget === '') {
            return null;
        }

        try {
            $response = Http::baseUrl($this->normalizeViettelBaseUrl((string) ($baseUrl ?: config('services.viettel_post.base_url', self::VTP_BASE_URL))))
                ->acceptJson()
                ->timeout(15)
                ->get('/v2/categories/listDistrict', [
                    'provinceId' => $provinceId,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $districts = (array) data_get($response->json(), 'data', []);

        foreach ($districts as $district) {
            $candidateName = (string) data_get($district, 'DISTRICT_NAME', '');
            if ($this->geoNameMatches($normalizedTarget, $candidateName)) {
                $districtId = (int) data_get($district, 'DISTRICT_ID', 0);
                return $districtId > 0 ? $districtId : null;
            }
        }

        return null;
    }

    /**
     * Tim ward_id Viettel theo ten phuong/xa trong 1 district.
     */
    public function findViettelWardIdByName(int $districtId, string $wardName, ?string $baseUrl = null): ?string
    {
        if ($districtId <= 0) {
            return null;
        }

        $normalizedTarget = $this->normalizeGeoName($wardName);

        if ($normalizedTarget === '') {
            return null;
        }

        try {
            $response = Http::baseUrl($this->normalizeViettelBaseUrl((string) ($baseUrl ?: config('services.viettel_post.base_url', self::VTP_BASE_URL))))
                ->acceptJson()
                ->timeout(15)
                ->get('/v2/categories/listWards', [
                    'districtId' => $districtId,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $wards = (array) data_get($response->json(), 'data', []);

        foreach ($wards as $ward) {
            $candidateName = (string) data_get($ward, 'WARDS_NAME', '');
            if ($this->geoNameMatches($normalizedTarget, $candidateName)) {
                $wardId = trim((string) data_get($ward, 'WARDS_ID', ''));
                return $wardId !== '' ? $wardId : null;
            }
        }

        return null;
    }

    /**
     * Kiem tra ward co thuoc district GHN hay khong.
     * null = khong the xac minh (thieu credential/loi mang/API), true/false = ket qua xac minh.
     */
    public function isValidGhnWardForDistrict(int $districtId, string $wardCode, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?bool
    {
        // Muc tieu: Kiem tra dieu kien nghiep vu trong mang tich hop API da hang van chuyen.
        if ($districtId <= 0 || trim($wardCode) === '') {
            return null;
        }

        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentialFromPayload([
            '__token' => $token,
            '__shop_id' => $shopId,
            '__base_url' => $baseUrl,
        ]);

        if (! $resolvedToken) {
            return null;
        }

        try {
            $response = $this->sendGhnWithFallback(
                'post',
                self::GHN_WARD_LIST_ENDPOINT,
                (string) $resolvedToken,
                $resolvedShopId ? (int) $resolvedShopId : null,
                ['district_id' => $districtId],
                $resolvedBaseUrl
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $wardCodeTrimmed = trim($wardCode);
        $wards = (array) data_get($response->json(), 'data', []);

        foreach ($wards as $ward) {
            $candidates = [
                (string) data_get($ward, 'WardCode'),
                (string) data_get($ward, 'ward_code'),
                (string) data_get($ward, 'Code'),
            ];

            foreach ($candidates as $candidate) {
                if (trim($candidate) !== '' && trim($candidate) === $wardCodeTrimmed) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Lay danh sach district GHN de phuc vu combobox tren form.
     * null = khong the tai du lieu do loi ket noi/credential.
     */
    public function listGhnDistricts(?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang tich hop API da hang van chuyen.
        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentialFromPayload([
            '__token' => $token,
            '__shop_id' => $shopId,
            '__base_url' => $baseUrl,
        ]);

        if (! $resolvedToken) {
            return null;
        }

        try {
            // Metadata endpoint thuong khong can ShopId; bo ShopId de tranh lech token-shop.
            $provinceResponse = $this->sendGhnWithFallback(
                'post',
                self::GHN_PROVINCE_LIST_ENDPOINT,
                (string) $resolvedToken,
                null,
                [],
                $resolvedBaseUrl
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $provinceResponse->successful()) {
            return null;
        }

        $districts = [];
        $provinces = (array) data_get($provinceResponse->json(), 'data', []);

        foreach ($provinces as $province) {
            $provinceId = (int) data_get($province, 'ProvinceID', data_get($province, 'province_id', 0));
            if ($provinceId <= 0) {
                continue;
            }

            try {
                $districtResponse = $this->sendGhnWithFallback(
                    'post',
                    '/shiip/public-api/master-data/district',
                    (string) $resolvedToken,
                    null,
                    ['province_id' => $provinceId],
                    $resolvedBaseUrl
                );
            } catch (ConnectionException) {
                continue;
            }

            if (! $districtResponse->successful()) {
                continue;
            }

            $rows = (array) data_get($districtResponse->json(), 'data', []);

            foreach ($rows as $row) {
                $id = (int) data_get($row, 'DistrictID', data_get($row, 'district_id', 0));
                $name = trim((string) data_get($row, 'DistrictName', data_get($row, 'name', '')));

                if ($id <= 0 || $name === '') {
                    continue;
                }

                $districts[$id] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
        }

        // Fallback cho mot so moi truong API co the tra district khi payload rong.
        if ($districts === []) {
            try {
                $fallbackResponse = $this->sendGhnWithFallback(
                    'post',
                    '/shiip/public-api/master-data/district',
                    (string) $resolvedToken,
                    null,
                    [],
                    $resolvedBaseUrl
                );
            } catch (ConnectionException) {
                return null;
            }

            if (! $fallbackResponse->successful()) {
                return null;
            }

            $rows = (array) data_get($fallbackResponse->json(), 'data', []);
            foreach ($rows as $row) {
                $id = (int) data_get($row, 'DistrictID', data_get($row, 'district_id', 0));
                $name = trim((string) data_get($row, 'DistrictName', data_get($row, 'name', '')));

                if ($id <= 0 || $name === '') {
                    continue;
                }

                $districts[$id] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
        }

        $districts = array_values($districts);
        usort($districts, function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $districts;
    }

    /**
     * Lay danh sach province GHN de phuc vu combobox tren form.
     * null = khong the tai du lieu do loi ket noi/credential.
     */
    public function listGhnProvinces(?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang tich hop API da hang van chuyen.
        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentialFromPayload([
            '__token' => $token,
            '__shop_id' => $shopId,
            '__base_url' => $baseUrl,
        ]);

        if (! $resolvedToken) {
            return null;
        }

        try {
            $response = $this->sendGhnWithFallback(
                'post',
                self::GHN_PROVINCE_LIST_ENDPOINT,
                (string) $resolvedToken,
                null,
                [],
                $resolvedBaseUrl
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $rows = (array) data_get($response->json(), 'data', []);
        $provinces = [];

        foreach ($rows as $row) {
            $id = (int) data_get($row, 'ProvinceID', data_get($row, 'province_id', 0));
            $name = trim((string) data_get($row, 'ProvinceName', data_get($row, 'name', '')));

            if ($id <= 0 || $name === '') {
                continue;
            }

            $provinces[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        usort($provinces, function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $provinces;
    }

    /**
     * Lay district theo province GHN.
     * null = khong the tai du lieu do loi ket noi/credential.
     */
    public function listGhnDistrictsByProvince(int $provinceId, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang tich hop API da hang van chuyen.
        if ($provinceId <= 0) {
            return [];
        }

        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentialFromPayload([
            '__token' => $token,
            '__shop_id' => $shopId,
            '__base_url' => $baseUrl,
        ]);

        if (! $resolvedToken) {
            return null;
        }

        try {
            $response = $this->sendGhnWithFallback(
                'post',
                '/shiip/public-api/master-data/district',
                (string) $resolvedToken,
                null,
                ['province_id' => $provinceId],
                $resolvedBaseUrl
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $rows = (array) data_get($response->json(), 'data', []);
        $districts = [];

        foreach ($rows as $row) {
            $id = (int) data_get($row, 'DistrictID', data_get($row, 'district_id', 0));
            $name = trim((string) data_get($row, 'DistrictName', data_get($row, 'name', '')));

            if ($id <= 0 || $name === '') {
                continue;
            }

            $districts[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        usort($districts, function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $districts;
    }

    /**
     * Lay danh sach ward theo district GHN de phuc vu combobox tren form.
     * null = khong the tai du lieu do loi ket noi/credential.
     */
    public function listGhnWardsByDistrict(int $districtId, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): ?array
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang tich hop API da hang van chuyen.
        if ($districtId <= 0) {
            return [];
        }

        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentialFromPayload([
            '__token' => $token,
            '__shop_id' => $shopId,
            '__base_url' => $baseUrl,
        ]);

        if (! $resolvedToken) {
            return null;
        }

        try {
            $response = $this->sendGhnWithFallback(
                'post',
                self::GHN_WARD_LIST_ENDPOINT,
                (string) $resolvedToken,
                null,
                ['district_id' => $districtId],
                $resolvedBaseUrl
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $rows = (array) data_get($response->json(), 'data', []);
        $wards = [];

        foreach ($rows as $row) {
            $code = trim((string) data_get($row, 'WardCode', data_get($row, 'ward_code', data_get($row, 'Code', ''))));
            $name = trim((string) data_get($row, 'WardName', data_get($row, 'ward_name', data_get($row, 'Name', ''))));

            if ($code === '' || $name === '') {
                continue;
            }

            $wards[] = [
                'code' => $code,
                'name' => $name,
            ];
        }

        usort($wards, function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $wards;
    }

    /**
     * Luu nhanh credential GHN de tuong thich voi luong cu.
     */
    public function saveGhnCredentials(string $token, ?int $shopId = null): Hang_Van_Chuyen
    {
        // Muc tieu: Luu du lieu nghiep vu theo quy tac cua mang tich hop API da hang van chuyen.
        $carrier = Hang_Van_Chuyen::query()->firstOrNew([
            'ten_hang' => 'GHN',
        ]);

        $carrier->api_token = $token;
        $carrier->shop_id = $shopId ? (string) $shopId : null;
        $carrier->moi_truong = $carrier->moi_truong ?? (str_contains((string) config('services.ghn.base_url'), 'dev') ? 0 : 1);
        $carrier->config_json = array_merge((array) $carrier->config_json, [
            'base_url' => config('services.ghn.base_url'),
            'saved_at' => now()->toDateTimeString(),
        ]);
        $carrier->save();

        return $carrier;
    }

    /**
     * Chuan hoa ten hang ve key noi bo (GHN/VIETTELPOST).
     */
    private function normalizeCarrierName(string $carrierName): string
    {
        // Muc tieu: Chuan hoa ten hang van chuyen de dieu huong dung luong trong mang tich hop API da hang van chuyen.
        $normalized = Str::of($carrierName)
            ->trim()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->toString();

        return $normalized !== '' ? $normalized : 'GHN';
    }

    /**
     * Goi endpoint test ket noi GHN.
     */
    private function testGhnConnection(?string $token = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Xu ly nghiep vu ham testGhnConnection trong mang tich hop API da hang van chuyen.
        $defaults = $this->getDefaultCredentials('GHN');
        $resolvedToken = $token ?: ($defaults['token'] ?? null);

        if (! $resolvedToken) {
            return $this->response(false, 422, 'Thiếu GHN token.');
        }

        try {
            $response = $this->sendGhnWithFallback('get', self::GHN_SHOP_LIST_ENDPOINT, $resolvedToken, null, [], $baseUrl);
        } catch (ConnectionException $exception) {
            return $this->ghnConnectionErrorResponse($exception);
        }

        return $this->fromHttpResponse($response->successful(), $response->status(), $response->json('message'), $response->json());
    }

    /**
     * Tao van don GHN tu payload da duoc bo sung credential.
     */
    private function createGhnShipment(array $payload): array
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang tich hop API da hang van chuyen.
        [$token, $shopId, $baseUrl] = $this->resolveGhnCredentialFromPayload($payload);

        unset($payload['__token'], $payload['__shop_id'], $payload['__base_url']);

        if (! $token || ! $shopId) {
            return $this->response(false, 422, 'Thiếu GHN token hoặc GHN shop id.');
        }

        try {
            $response = $this->sendGhnWithFallback('post', self::GHN_CREATE_ORDER_ENDPOINT, $token, (int) $shopId, $payload, $baseUrl);
        } catch (ConnectionException $exception) {
            return $this->ghnConnectionErrorResponse($exception);
        }

        return $this->fromHttpResponse($response->successful(), $response->status(), $response->json('message'), $response->json());
    }

    /**
     * Theo doi van don GHN bang order_code.
     */
    private function trackGhnShipment(string $trackingCode, ?string $token = null, ?int $shopId = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang tich hop API da hang van chuyen.
        [$resolvedToken, $resolvedShopId, $resolvedBaseUrl] = $this->resolveGhnCredentialFromPayload([
            '__token' => $token,
            '__shop_id' => $shopId,
            '__base_url' => $baseUrl,
        ]);

        if (! $resolvedToken) {
            return $this->response(false, 422, 'Thiếu GHN token để theo dõi vận đơn.');
        }

        try {
            $response = $this->sendGhnWithFallback(
                'post',
                self::GHN_DETAIL_ORDER_ENDPOINT,
                $resolvedToken,
                $resolvedShopId ? (int) $resolvedShopId : null,
                ['order_code' => $trackingCode],
                $resolvedBaseUrl
            );
        } catch (ConnectionException $exception) {
            return $this->ghnConnectionErrorResponse($exception);
        }

        return $this->fromHttpResponse($response->successful(), $response->status(), $response->json('message'), $response->json());
    }

    /**
     * Test ket noi Viettel Post bang endpoint inventory.
     */
    private function testViettelConnection(?string $token = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Xu ly nghiep vu ham testViettelConnection trong mang tich hop API da hang van chuyen.
        $defaults = $this->getDefaultCredentials('VIETTELPOST');
        $resolvedToken = trim((string) ($token ?: ($defaults['token'] ?? '')));

        if ($resolvedToken === '') {
            return $this->response(false, 422, 'Thiếu token Viettel Post.');
        }

        try {
            $response = $this->viettelClient($resolvedToken, $baseUrl)->get(self::VTP_TEST_ENDPOINT);
        } catch (ConnectionException $exception) {
            return $this->response(false, 503, 'Không thể kết nối Viettel Post gateway: '.$exception->getMessage());
        }

        return $this->fromHttpResponse($response->successful(), $response->status(), $response->json('message'), $this->normalizeResponseData($response));
    }

    /**
     * Tao van don Viettel Post.
     */
    private function createViettelShipment(array $payload): array
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang tich hop API da hang van chuyen.
        $defaults = $this->getDefaultCredentials('VIETTELPOST');
        $token = trim((string) ($payload['__token'] ?? ($defaults['token'] ?? '')));
        $baseUrl = $this->normalizeViettelBaseUrl((string) ($payload['__base_url'] ?? ($defaults['base_url'] ?? self::VTP_BASE_URL)));

        unset($payload['__token'], $payload['__base_url']);

        if ($token === '') {
            return $this->response(false, 422, 'Thiếu token Viettel Post để tạo vận đơn.');
        }

        try {
            $response = $this->viettelClient($token, $baseUrl)->post(self::VTP_CREATE_ORDER_ENDPOINT, $payload);
        } catch (ConnectionException $exception) {
            return $this->response(false, 503, 'Không thể kết nối Viettel Post gateway: '.$exception->getMessage());
        }

        return $this->fromHttpResponse($response->successful(), $response->status(), $response->json('message'), $this->normalizeResponseData($response));
    }

    /**
     * Theo doi van don Viettel Post theo ORDER_NUMBER.
     */
    private function trackViettelShipment(string $trackingCode, ?string $token = null, ?string $baseUrl = null): array
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang tich hop API da hang van chuyen.
        $defaults = $this->getDefaultCredentials('VIETTELPOST');
        $resolvedToken = trim((string) ($token ?: ($defaults['token'] ?? '')));

        if ($resolvedToken === '') {
            return $this->response(false, 422, 'Thiếu token Viettel Post để theo dõi vận đơn.');
        }

        try {
            $client = $this->viettelClient($resolvedToken, $baseUrl ?: data_get($defaults, 'base_url'));
            $payload = [
                'TYPE' => 1,
                'ORDER_NUMBER' => $trackingCode,
            ];

            $response = $client->post(self::VTP_TRACK_ORDER_ENDPOINT, $payload);

            // Mot so cum Viettel chi cho phep GET cho endpoint getOrderInfo.
            if ($response->status() === 405) {
                $response = $client->get(self::VTP_TRACK_ORDER_ENDPOINT, $payload);
            }

            // Tuong thich nguoc cho cac cum cu chi expose duong dan /v2.
            if (in_array($response->status(), [404, 405], true)) {
                $response = $client->post(self::VTP_TRACK_ORDER_FALLBACK_ENDPOINT, $payload);

                if ($response->status() === 405) {
                    $response = $client->get(self::VTP_TRACK_ORDER_FALLBACK_ENDPOINT, $payload);
                }
            }
        } catch (ConnectionException $exception) {
            return $this->response(false, 503, 'Không thể kết nối Viettel Post gateway: '.$exception->getMessage());
        }

        return $this->fromHttpResponse($response->successful(), $response->status(), $response->json('message'), $this->normalizeResponseData($response));
    }

    /**
     * Tao HTTP client GHN voi header Token/ShopId.
     */
    private function ghnClient(string $token, ?int $shopId = null, ?string $baseUrl = null): PendingRequest
    {
        // Muc tieu: Xu ly nghiep vu ham ghnClient trong mang tich hop API da hang van chuyen.
        $headers = [
            'Token' => $token,
            'Content-Type' => 'application/json',
        ];

        if ($shopId) {
            $headers['ShopId'] = $shopId;
        }

        return Http::baseUrl($baseUrl ?: config('services.ghn.base_url'))
            ->withHeaders($headers)
            ->acceptJson()
            ->timeout(20);
    }

    /**
     * Tao HTTP client Viettel Post voi header Token.
     */
    private function viettelClient(string $token, ?string $baseUrl = null): PendingRequest
    {
        // Muc tieu: Xu ly nghiep vu ham viettelClient trong mang tich hop API da hang van chuyen.
        return Http::baseUrl($this->normalizeViettelBaseUrl((string) ($baseUrl ?: config('services.viettel_post.base_url', self::VTP_BASE_URL))))
            ->withHeaders([
                'Token' => $token,
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->timeout(20);
    }

    /**
     * Chuan hoa host Viettel: map host cu api.viettelpost.vn sang partner.viettelpost.vn.
     */
    private function normalizeViettelBaseUrl(string $baseUrl): string
    {
        // Muc tieu: Chuan hoa du lieu dau vao de xu ly on dinh trong mang tich hop API da hang van chuyen.
        $normalized = $this->normalizeBaseUrl($baseUrl, self::VTP_BASE_URL);

        if (str_contains(Str::lower($normalized), 'api.viettelpost.vn')) {
            return self::VTP_BASE_URL;
        }

        return $normalized;
    }

    /**
     * @throws ConnectionException
     */
    private function sendGhnWithFallback(string $method, string $endpoint, string $token, ?int $shopId = null, array $payload = [], ?string $baseUrlOverride = null): Response
    {
        // Muc tieu: Xu ly nghiep vu ham sendGhnWithFallback trong mang tich hop API da hang van chuyen.
        $baseUrl = $this->normalizeBaseUrl((string) ($baseUrlOverride ?: config('services.ghn.base_url')), self::GHN_PROD_BASE_URL);

        if ($baseUrlOverride) {
            return $this->sendGhnRequest($method, $endpoint, $token, $shopId, $payload, $baseUrl);
        }

        try {
            return $this->sendGhnRequest($method, $endpoint, $token, $shopId, $payload, $baseUrl);
        } catch (ConnectionException $exception) {
            $fallbackBaseUrl = $this->resolveGhnFallbackBaseUrl($baseUrl);

            if (! $fallbackBaseUrl) {
                throw $exception;
            }

            return $this->sendGhnRequest($method, $endpoint, $token, $shopId, $payload, $fallbackBaseUrl);
        }
    }

    /**
     * Gui request GHN theo method GET/POST.
     */
    private function sendGhnRequest(string $method, string $endpoint, string $token, ?int $shopId, array $payload, string $baseUrl): Response
    {
        // Muc tieu: Xu ly nghiep vu ham sendGhnRequest trong mang tich hop API da hang van chuyen.
        $client = $this->ghnClient($token, $shopId, $baseUrl);

        if ($method === 'get') {
            return $client->get($endpoint);
        }

        return $client->post($endpoint, $payload);
    }

    /**
     * Xac dinh host GHN fallback khi host hien tai loi ket noi.
     */
    private function resolveGhnFallbackBaseUrl(string $baseUrl): ?string
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang tich hop API da hang van chuyen.
        if (str_contains($baseUrl, 'dev-online-gateway.ghn.vn')) {
            return self::GHN_PROD_BASE_URL;
        }

        if (str_contains($baseUrl, 'online-gateway.ghn.vn')) {
            return self::GHN_DEV_BASE_URL;
        }

        return null;
    }

    /**
     * Resolve credential GHN tu payload override hoac defaults.
     */
    private function resolveGhnCredentialFromPayload(array $payload): array
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang tich hop API da hang van chuyen.
        $defaults = $this->getDefaultCredentials('GHN');

        $token = $payload['__token'] ?? ($defaults['token'] ?? null);
        $shopId = $payload['__shop_id'] ?? ($defaults['shop_id'] ?? null);
        $baseUrl = $payload['__base_url'] ?? ($defaults['base_url'] ?? config('services.ghn.base_url'));

        return [$token, $shopId, $this->normalizeBaseUrl((string) $baseUrl, self::GHN_PROD_BASE_URL)];
    }

    /**
     * Doc cau hinh carrier da luu tu DB theo hang.
     */
    private function getStoredCarrierConfig(string $carrierName): ?Hang_Van_Chuyen
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang tich hop API da hang van chuyen.
        if ($this->normalizeCarrierName($carrierName) === 'GHN') {
            $validCarrier = Hang_Van_Chuyen::query()
                ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
                ->where('api_token', '!=', 'pending_token')
                ->whereNotNull('shop_id')
                ->where('shop_id', '!=', '')
                ->latest('ma_hang_van_chuyen')
                ->first();

            if ($validCarrier) {
                return $validCarrier;
            }

            return Hang_Van_Chuyen::query()
                ->whereRaw('LOWER(ten_hang) = ?', ['ghn'])
                ->first();
        }

        return Hang_Van_Chuyen::query()
            ->whereRaw('LOWER(ten_hang) like ?', ['%viettel%'])
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->where('api_token', '!=', 'API_CHUA_CAP')
            ->latest('ma_hang_van_chuyen')
            ->first();
    }

    /**
     * Resolve GHN base URL theo moi_truong cua carrier.
     */
    private function resolveGhnBaseUrlFromCarrier(?Hang_Van_Chuyen $carrier): string
    {
        // Muc tieu: Lay cau hinh du lieu theo thu tu uu tien trong mang tich hop API da hang van chuyen.
        if (! $carrier) {
            return $this->normalizeBaseUrl((string) config('services.ghn.base_url'), self::GHN_PROD_BASE_URL);
        }

        return (int) $carrier->moi_truong === 0 ? self::GHN_DEV_BASE_URL : self::GHN_PROD_BASE_URL;
    }

    /**
     * Chuan hoa base URL, fallback ve gia tri mac dinh neu rong.
     */
    private function normalizeBaseUrl(string $baseUrl, string $defaultBaseUrl): string
    {
        // Muc tieu: Chuan hoa du lieu dau vao de xu ly on dinh trong mang tich hop API da hang van chuyen.
        $trimmed = rtrim(trim($baseUrl), '/');

        return $trimmed !== '' ? $trimmed : $defaultBaseUrl;
    }

    /**
     * Chuan hoa loi ket noi GHN thanh response co nghia nghiep vu.
     */
    private function ghnConnectionErrorResponse(ConnectionException $exception): array
    {
        // Muc tieu: Xu ly nghiep vu ham ghnConnectionErrorResponse trong mang tich hop API da hang van chuyen.
        $message = 'Không thể kết nối GHN gateway: '.$exception->getMessage();

        if (str_contains(Str::lower($message), 'could not resolve host')) {
            $message .= '. Vui lòng kiểm tra GHN_BASE_URL (ưu tiên https://online-gateway.ghn.vn).';
        }

        return $this->response(false, 503, $message);
    }

    /**
     * Chuan hoa response HTTP (co xu ly API tra HTTP 200 nhung body bao loi).
     */
    private function fromHttpResponse(bool $ok, int $status, ?string $message, mixed $data): array
    {
        // Muc tieu: Xu ly nghiep vu ham fromHttpResponse trong mang tich hop API da hang van chuyen.
        $payload = is_array($data) ? $data : [];
        $hasApiErrorFlag = data_get($payload, 'error') === true;
        $apiStatus = (int) data_get($payload, 'status', 0);

        // Nhieu API (dac biet Viettel Post) tra HTTP 200 nhung bao loi trong body.
        if ($hasApiErrorFlag || $apiStatus >= 300) {
            $ok = false;
        }

        $resolvedMessage = $message
            ?? data_get($payload, 'message')
            ?? data_get($payload, 'error_message')
            ?? data_get($payload, 'data.message')
            ?? data_get($payload, 'data.MESSAGE')
            ?? ($ok ? 'Thành công' : 'Yêu cầu thất bại');

        if (! $ok && ($resolvedMessage === 'Yêu cầu thất bại' || trim((string) $resolvedMessage) === '')) {
            $rawBody = trim((string) data_get($payload, '__raw_body', ''));

            if ($rawBody !== '') {
                $resolvedMessage = 'Yêu cầu thất bại: '.$rawBody;

                return $this->response($ok, $status, (string) $resolvedMessage, $data);
            }

            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded && $encoded !== '[]' && $encoded !== '{}') {
                $resolvedMessage = 'Yêu cầu thất bại: '.$encoded;
            }
        }

        return $this->response($ok, $status, (string) $resolvedMessage, $data);
    }

    /**
     * Chuan hoa du lieu response de khong mat thong tin khi API tra body khong phai JSON.
     */
    private function normalizeResponseData(Response $response): array
    {
        // Muc tieu: Chuan hoa du lieu dau vao de xu ly on dinh trong mang tich hop API da hang van chuyen.
        $json = $response->json();

        if (is_array($json)) {
            return $json;
        }

        return [
            '__raw_body' => $response->body(),
        ];
    }

    /**
     * Chuan hoa ten dia gioi de so khop cross-carrier.
     */
    private function normalizeGeoName(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/\b(tinh|thanh pho|tp|quan|huyen|thi xa|thi tran|phuong|xa)\b/', ' ')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    /**
     * So khop ten dia gioi theo chuan hoa, co fallback contains hai chieu.
     */
    private function geoNameMatches(string $normalizedTarget, string $candidateName): bool
    {
        $normalizedCandidate = $this->normalizeGeoName($candidateName);

        if ($normalizedTarget === '' || $normalizedCandidate === '') {
            return false;
        }

        return $normalizedTarget === $normalizedCandidate
            || str_contains($normalizedCandidate, $normalizedTarget)
            || str_contains($normalizedTarget, $normalizedCandidate);
    }

    /**
     * Dong goi ket qua theo schema chung cua service gateway.
     */
    private function response(bool $ok, int $status, string $message, mixed $data = null): array
    {
        // Muc tieu: Xu ly nghiep vu ham response trong mang tich hop API da hang van chuyen.
        return [
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }
}




