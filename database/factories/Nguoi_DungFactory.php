<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/factories/Nguoi_DungFactory.php
| - Buoc 1: Xu ly input theo trach nhiem cua file.
| - Buoc 2: Thuc hien nghiep vu trung tam.
| - Buoc 3: Tra ket qua cho lop su dung tiep theo.
*/

/*
|--------------------------------------------------------------------------
| FACTORY NGUOI_DUNG
|--------------------------------------------------------------------------
| Tao du lieu gia (fake data) cho model Nguoi_Dung phuc vu test/seeder.
*/

namespace Database\Factories;

use App\Models\Nguoi_Dung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Nguoi_Dung>
 */
class Nguoi_DungFactory extends Factory
{
    protected $model = Nguoi_Dung::class;

    protected static ?string $password;

    /**
     * Dinh nghia bo du lieu mac dinh khi goi Nguoi_Dung::factory()->create().
     */
    public function definition(): array
    {
        // Su dung fake() de sinh du lieu ngau nhien, mat_khau duoc hash san.
        return [
            'ho_ten' => fake()->name(),
            'ten_don_vi' => fake()->company(),
            'ten_dang_nhap' => fake()->unique()->userName(),
            'so_dien_thoai' => fake()->numerify('09########'),
            'email' => fake()->unique()->safeEmail(),
            'mst' => null,
            'dia_chi' => fake()->address(),
            'mat_khau' => static::$password ??= Hash::make('password'),
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ly_do_tu_choi' => null,
        ];
    }
}



