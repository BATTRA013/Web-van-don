<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/Cod_Reconciliation.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL DOI SOAT COD
|--------------------------------------------------------------------------
| Luu ket qua doi soat COD giua he thong va hang van chuyen.
| Co cac truong cod_ky_vong, cod_thuc_nhan, chenhlech.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cod_Reconciliation extends Model
{
    use HasFactory;

    protected $table = 'doi_soat_cod';
    protected $primaryKey = 'ma_doi_soat';
    public $timestamps = false;

    protected $fillable = [
        'ma_don_hang',
        'ma_hang_van_chuyen',
        'cod_ky_vong',
        'cod_thuc_nhan',
        'chenhlech',
        'ngay_doi_soat',
        'trang_thai',
    ];

    protected $casts = [
        'cod_ky_vong' => 'decimal:2',
        'cod_thuc_nhan' => 'decimal:2',
        'chenhlech' => 'decimal:2',
        'ngay_doi_soat' => 'datetime',
    ];

    /**
     * Ban ghi doi soat nay thuoc ve 1 don hang.
     */
    public function order(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham order trong mang model va quan he du lieu.
        return $this->belongsTo(Order::class, 'ma_don_hang', 'ma_don_hang');
    }

    /**
     * Ban ghi doi soat nay thuoc ve 1 hang van chuyen.
     */
    public function hangVanChuyen(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham hangVanChuyen trong mang model va quan he du lieu.
        return $this->belongsTo(Hang_Van_Chuyen::class, 'ma_hang_van_chuyen', 'ma_hang_van_chuyen');
    }
}




