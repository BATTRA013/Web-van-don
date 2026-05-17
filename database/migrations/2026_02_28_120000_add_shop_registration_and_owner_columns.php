<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_28_120000_add_shop_registration_and_owner_columns.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION BO SUNG COT DANG KY SHOP + OWNER CARRIER
|--------------------------------------------------------------------------
| Bo sung thong tin shop/dang ky duyet cho nguoi_dung va cot ma_nguoi_dung cho hang_van_chuyen.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Them cot moi cho nguoi_dung va hang_van_chuyen, dong thoi cap nhat du lieu ban dau.
     */
    public function up(): void
    {
        // Bo sung thong tin dang ky shop vao bang nguoi_dung.
        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->string('ten_shop', 150)->nullable()->after('ho_ten');
            $table->string('so_dien_thoai', 20)->nullable()->after('ten_dang_nhap');
            $table->string('email', 150)->nullable()->after('so_dien_thoai');
            $table->string('mst', 50)->nullable()->after('email');
            $table->string('dia_chi', 255)->nullable()->after('mst');
            $table->tinyInteger('trang_thai_duyet')->default(1)->after('trang_thai');
            $table->text('ly_do_tu_choi')->nullable()->after('trang_thai_duyet');
        });

        // Bo sung cot owner cho cau hinh hang van chuyen.
        Schema::table('hang_van_chuyen', function (Blueprint $table) {
            $table->unsignedBigInteger('ma_nguoi_dung')->nullable()->after('ma_hang_van_chuyen');
            $table->foreign('ma_nguoi_dung')
                ->references('ma_nguoi_dung')
                ->on('nguoi_dung')
                ->nullOnDelete();
        });

        // Du lieu cu mac dinh da duyet de khong lam vo luong dang nhap hien tai.
        DB::table('nguoi_dung')->update([
            'trang_thai_duyet' => 1,
        ]);

        // Gan owner mac dinh cho carrier chua co owner (lay admin dau tien).
        DB::table('hang_van_chuyen')
            ->whereNull('ma_nguoi_dung')
            ->update([
                'ma_nguoi_dung' => DB::table('nguoi_dung')->where('vai_tro', 'admin')->value('ma_nguoi_dung'),
            ]);
    }

    /**
     * Rollback: bo cot vua them va khoi phuc schema cu.
     */
    public function down(): void
    {
        Schema::table('hang_van_chuyen', function (Blueprint $table) {
            $table->dropForeign(['ma_nguoi_dung']);
            $table->dropColumn('ma_nguoi_dung');
        });

        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->dropColumn([
                'ten_shop',
                'so_dien_thoai',
                'email',
                'mst',
                'dia_chi',
                'trang_thai_duyet',
                'ly_do_tu_choi',
            ]);
        });
    }
};



