<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: database/seeders/DatabaseSeeder.php
| - Buoc 1: Tao du lieu mau khoi tao cho moi truong dev/test.
| - Buoc 2: Goi cac seeder con theo thu tu phu thuoc nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| DATABASE SEEDER CHINH
|--------------------------------------------------------------------------
| Seed du lieu mau lien ket day du: user -> carrier -> order -> detail -> cod -> bien lai.
*/

namespace Database\Seeders;

use App\Models\Cod_Reconciliation;
use App\Models\External_Route_Bill;
use App\Models\Hang_Van_Chuyen;
use App\Models\Nguoi_Dung;
use App\Models\Nha_Xe;
use App\Models\Order;
use App\Models\Order_Detail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Chay seed du lieu mau cho toan bo he thong.
     */
    public function run(): void
    {
        // Lam sach du lieu custom de bo demo moi luon o trang thai xac dinh.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        External_Route_Bill::truncate();
        Cod_Reconciliation::truncate();
        Order_Detail::truncate();
        Order::truncate();
        Hang_Van_Chuyen::truncate();
        Nha_Xe::truncate();
        Nguoi_Dung::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $defaultPassword = Hash::make('demo12345');

        // 3 tai khoan theo dung bo vai tro de demo va test luong phan quyen.
        $admin = Nguoi_Dung::create([
            'ho_ten' => 'Admin Demo',
            'ten_don_vi' => 'He thong quan tri',
            'ten_dang_nhap' => 'admin_demo',
            'so_dien_thoai' => '0901000001',
            'email' => 'admin.demo@example.com',
            'mat_khau' => $defaultPassword,
            'vai_tro' => 'admin',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $chuShop = Nguoi_Dung::create([
            'ho_ten' => 'Chu Shop Demo',
            'ten_don_vi' => 'Shop Trai Cay 68',
            'ten_dang_nhap' => 'chushop_demo',
            'so_dien_thoai' => '0901000002',
            'email' => 'shop.demo@example.com',
            'mat_khau' => $defaultPassword,
            'vai_tro' => 'chu_shop',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        $quanLyChanhXe = Nguoi_Dung::create([
            'ho_ten' => 'Quan Ly Chanh Xe Demo',
            'ten_don_vi' => 'Chanh Xe Mien Trung',
            'ten_dang_nhap' => 'chanhxe_demo',
            'so_dien_thoai' => '0901000003',
            'email' => 'chanhxe.demo@example.com',
            'mat_khau' => $defaultPassword,
            'vai_tro' => 'quan_ly_chanh_xe',
            'trang_thai' => 1,
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
        ]);

        // Carrier mau cho 2 hang de retest da hang.
        $ghn = Hang_Van_Chuyen::create([
            'ma_nguoi_dung' => $admin->ma_nguoi_dung,
            'ten_hang' => 'GHN',
            'api_token' => 'demo-ghn-token',
            'shop_id' => 'GHN-DEMO-001',
            'moi_truong' => 0,
            'config_json' => [
                'service_type_id' => 2,
                'from_district_id' => 1454,
            ],
        ]);

        $viettel = Hang_Van_Chuyen::create([
            'ma_nguoi_dung' => $quanLyChanhXe->ma_nguoi_dung,
            'ten_hang' => 'VIETTEL_POST',
            'api_token' => 'demo-vtp-token',
            'shop_id' => 'VTP-DEMO-001',
            'moi_truong' => 0,
            'config_json' => [
                'customer_code' => 'VTP-CUSTOMER-DEMO',
            ],
        ]);

        $nhaXeMienDong = Nha_Xe::create([
            'ten_nha_xe' => 'Nha Xe Mien Dong',
            'so_dien_thoai' => '0902000001',
            'tuyen_duong' => 'TP.HCM - Da Nang',
        ]);

        $nhaXeMienTay = Nha_Xe::create([
            'ten_nha_xe' => 'Nha Xe Mien Tay',
            'so_dien_thoai' => '0902000002',
            'tuyen_duong' => 'TP.HCM - Can Tho',
        ]);

        // Don 1: da ban giao cho GHN, da doi soat COD.
        $orderGhn = Order::create([
            'ma_nguoi_dung' => $chuShop->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $ghn->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Tran Thi B',
            'sdt_nguoi_nhan' => '0903000001',
            'dia_chi_chi_tiet' => '45 Hung Vuong, Da Nang',
            'ma_tinh_thanh' => 48,
            'ma_quan_huyen' => 490,
            'ma_phuong_xa' => '20110',
            'trong_luong' => 2200,
            'chieu_dai' => 35,
            'chieu_rong' => 25,
            'chieu_cao' => 15,
            'tien_cod' => 780000,
            'phi_ship_du_kien' => 32000,
            'phi_ship_thuc_te' => 34000,
            'phi_van_chuyen' => 34000,
            'ma_tracking' => 'GHN-DEMO-0001',
            'trang_thai' => 'da_giao',
        ]);

        // Don 2: da tao cho Viettel va gui chanh xe kem bien lai ngoai tuyen.
        $orderVtp = Order::create([
            'ma_nguoi_dung' => $quanLyChanhXe->ma_nguoi_dung,
            'ma_hang_van_chuyen' => $viettel->ma_hang_van_chuyen,
            'ten_nguoi_nhan' => 'Le Van C',
            'sdt_nguoi_nhan' => '0903000002',
            'dia_chi_chi_tiet' => '12 Nguyen Van Linh, Can Tho',
            'ma_tinh_thanh' => 92,
            'ma_quan_huyen' => 916,
            'ma_phuong_xa' => '31189',
            'trong_luong' => 5400,
            'chieu_dai' => 50,
            'chieu_rong' => 40,
            'chieu_cao' => 30,
            'tien_cod' => 1320000,
            'phi_ship_du_kien' => 45000,
            'phi_ship_thuc_te' => null,
            'phi_van_chuyen' => 45000,
            'ma_tracking' => 'VTP-DEMO-0001',
            'trang_thai' => 'dang_van_chuyen',
        ]);

        Order_Detail::insert([
            [
                'ma_don_hang' => $orderGhn->ma_don_hang,
                'ten_san_pham' => 'May in nhiet',
                'so_luong' => 1,
                'gia_ban' => 700000,
                'khoi_luong_sp' => 1500,
            ],
            [
                'ma_don_hang' => $orderGhn->ma_don_hang,
                'ten_san_pham' => 'Tem nhan kho A6',
                'so_luong' => 2,
                'gia_ban' => 40000,
                'khoi_luong_sp' => 300,
            ],
            [
                'ma_don_hang' => $orderVtp->ma_don_hang,
                'ten_san_pham' => 'Thung tao huu co',
                'so_luong' => 6,
                'gia_ban' => 220000,
                'khoi_luong_sp' => 900,
            ],
        ]);

        Cod_Reconciliation::insert([
            [
                'ma_don_hang' => $orderGhn->ma_don_hang,
                'ma_hang_van_chuyen' => $ghn->ma_hang_van_chuyen,
                'cod_ky_vong' => 780000,
                'cod_thuc_nhan' => 775000,
                'chenhlech' => 5000,
                'ngay_doi_soat' => now()->subDay(),
                'trang_thai' => 'da_doi_soat',
            ],
            [
                'ma_don_hang' => $orderVtp->ma_don_hang,
                'ma_hang_van_chuyen' => $viettel->ma_hang_van_chuyen,
                'cod_ky_vong' => 1320000,
                'cod_thuc_nhan' => 0,
                'chenhlech' => 1320000,
                'ngay_doi_soat' => null,
                'trang_thai' => 'cho_doi_soat',
            ],
        ]);

        External_Route_Bill::insert([
            [
                'ma_don_hang' => $orderVtp->ma_don_hang,
                'ma_nha_xe' => $nhaXeMienDong->ma_nha_xe,
                'ma_bien_lai' => 'BL-MD-0001',
                'anh_chup_bien_lai' => 'bien_lai/bl-md-0001.jpg',
            ],
            [
                'ma_don_hang' => $orderGhn->ma_don_hang,
                'ma_nha_xe' => $nhaXeMienTay->ma_nha_xe,
                'ma_bien_lai' => 'BL-MT-0002',
                'anh_chup_bien_lai' => 'bien_lai/bl-mt-0002.jpg',
            ],
        ]);
    }
}



