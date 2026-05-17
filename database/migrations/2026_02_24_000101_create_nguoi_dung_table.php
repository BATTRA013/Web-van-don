<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000101_create_nguoi_dung_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG NGUOI_DUNG
|--------------------------------------------------------------------------
| File schema cu, giu lai de doi chieu lich su phat trien CSDL.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang nguoi_dung theo schema cu.
     */
    public function up(): void
    {
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_nguoi_dung', 50)->unique();
            $table->string('ho_ten');
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('dia_chi')->nullable();
            $table->string('loai_nguoi_dung', 30)->default('khach_hang');
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });
    }

    /**
        * Rollback bang nguoi_dung.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguoi_dung');
    }
};



