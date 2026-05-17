<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/Order_Detail.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL CHI TIET DON HANG
|--------------------------------------------------------------------------
| Luu danh sach san pham trong moi don (so luong, gia, khoi luong...).
| Thuong duoc tao/cap nhat cung luong xu ly don hang.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order_Detail extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_don_hang';
    public $timestamps = false;

    protected $fillable = [
        'ma_don_hang',
        'ten_san_pham',
        'so_luong',
        'gia_ban',
        'khoi_luong_sp',
    ];

    protected $casts = [
        'so_luong' => 'integer',
        'gia_ban' => 'decimal:2',
        'khoi_luong_sp' => 'integer',
    ];

    /**
     * Moi dong chi tiet thuoc ve 1 don hang.
     */
    public function order(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham order trong mang model va quan he du lieu.
        return $this->belongsTo(Order::class, 'ma_don_hang', 'ma_don_hang');
    }
}




