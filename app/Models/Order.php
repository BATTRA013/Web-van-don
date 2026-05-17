<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/Order.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL DON_HANG
|--------------------------------------------------------------------------
| Dai dien bang don_hang, luu thong tin don va trang thai van chuyen.
| Co cac quan he den nguoi_dung, hang_van_chuyen, chi_tiet_don_hang.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $table = 'don_hang';
    protected $primaryKey = 'ma_don_hang';
    public $timestamps = false;

    protected $fillable = [
        'ma_nguoi_dung',
        'ma_hang_van_chuyen',
        'ten_nguoi_nhan',
        'sdt_nguoi_nhan',
        'dia_chi_chi_tiet',
        'ma_tinh_thanh',
        'ma_quan_huyen',
        'ma_phuong_xa',
        'trong_luong',
        'chieu_dai',
        'chieu_rong',
        'chieu_cao',
        'tien_cod',
        'phi_ship_du_kien',
        'phi_ship_thuc_te',
        'phi_van_chuyen',
        'ma_tracking',
        'trang_thai',
    ];

    protected $casts = [
        'ma_tinh_thanh' => 'integer',
        'ma_quan_huyen' => 'integer',
        'trong_luong' => 'integer',
        'chieu_dai' => 'integer',
        'chieu_rong' => 'integer',
        'chieu_cao' => 'integer',
        'tien_cod' => 'decimal:2',
        'phi_ship_du_kien' => 'decimal:2',
        'phi_ship_thuc_te' => 'decimal:2',
        'phi_van_chuyen' => 'decimal:2',
    ];

    /**
     * Don hang thuoc 1 hang van chuyen.
     */
    public function hangVanChuyen(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham hangVanChuyen trong mang model va quan he du lieu.
        return $this->belongsTo(Hang_Van_Chuyen::class, 'ma_hang_van_chuyen', 'ma_hang_van_chuyen');
    }

    /**
     * Don hang thuoc ve 1 nguoi dung (chu shop/nguoi tao don).
     */
    public function nguoiDung(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham nguoiDung trong mang model va quan he du lieu.
        return $this->belongsTo(Nguoi_Dung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    /**
     * Don hang co nhieu dong chi tiet san pham.
     */
    public function orderDetails(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham orderDetails trong mang model va quan he du lieu.
        return $this->hasMany(Order_Detail::class, 'ma_don_hang', 'ma_don_hang');
    }

    /**
     * Don hang co the co nhieu ban ghi doi soat COD.
     */
    public function codReconciliations(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham codReconciliations trong mang model va quan he du lieu.
        return $this->hasMany(Cod_Reconciliation::class, 'ma_don_hang', 'ma_don_hang');
    }

    /**
     * Don hang co the co nhieu van don ngoai tuyen.
     */
    public function externalRouteBills(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham externalRouteBills trong mang model va quan he du lieu.
        return $this->hasMany(External_Route_Bill::class, 'ma_don_hang', 'ma_don_hang');
    }
}




