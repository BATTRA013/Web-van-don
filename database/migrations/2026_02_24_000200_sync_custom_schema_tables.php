<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000200_sync_custom_schema_tables.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION DONG BO LAI TOAN BO SCHEMA CHINH
|--------------------------------------------------------------------------
| Migration nay reset cac bang custom va tao lai theo schema cuoi cung.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reset schema custom cu, sau do tao lai cac bang theo thiet ke chuan.
     */
    public function up(): void
    {
        // Tat FKs de co the drop bang theo thu tu linh hoat.
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('van_don_ngoai_tuyen');
        Schema::dropIfExists('doi_soat_cod');
        Schema::dropIfExists('chi_tiet_don_hang');
        Schema::dropIfExists('don_hang');
        Schema::dropIfExists('hang_van_chuyen');
        Schema::dropIfExists('nha_xe');
        Schema::dropIfExists('nguoi_dung');
        Schema::enableForeignKeyConstraints();

        // Tao lai bang nguoi_dung.
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->bigIncrements('ma_nguoi_dung');
            $table->string('ho_ten', 150);
            $table->string('ten_dang_nhap', 150)->unique();
            $table->string('mat_khau', 255);
            $table->string('vai_tro', 50);
            $table->tinyInteger('trang_thai')->default(1);
        });

        // Tao lai bang nha_xe.
        Schema::create('nha_xe', function (Blueprint $table) {
            $table->bigIncrements('ma_nha_xe');
            $table->string('ten_nha_xe', 150);
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('tuyen_duong', 255)->nullable();
        });

        // Tao lai bang hang_van_chuyen.
        Schema::create('hang_van_chuyen', function (Blueprint $table) {
            $table->bigIncrements('ma_hang_van_chuyen');
            $table->string('ten_hang', 150);
            $table->text('api_token');
            $table->string('shop_id', 50)->nullable();
            $table->tinyInteger('moi_truong')->nullable();
            $table->json('config_json')->nullable();
        });

        // Tao lai bang don_hang + rang buoc khoa ngoai.
        Schema::create('don_hang', function (Blueprint $table) {
            $table->bigIncrements('ma_don_hang');
            $table->unsignedBigInteger('ma_nguoi_dung');
            $table->unsignedBigInteger('ma_hang_van_chuyen');
            $table->string('ten_nguoi_nhan', 150);
            $table->string('sdt_nguoi_nhan', 20);
            $table->string('dia_chi_chi_tiet', 255);
            $table->integer('ma_tinh_thanh');
            $table->integer('ma_quan_huyen');
            $table->string('ma_phuong_xa', 20);
            $table->integer('trong_luong');
            $table->integer('chieu_dai')->nullable();
            $table->integer('chieu_rong')->nullable();
            $table->integer('chieu_cao')->nullable();
            $table->decimal('tien_cod', 15, 2);
            $table->decimal('phi_ship_du_kien', 15, 2)->nullable();
            $table->decimal('phi_ship_thuc_te', 15, 2)->nullable();
            $table->decimal('phi_van_chuyen', 15, 2)->nullable();
            $table->string('ma_tracking', 100)->unique();
            $table->string('trang_thai', 50);

            $table->foreign('ma_nguoi_dung')->references('ma_nguoi_dung')->on('nguoi_dung')->restrictOnDelete();
            $table->foreign('ma_hang_van_chuyen')->references('ma_hang_van_chuyen')->on('hang_van_chuyen')->restrictOnDelete();
        });

        // Tao lai bang chi_tiet_don_hang.
        Schema::create('chi_tiet_don_hang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ma_don_hang');
            $table->string('ten_san_pham', 255);
            $table->integer('so_luong');
            $table->decimal('gia_ban', 15, 2)->nullable();
            $table->integer('khoi_luong_sp')->nullable();

            $table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hang')->cascadeOnDelete();
        });

        // Tao lai bang doi_soat_cod.
        Schema::create('doi_soat_cod', function (Blueprint $table) {
            $table->bigIncrements('ma_doi_soat');
            $table->unsignedBigInteger('ma_don_hang');
            $table->unsignedBigInteger('ma_hang_van_chuyen');
            $table->decimal('cod_ky_vong', 15, 2);
            $table->decimal('cod_thuc_nhan', 15, 2);
            $table->decimal('chenhlech', 15, 2);
            $table->dateTime('ngay_doi_soat')->nullable();
            $table->string('trang_thai', 50);

            $table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hang')->cascadeOnDelete();
            $table->foreign('ma_hang_van_chuyen')->references('ma_hang_van_chuyen')->on('hang_van_chuyen')->restrictOnDelete();
        });

        // Tao lai bang van_don_ngoai_tuyen.
        Schema::create('van_don_ngoai_tuyen', function (Blueprint $table) {
            $table->bigIncrements('ma_van_don_ngoai_tuyen');
            $table->unsignedBigInteger('ma_don_hang');
            $table->unsignedBigInteger('ma_nha_xe');
            $table->string('ma_bien_lai', 100)->unique();
            $table->string('anh_chup_bien_lai', 255)->nullable();

            $table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hang')->cascadeOnDelete();
            $table->foreign('ma_nha_xe')->references('ma_nha_xe')->on('nha_xe')->restrictOnDelete();
        });
    }

    /**
        * Rollback: xoa toan bo bang custom theo thu tu an toan.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('van_don_ngoai_tuyen');
        Schema::dropIfExists('doi_soat_cod');
        Schema::dropIfExists('chi_tiet_don_hang');
        Schema::dropIfExists('don_hang');
        Schema::dropIfExists('hang_van_chuyen');
        Schema::dropIfExists('nha_xe');
        Schema::dropIfExists('nguoi_dung');
        Schema::enableForeignKeyConstraints();
    }
};



