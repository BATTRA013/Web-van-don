<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/migrations/2026_02_24_000103_create_don_hang_table.php
| - Buoc 1: Khai bao thay doi schema cho bang lien quan.
| - Buoc 2: Dam bao migration rollback an toan (neu co down()).
*/

/*
|--------------------------------------------------------------------------
| MIGRATION LEGACY: TAO BANG DON_HANG
|--------------------------------------------------------------------------
| Schema cu cho bang don_hang, da duoc dong bo lai o migration tong hop sau.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
        * Tao bang don_hang theo schema cu.
     */
    public function up(): void
    {
        Schema::create('don_hang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_don_hang', 50)->unique();
            $table->unsignedBigInteger('ma_nguoi_dung')->nullable();
            $table->unsignedBigInteger('ma_nha_xe')->nullable();
            $table->string('ten_nguoi_gui');
            $table->string('sdt_nguoi_gui', 20);
            $table->text('dia_chi_gui');
            $table->string('ten_nguoi_nhan');
            $table->string('sdt_nguoi_nhan', 20);
            $table->text('dia_chi_nhan');
            $table->decimal('tong_tien_hang', 15, 2)->default(0);
            $table->decimal('phi_van_chuyen', 15, 2)->default(0);
            $table->decimal('tien_thu_ho', 15, 2)->default(0);
            $table->string('trang_thai', 50)->default('cho_xu_ly');
            $table->timestamp('ngay_gui')->nullable();
            $table->timestamp('ngay_giao')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            // Khoa ngoai lien ket den nguoi_dung va nha_xe.
            $table->foreign('ma_nguoi_dung')->references('id')->on('nguoi_dung')->nullOnDelete();
            $table->foreign('ma_nha_xe')->references('id')->on('nha_xe')->nullOnDelete();
        });
    }

    /**
     * Rollback bang don_hang.
     */
    public function down(): void
    {
        Schema::dropIfExists('don_hang');
    }
};



