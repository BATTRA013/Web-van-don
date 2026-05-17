<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_04_13_000300_add_workflow_fields_to_van_don_ngoai_tuyen_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('van_don_ngoai_tuyen', function (Blueprint $table): void {
            $table->string('trang_thai', 50)->default('cho_nhan')->after('anh_chup_bien_lai');
            $table->string('ly_do_tu_choi', 500)->nullable()->after('trang_thai');
        });

        // Du lieu cu da co ma bien lai duoc xem la da cap nhat bien lai xong.
        DB::table('van_don_ngoai_tuyen')
            ->whereNotNull('ma_bien_lai')
            ->where('ma_bien_lai', '!=', '')
            ->update([
                'trang_thai' => 'da_gui_bien_lai',
                'ly_do_tu_choi' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('van_don_ngoai_tuyen', function (Blueprint $table): void {
            $table->dropColumn(['trang_thai', 'ly_do_tu_choi']);
        });
    }
};



