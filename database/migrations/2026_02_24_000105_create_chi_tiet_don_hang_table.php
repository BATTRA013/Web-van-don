<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000105_create_chi_tiet_don_hang_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG CHI_TIET_DON_HANG
|--------------------------------------------------------------------------
| Schema cu cho bang chi_tiet_don_hang.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang chi_tiet_don_hang.
     */
    public function up(): void
    {
        Schema::create('chi_tiet_don_hang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ma_don_hang');
            $table->string('ten_san_pham');
            $table->unsignedInteger('so_luong')->default(1);
            $table->decimal('don_gia', 15, 2)->default(0);
            $table->decimal('thanh_tien', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('ma_don_hang')->references('id')->on('don_hang')->cascadeOnDelete();
        });
    }

    /**
        * Rollback bang chi_tiet_don_hang.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_don_hang');
    }
};



