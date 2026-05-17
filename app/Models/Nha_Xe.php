<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/Nha_Xe.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL NHA XE
|--------------------------------------------------------------------------
| Danh muc nha xe/chanh xe do he thong quan ly noi bo.
| Dung trong module carriers va van don ngoai tuyen.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nha_Xe extends Model
{
    use HasFactory;

    protected $table = 'nha_xe';
    protected $primaryKey = 'ma_nha_xe';
    public $timestamps = false;

    protected $fillable = [
        'ten_nha_xe',
        'so_dien_thoai',
        'tuyen_duong',
    ];

    /**
     * 1 nha xe co the co nhieu bien lai/van don ngoai tuyen.
     */
    public function externalRouteBills(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham externalRouteBills trong mang model va quan he du lieu.
        return $this->hasMany(External_Route_Bill::class, 'ma_nha_xe', 'ma_nha_xe');
    }
}




