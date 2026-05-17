<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Models/Nguoi_Dung.php
| - Buoc 1: Dinh nghia anh xa model <-> bang du lieu.
| - Buoc 2: Cau hinh fillable/casts va quy tac du lieu.
| - Buoc 3: Khai bao relations phuc vu query nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| MODEL NGUOI_DUNG
|--------------------------------------------------------------------------
| Model Eloquent anh xa bang `nguoi_dung` trong database.
| Chua quan he va quy tac auth (cot mat_khau, trang thai duyet, vai tro...).
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Nguoi_Dung extends Authenticatable
{
    use HasFactory, Notifiable;

    public const DUYET_CHO_DUYET = 0;
    public const DUYET_DA_DUYET = 1;
    public const DUYET_TU_CHOI = 2;

    protected $table = 'nguoi_dung';
    protected $primaryKey = 'ma_nguoi_dung';
    public $timestamps = false;

    protected $fillable = [
        'ho_ten',
        'ten_don_vi',
        'ten_dang_nhap',
        'so_dien_thoai',
        'email',
        'mst',
        'dia_chi',
        'mat_khau',
        'vai_tro',
        'trang_thai',
        'trang_thai_duyet',
        'ly_do_tu_choi',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected $casts = [
        'trang_thai' => 'integer',
        'trang_thai_duyet' => 'integer',
        'mat_khau' => 'hashed',
    ];

    /**
     * Khai bao cot mat khau dung cho he thong Auth.
     */
    public function getAuthPasswordName(): string
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang model va quan he du lieu.
        return 'mat_khau';
    }

    /**
     * Vo hieu hoa remember token vi bang custom hien khong dung cot nay.
     */
    public function getRememberTokenName(): string
    {
        // Muc tieu: Lay du lieu phuc vu xu ly trong mang model va quan he du lieu.
        return '';
    }

    /**
     * 1 user co nhieu don hang.
     */
    public function orders(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham orders trong mang model va quan he du lieu.
        return $this->hasMany(Order::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    /**
     * 1 user co the co nhieu cau hinh hang van chuyen.
     */
    public function carriers(): HasMany
    {
        // Muc tieu: Xu ly nghiep vu ham carriers trong mang model va quan he du lieu.
        return $this->hasMany(Hang_Van_Chuyen::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }
}




