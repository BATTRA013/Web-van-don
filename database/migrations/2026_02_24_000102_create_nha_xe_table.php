<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000102_create_nha_xe_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG NHA_XE
|--------------------------------------------------------------------------
| Schema cu cho module nha xe, giu lai de doi chieu.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang nha_xe theo schema cu.
     */
    public function up(): void
    {
        Schema::create('nha_xe', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_nha_xe', 50)->unique();
            $table->string('ten_nha_xe');
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('dia_chi')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });
    }

    /**
        * Rollback bang nha_xe.
     */
    public function down(): void
    {
        Schema::dropIfExists('nha_xe');
    }
};



