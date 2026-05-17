<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Services/Cod_AutoReconciliationService.php
| - Buoc 1: Nhan input nghiep vu tu controller/command.
| - Buoc 2: Chuan hoa du lieu va chon nhanh luong xu ly theo carrier/module.
| - Buoc 3: Goi API doi tac hoac thao tac DB thong qua gateway/model.
| - Buoc 4: Chuan hoa ket qua dau ra de lop goi su dung on dinh.
*/

namespace App\Services;

use App\Models\Cod_Reconciliation;
use App\Models\Order;

class Cod_AutoReconciliationService
{
    /**
     * Tu dong doi soat cac don da giao.
     *
     * @return array{processed:int,created:int,updated:int,pending:int,failed:int,skipped:int}
     */
    public function reconcileDeliveredOrders(int $limit = 200, bool $onlyMissing = false, ?int $ownerUserId = null, ?array $managedCarrierIds = null): array
    {
        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'pending' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $query = Order::query()
            ->with(['hangVanChuyen'])
            ->whereIn('trang_thai', ['da_giao', 'dang_van_chuyen'])
            ->whereNotNull('ma_tracking')
            ->where('ma_tracking', '!=', '')
            ->orderByDesc('ma_don_hang');

        if ($ownerUserId !== null) {
            $query->where('ma_nguoi_dung', $ownerUserId);
        }

        if (is_array($managedCarrierIds)) {
            if ($managedCarrierIds === []) {
                return $summary;
            }

            $query->whereHas('externalRouteBills', function ($billQuery) use ($managedCarrierIds): void {
                $billQuery->whereIn('ma_nha_xe', $managedCarrierIds);
            });
        }

        if ($onlyMissing) {
            $query->whereDoesntHave('codReconciliations');
        }

        $orders = $query->limit(max(1, $limit))->get();

        foreach ($orders as $order) {
            if (! $order instanceof Order) {
                $summary['skipped']++;
                continue;
            }

            $summary['processed']++;

            $carrier = $order->hangVanChuyen;
            if (! $carrier) {
                $summary['skipped']++;
                continue;
            }

            try {
                $result = $this->resolveActualCod($order);
            } catch (\Throwable $exception) {
                $summary['failed']++;
                continue;
            }

            $expected = (float) $order->tien_cod;
            $received = (float) $result['received'];
            $difference = $received - $expected;

            $status = $result['status'] === 'auto'
                ? $this->resolveStatus($difference)
                : 'cho_xac_nhan';

            $record = Cod_Reconciliation::query()->firstOrNew([
                'ma_don_hang' => $order->ma_don_hang,
                'ma_hang_van_chuyen' => $carrier->ma_hang_van_chuyen,
            ]);

            $isNewRecord = ! $record->exists;

            $record->fill([
                'cod_ky_vong' => $expected,
                'cod_thuc_nhan' => $received,
                'chenhlech' => $difference,
                'ngay_doi_soat' => now(),
                'trang_thai' => $status,
            ]);
            $record->save();

            if ($isNewRecord) {
                $summary['created']++;
            } else {
                $summary['updated']++;
            }

            if ($status === 'cho_xac_nhan') {
                $summary['pending']++;
            }
        }

        return $summary;
    }

    /**
     * Truy van API hang de lay COD thuc nhan.
     *
     * @return array{received:float,status:string}
     */
    private function resolveActualCod(Order $order): array
    {
        $carrier = $order->hangVanChuyen;
        $expected = (float) $order->tien_cod;

        if (! $carrier || trim((string) $order->ma_tracking) === '') {
            return [
                'received' => $expected,
                'status' => 'pending',
            ];
        }

        $gateway = app(Carrier_GatewayService::class);

        $response = $gateway->trackShipment(
            (string) $carrier->ten_hang,
            (string) $order->ma_tracking,
            $carrier->api_token,
            $carrier->shop_id !== null ? (int) $carrier->shop_id : null,
            data_get((array) $carrier->config_json, 'base_url')
                ?? data_get((array) $carrier->config_json, 'api_url')
        );

        if (! (bool) data_get($response, 'ok', false)) {
            return [
                'received' => $expected,
                'status' => 'pending',
            ];
        }

        $received = $this->extractReceivedCodAmount((array) data_get($response, 'data', []));

        if ($received === null) {
            return [
                'received' => $expected,
                'status' => 'pending',
            ];
        }

        return [
            'received' => $received,
            'status' => 'auto',
        ];
    }

    /**
     * Tim gia tri COD thuc nhan tu payload API theo bo key pho bien.
     */
    private function extractReceivedCodAmount(array $payload): ?float
    {
        $candidateKeys = [
            'cod_amount_collect',
            'codamountcollect',
            'cod_thuc_nhan',
            'cod_thu_ho',
            'cod_collect',
            'money_collection',
            'moneycollection',
            'money_collect',
            'thu_ho',
            'money_collected',
        ];

        return $this->findNumericByKeys($payload, $candidateKeys);
    }

    /**
     * Duyet de quy mang/object de tim key trung bo key va co gia tri so.
     */
    private function findNumericByKeys(array $data, array $candidateKeys): ?float
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nested = $this->findNumericByKeys($value, $candidateKeys);
                if ($nested !== null) {
                    return $nested;
                }
            }

            $normalizedKey = strtolower((string) preg_replace('/[^a-z0-9]/i', '', (string) $key));

            if (! in_array($normalizedKey, $candidateKeys, true)) {
                continue;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }

            if (is_string($value)) {
                $normalizedValue = str_replace([',', ' '], '', $value);
                if (is_numeric($normalizedValue)) {
                    return (float) $normalizedValue;
                }
            }
        }

        return null;
    }

    /**
     * Map chenh lech COD sang trang thai nghiep vu.
     */
    private function resolveStatus(float $difference): string
    {
        if (abs($difference) < 1) {
            return 'khop';
        }

        if ($difference > 0) {
            return 'du';
        }

        return 'thieu';
    }
}



