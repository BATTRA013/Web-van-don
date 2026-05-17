<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/ShippingService.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

/*
|--------------------------------------------------------------------------
| ABSTRACT SERVICE VAN CHUYEN
|--------------------------------------------------------------------------
| Lop nen cho cac service hang van chuyen.
| Cac ham chua override se nem loi de bat buoc lop con phai tu cai dat.
*/

namespace App\Services;

use App\Services\Contracts\Shipping_ServiceInterface;
use LogicException;

abstract class ShippingService implements Shipping_ServiceInterface
{
    /**
     * Lop con bat buoc override de tao van don.
     */
    public function createShipment(array $payload): array
    {
        // Muc tieu: Dinh nghia thao tac tao van don cho tung hang van chuyen.
        throw new LogicException('Method createShipment() must be implemented by concrete shipping provider.');
    }

    /**
     * Lop con bat buoc override de theo doi van don.
     */
    public function trackShipment(string $trackingCode): array
    {
        // Muc tieu: Dinh nghia thao tac theo doi trang thai van don.
        throw new LogicException('Method trackShipment() must be implemented by concrete shipping provider.');
    }

    /**
     * Lop con bat buoc override de tinh phi van chuyen.
     */
    public function calculateShippingFee(array $payload): float
    {
        // Muc tieu: Dinh nghia thao tac tinh phi van chuyen theo du lieu dau vao.
        throw new LogicException('Method calculateShippingFee() must be implemented by concrete shipping provider.');
    }
}




