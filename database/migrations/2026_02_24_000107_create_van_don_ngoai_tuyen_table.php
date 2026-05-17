<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000107_create_van_don_ngoai_tuyen_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG VAN_DON_NGOAI_TUYEN
|--------------------------------------------------------------------------
| Schema cu cho van don ngoai tuyen.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang van_don_ngoai_tuyen.
     */
    public function up(): void
    {
        Schema::create('van_don_ngoai_tuyen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ma_don_hang');
            $table->string('don_vi_van_chuyen');
            $table->string('ma_van_don_doi_tac', 100)->index();
            $table->string('trang_thai', 50)->default('khoi_tao');
            $table->decimal('phi_van_chuyen', 15, 2)->default(0);
            $table->timestamp('ngay_gui')->nullable();
            $table->timestamp('ngay_du_kien_giao')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->foreign('ma_don_hang')->references('id')->on('don_hang')->cascadeOnDelete();
        });
    }

    /**
        * Rollback bang van_don_ngoai_tuyen.
     */
    public function down(): void
    {
        Schema::dropIfExists('van_don_ngoai_tuyen');
    }
};



