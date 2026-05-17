# Web-van-don
He thong quan ly van don va doi soat cho mo hinh lam viec lien ket nhieu hang van chuyen. Du an tap trung vao nghiep vu thuc te: tao don, day sang doi tac van chuyen, dong bo trang thai, quan ly COD va phan quyen nguoi dung theo vai tro.

## Tong quan

Ung dung duoc xay dung de phuc vu cac doi tuong sau:

- Chu shop: quan ly don hang, tao van don va theo doi trang thai
- Quan ly chanh xe: xu ly van don ngoai tuyen, tiep nhan/tu choi va xac nhan bien lai
- Admin: quan ly nguoi dung, cau hinh he thong va theo doi van hanh

## Tinh nang chinh

- Quan ly don hang noi bo theo CRUD day du
- Tao van don da hang thong qua cac dich vu van chuyen ho tro
- Dong bo trang thai van don theo tung don hoac dong loat
- Quan ly van don ngoai tuyen qua kenh chanh xe
- Quan ly cau hinh API/ket noi cho tung hang van chuyen
- Doi soat COD va chay tac vu auto reconciliation
- Quan ly nguoi dung va trang thai duyet tai khoan
- Phan quyen truy cap theo vai tro va trang thai tai khoan

## Vai tro nguoi dung

- `admin`: co toan quyen, quan ly nguoi dung, nha xe, don hang, cau hinh va doi soat
- `chu_shop`: quan ly don hang, cau hinh van chuyen, theo doi van hanh
- `quan_ly_chanh_xe`: tiep nhan van don ngoai tuyen va cap nhat bien lai

## Cong nghe chinh

- PHP 8.2
- Laravel 12
- Frontend build voi Vite
- Database migrations va test feature/unit

## Cau truc thu muc

- `app/Http/Controllers/`: logic API va nghiep vu
- `app/Services/`: xu ly ket noi hang van chuyen va doi soat COD
- `app/Models/`: model cho don hang, nha xe, doi soat va van don ngoai tuyen
- `config/services.php`: cau hinh ket noi ben ngoai
- `database/migrations/`: schema co so du lieu
- `routes/web.php`: cac route dashboard va nghiep vu chinh
- `tests/`: tap hop test cho cac luong nghiep vu

## Chay du an

1. Copy `.env.example` thanh `.env`
2. Cau hinh database va cac bien moi truong trong `.env`
3. Cau hinh thong tin dich vu ben ngoai trong `config/services.php` neu can
4. Chay `composer install`
5. Chay `php artisan key:generate`
6. Chay `php artisan migrate`
7. Chay `npm install` va `npm run dev` neu can build frontend

## Ghi chu

Repository hien tai khong con thu muc tai lieu rieng `docs/`. Neu can mo ta chi tiet hon, hay viet cac tai lieu moi gan voi nghiep vu hien tai thay vi tai dung mo ta chung cho Laravel.
