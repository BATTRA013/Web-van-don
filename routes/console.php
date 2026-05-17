<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: routes/console.php
| - Buoc 1: Dinh nghia endpoint/command can ho tro.
| - Buoc 2: Gan middleware va anh xa den controller/handler tuong ung.
| - Buoc 3: Thiet lap boundary quyen truy cap theo module nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| ROUTE CHO LENH CONSOLE (ARTISAN)
|--------------------------------------------------------------------------
| File nay dung cho cac lenh chay bang terminal (php artisan ...).
| Khong phuc vu request web truc tiep tu trinh duyet.
*/

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use App\Services\Cod_AutoReconciliationService;

// Lenh demo mac dinh cua Laravel, chay bang: php artisan inspire
Artisan::command('inspire', function () {
    // In mot cau quote ra man hinh terminal.
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lenh precheck truoc demo: kiem tra DB + cac bang nghiep vu cot loi.
Artisan::command('demo:precheck', function () {
    $this->info('=== DEMO PRECHECK ===');

    $errors = [];

    try {
        DB::connection()->getPdo();
        $this->line('[OK] Ket noi database thanh cong.');
    } catch (Throwable $exception) {
        $errors[] = 'Khong ket noi duoc database: '.$exception->getMessage();
    }

    $requiredTables = [
        'nguoi_dung',
        'hang_van_chuyen',
        'don_hang',
        'doi_soat_cod',
        'van_don_ngoai_tuyen',
    ];

    foreach ($requiredTables as $table) {
        if (! Schema::hasTable($table)) {
            $errors[] = 'Thieu bang bat buoc: '.$table;
            continue;
        }

        $count = DB::table($table)->count();
        $this->line('[OK] Bang '.$table.' ton tai, so dong: '.$count);
    }

    if ($errors !== []) {
        $this->newLine();
        $this->error('Precheck that bai:');
        foreach ($errors as $error) {
            $this->line('- '.$error);
        }

        return 1;
    }

    $this->newLine();
    $this->info('Precheck thanh cong. San sang demo.');

    return 0;
})->purpose('Kiem tra nhanh dieu kien truoc khi demo');

// Lenh doi soat COD tu dong cho don da giao.
Artisan::command('cod:auto-reconcile {--limit=200} {--only-missing} {--quiet-summary}', function () {
    $limit = max(1, (int) $this->option('limit'));
    $onlyMissing = (bool) $this->option('only-missing');
    $quietSummary = (bool) $this->option('quiet-summary');

    /** @var Cod_AutoReconciliationService $service */
    $service = app(Cod_AutoReconciliationService::class);
    $summary = $service->reconcileDeliveredOrders($limit, $onlyMissing);

    if (! $quietSummary) {
        $this->info('=== AUTO COD RECONCILIATION ===');
        $this->line('Processed: '.$summary['processed']);
        $this->line('Created: '.$summary['created']);
        $this->line('Updated: '.$summary['updated']);
        $this->line('Pending confirmation: '.$summary['pending']);
        $this->line('Skipped: '.$summary['skipped']);
        $this->line('Failed: '.$summary['failed']);
    }

    return 0;
})->purpose('Tu dong doi soat COD cho don da giao');

// Lich chay nen: quet cod auto moi 10 phut, tranh chong cheo job.
Schedule::command('cod:auto-reconcile --limit=300 --only-missing --quiet-summary')
    ->everyTenMinutes()
    ->withoutOverlapping();



