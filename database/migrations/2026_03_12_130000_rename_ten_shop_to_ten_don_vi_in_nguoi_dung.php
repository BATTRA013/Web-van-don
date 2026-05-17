<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_03_12_130000_rename_ten_shop_to_ten_don_vi_in_nguoi_dung.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION DOI TEN COT TEN SHOP -> TEN DON VI
|--------------------------------------------------------------------------
| Doi ten cot de neutral hoa schema, dung chung cho chu shop va quan ly chanh xe.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Doi ten cot ten_shop thanh ten_don_vi.
     */
    public function up(): void
    {
        if (Schema::hasColumn('nguoi_dung', 'ten_shop') && ! Schema::hasColumn('nguoi_dung', 'ten_don_vi')) {
            Schema::table('nguoi_dung', function (Blueprint $table): void {
                $table->renameColumn('ten_shop', 'ten_don_vi');
            });
        }
    }

    /**
     * Rollback ve ten cot cu.
     */
    public function down(): void
    {
        if (Schema::hasColumn('nguoi_dung', 'ten_don_vi') && ! Schema::hasColumn('nguoi_dung', 'ten_shop')) {
            Schema::table('nguoi_dung', function (Blueprint $table): void {
                $table->renameColumn('ten_don_vi', 'ten_shop');
            });
        }
    }
};



