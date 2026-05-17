<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_03_12_120000_migrate_roles_to_three_role_scheme.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION CHUYEN DOI VAI TRO SANG BO 3 ROLE
|--------------------------------------------------------------------------
| Chuan hoa gia tri cot vai_tro ve 3 gia tri:
| - admin
| - chu_shop
| - quan_ly_chanh_xe
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chuyen du lieu vai tro cu sang bo 3 role moi.
     */
    public function up(): void
    {
        // Quy ve admin cho cac bien the viet khac nhau cua admin.
        DB::table('nguoi_dung')
            ->whereRaw("LOWER(REPLACE(REPLACE(TRIM(vai_tro), '_', ''), ' ', '')) = ?", ['admin'])
            ->update(['vai_tro' => 'admin']);

        // Role khachhang cu duoc doi thanh chu_shop.
        DB::table('nguoi_dung')
            ->whereRaw("LOWER(REPLACE(REPLACE(TRIM(vai_tro), '_', ''), ' ', '')) = ?", ['khachhang'])
            ->update(['vai_tro' => 'chu_shop']);

        // Role nhanvien cu duoc doi thanh quan_ly_chanh_xe.
        DB::table('nguoi_dung')
            ->whereRaw("LOWER(REPLACE(REPLACE(TRIM(vai_tro), '_', ''), ' ', '')) = ?", ['nhanvien'])
            ->update(['vai_tro' => 'quan_ly_chanh_xe']);

        // Chuan hoa cac bien the nhap tay cua role moi.
        DB::table('nguoi_dung')
            ->whereRaw("LOWER(REPLACE(REPLACE(TRIM(vai_tro), '_', ''), ' ', '')) = ?", ['chushop'])
            ->update(['vai_tro' => 'chu_shop']);

        DB::table('nguoi_dung')
            ->whereRaw("LOWER(REPLACE(REPLACE(TRIM(vai_tro), '_', ''), ' ', '')) = ?", ['quanlychanhxe'])
            ->update(['vai_tro' => 'quan_ly_chanh_xe']);
    }

    /**
     * Rollback du lieu role ve bo cu.
     */
    public function down(): void
    {
        DB::table('nguoi_dung')
            ->where('vai_tro', 'chu_shop')
            ->update(['vai_tro' => 'khachhang']);

        DB::table('nguoi_dung')
            ->where('vai_tro', 'quan_ly_chanh_xe')
            ->update(['vai_tro' => 'nhanvien']);
    }
};



