<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000104_create_hang_van_chuyen_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG HANG_VAN_CHUYEN
|--------------------------------------------------------------------------
| Schema cu cho bang hang_van_chuyen, giu lai de doi chieu.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang hang_van_chuyen theo schema cu.
     */
    public function up(): void
    {
        Schema::create('hang_van_chuyen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ma_don_hang');
            $table->string('ma_hang', 50)->unique();
            $table->string('ten_hang');
            $table->text('mo_ta')->nullable();
            $table->unsignedInteger('so_luong')->default(1);
            $table->decimal('trong_luong', 10, 2)->nullable();
            $table->decimal('gia_tri_hang', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('ma_don_hang')->references('id')->on('don_hang')->cascadeOnDelete();
        });
    }

    /**
        * Rollback bang hang_van_chuyen.
     */
    public function down(): void
    {
        Schema::dropIfExists('hang_van_chuyen');
    }
};



