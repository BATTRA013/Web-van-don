<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000106_create_doi_soat_cod_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG DOI_SOAT_COD
|--------------------------------------------------------------------------
| Schema cu cho module doi soat COD.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang doi_soat_cod.
     */
    public function up(): void
    {
        Schema::create('doi_soat_cod', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ma_don_hang');
            $table->decimal('so_tien_cod', 15, 2);
            $table->decimal('phi_doi_soat', 15, 2)->default(0);
            $table->decimal('thuc_nhan', 15, 2)->default(0);
            $table->timestamp('ngay_doi_soat')->nullable();
            $table->string('trang_thai', 50)->default('cho_doi_soat');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('ma_don_hang')->references('id')->on('don_hang')->cascadeOnDelete();
        });
    }

    /**
        * Rollback bang doi_soat_cod.
     */
    public function down(): void
    {
        Schema::dropIfExists('doi_soat_cod');
    }
};



