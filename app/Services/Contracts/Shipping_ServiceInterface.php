<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/Contracts/Shipping_ServiceInterface.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

/*
|--------------------------------------------------------------------------
| HOP DONG SERVICE VAN CHUYEN
|--------------------------------------------------------------------------
| Interface dinh nghia bo ham chung cho moi hang van chuyen.
| Neu them hang moi (GHTK, Viettel Post...), service moi can implement bo ham nay.
*/

namespace App\Services\Contracts;

interface Shipping_ServiceInterface
{
    /**
     * Tao van don tren he thong van chuyen ben thu 3.
     */
    public function createShipment(array $payload): array;

    /**
     * Theo doi trang thai van don theo ma tracking.
     */
    public function trackShipment(string $trackingCode): array;

    /**
     * Tinh phi van chuyen dua tren payload dau vao.
     */
    public function calculateShippingFee(array $payload): float;
}



