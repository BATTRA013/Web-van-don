<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/External_Route_Bill.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL VAN DON NGOAI TUYEN
|--------------------------------------------------------------------------
| Luu thong tin van don ngoai tuyen/chanh xe (nha_xe).
| Ho tro doi soat va theo doi van don khong qua API GHN.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class External_Route_Bill extends Model
{
    use HasFactory;

    protected $table = 'van_don_ngoai_tuyen';
    protected $primaryKey = 'ma_van_don_ngoai_tuyen';
    public $timestamps = false;

    protected $fillable = [
        'ma_don_hang',
        'ma_nha_xe',
        'ma_bien_lai',
        'anh_chup_bien_lai',
        'trang_thai',
        'ly_do_tu_choi',
    ];

    protected $casts = [
        'ma_don_hang' => 'integer',
        'ma_nha_xe' => 'integer',
    ];

    /**
     * Van don ngoai tuyen nay gan voi 1 don hang noi bo.
     */
    public function order(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham order trong mang model va quan he du lieu.
        return $this->belongsTo(Order::class, 'ma_don_hang', 'ma_don_hang');
    }

    /**
     * Van don ngoai tuyen nay do 1 nha xe van chuyen.
     */
    public function nhaXe(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham nhaXe trong mang model va quan he du lieu.
        return $this->belongsTo(Nha_Xe::class, 'ma_nha_xe', 'ma_nha_xe');
    }
}




