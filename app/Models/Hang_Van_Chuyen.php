<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/Hang_Van_Chuyen.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL HANG VAN CHUYEN
|--------------------------------------------------------------------------
| Luu cau hinh ket noi API cua cac hang van chuyen (ten_hang, token, shop_id).
| Duoc dung trong luong tao don GHN va mo rong da hang sau nay.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hang_Van_Chuyen extends Model
{
    use HasFactory;

    protected $table = 'hang_van_chuyen';
    protected $primaryKey = 'ma_hang_van_chuyen';
    public $timestamps = false;

    protected $fillable = [
        'ma_nguoi_dung',
        'ten_hang',
        'api_token',
        'shop_id',
        'moi_truong',
        'config_json',
    ];

    protected $casts = [
        'moi_truong' => 'integer',
        'config_json' => 'array',
    ];

    /**
     * 1 hang van chuyen/cau hinh co the gan voi nhieu don hang.
     */
    public function orders(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham orders trong mang model va quan he du lieu.
        return $this->hasMany(Order::class, 'ma_hang_van_chuyen', 'ma_hang_van_chuyen');
    }

    /**
     * 1 cau hinh hang van chuyen co the duoc tham chieu boi nhieu ban ghi doi soat COD.
     */
    public function codReconciliations(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham codReconciliations trong mang model va quan he du lieu.
        return $this->hasMany(Cod_Reconciliation::class, 'ma_hang_van_chuyen', 'ma_hang_van_chuyen');
    }

    /**
     * Cau hinh nay thuoc 1 user (hoac null voi cau hinh dung chung he thong).
     */
    public function nguoiDung(): BelongsTo
    {
        // Muc tieu: Xu ly nghiep vu ham nguoiDung trong mang model va quan he du lieu.
        return $this->belongsTo(Nguoi_Dung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }
}




