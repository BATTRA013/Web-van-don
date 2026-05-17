<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/Nha_XeController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER NHA XE
|--------------------------------------------------------------------------
| Quan ly danh muc nha xe/chanh xe trong he thong.
| Cac ham o day chu yeu la CRUD du lieu bang nha_xe.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\External_Route_Bill;
use App\Models\Nha_Xe;
use App\Models\Nguoi_Dung;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class Nha_XeController extends Controller
{
    /**
     * Hien thi danh sach nha xe.
     */
    public function index(): View
    {
        // Buoc 1: Dong bo danh muc nha xe voi tai khoan quan ly chanh xe da duyet.
        $this->syncApprovedTransportManagersToCarriers();

        // Buoc 2: Nap danh sach nha xe + so van don ngoai tuyen lien quan.
        $carriers = Nha_Xe::query()
            ->withCount('externalRouteBills')
            ->orderByDesc('ma_nha_xe')
            ->get();

        // Buoc 3: Chu dong map thong tin user phu trach de hien thi trong danh sach.
        $candidateUsers = $this->candidateUsersForCarrierMapping();
        $currentUserId = (int) (auth()->user()?->ma_nguoi_dung ?? 0);

        $carriers->each(function (Nha_Xe $carrier) use ($candidateUsers): void {
            $linkedUser = $this->resolveLinkedUserForCarrier($carrier, $candidateUsers);

            if ($linkedUser) {
                $carrier->setAttribute('nguoi_phu_trach', $linkedUser);
            }
        });

        // Quan ly chanh xe chi duoc thay nha xe lien ket voi tai khoan cua ho.
        if ($this->isTransportManager()) {
            $carriers = $carriers
                ->filter(function (Nha_Xe $carrier) use ($currentUserId): bool {
                    return (int) ($carrier->nguoi_phu_trach?->ma_nguoi_dung ?? 0) === $currentUserId;
                })
                ->values();
        }

        // Buoc 4: Tra ve danh sach da enrich thong tin cho view.
        return view('admin.carriers.index', [
            'carriers' => $carriers,
            'canCreateCarrier' => $this->isAdmin(),
        ]);
    }

    /**
     * Dam bao tai khoan quan ly chanh xe da duyet co ban ghi tuong ung trong nha_xe.
     */
    private function syncApprovedTransportManagersToCarriers(): void
    {
        // Tap user dau vao: chi lay quan_ly_chanh_xe dang hoat dong va da duyet.
        $users = Nguoi_Dung::query()
            ->where('vai_tro', 'quan_ly_chanh_xe')
            ->where('trang_thai', 1)
            ->where('trang_thai_duyet', Nguoi_Dung::DUYET_DA_DUYET)
            ->get(['ho_ten', 'ten_don_vi', 'so_dien_thoai']);

        foreach ($users as $user) {
            // Uu tien ten don vi; neu khong co thi fallback ve ho ten.
            $name = trim((string) ($user->ten_don_vi ?: $user->ho_ten));

            if ($name === '') {
                continue;
            }

            $carrier = Nha_Xe::query()->firstOrCreate(
                ['ten_nha_xe' => $name],
                [
                    'so_dien_thoai' => $user->so_dien_thoai,
                    'tuyen_duong' => null,
                ]
            );

            if (! $carrier->so_dien_thoai && $user->so_dien_thoai) {
                // Bo sung so dien thoai cho ban ghi cu bi thieu.
                $carrier->so_dien_thoai = $user->so_dien_thoai;
                $carrier->save();
            }
        }
    }

    /**
     * Hien thi form tao nha xe moi.
     */
    public function create(): View
    {
        if (! $this->isAdmin()) {
            abort(403, 'Bạn chỉ được quản lý chành xe của chính mình.');
        }

        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang nha xe va chanh xe.
        return view('admin.carriers.create');
    }

    /**
     * Luu nha xe moi vao database.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $this->isAdmin()) {
            abort(403, 'Bạn chỉ được quản lý chành xe của chính mình.');
        }

        // Validate thong tin co ban cua nha xe.
        $validated = $request->validate([
            'ten_nha_xe' => ['required', 'string', 'max:150'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'tuyen_duong' => ['nullable', 'string', 'max:255'],
        ]);

        // Tao ban ghi nha xe.
        Nha_Xe::query()->create($validated);

        return redirect()->route('carriers.index')->with('success', 'Đã thêm nhà xe.');
    }

    /**
     * Xem chi tiet 1 nha xe.
     */
    public function show(Nha_Xe $carrier): View
    {
        $this->ensureCanManageCarrier($carrier);

        // Muc tieu: Tai chi tiet ban ghi de hien thi theo mang nha xe va chanh xe.
        $carrier->loadCount('externalRouteBills');

        return view('admin.carriers.show', [
            'carrier' => $carrier,
            'linkedUser' => $this->resolveLinkedUserForCarrier($carrier, $this->candidateUsersForCarrierMapping()),
        ]);
    }

    /**
     * Tap user co kha nang lien ket voi danh muc nha xe de bo sung thong tin hien thi.
     */
    private function candidateUsersForCarrierMapping(): Collection
    {
        // Muc tieu: Xu ly nghiep vu ham candidateUsersForCarrierMapping trong mang nha xe va chanh xe.
        return Nguoi_Dung::query()
            ->whereIn('vai_tro', ['quan_ly_chanh_xe', 'chu_shop'])
            ->orderByDesc('ma_nguoi_dung')
            ->get([
                'ma_nguoi_dung',
                'ho_ten',
                'ten_don_vi',
                'ten_dang_nhap',
                'so_dien_thoai',
                'email',
                'dia_chi',
                'vai_tro',
                'trang_thai',
                'trang_thai_duyet',
            ]);
    }

    /**
     * Tim user lien ket voi nha xe theo ten don vi hoac so dien thoai.
     */
    private function resolveLinkedUserForCarrier(Nha_Xe $carrier, Collection $candidateUsers): ?Nguoi_Dung
    {
        // So khop mem theo ten don vi, neu khong khop thi fallback theo so dien thoai.
        $carrierNameKey = $this->normalizeUnitName((string) $carrier->ten_nha_xe);
        $carrierPhoneKey = $this->normalizePhone($carrier->so_dien_thoai);

        return $candidateUsers->first(function (Nguoi_Dung $user) use ($carrierNameKey, $carrierPhoneKey): bool {
            $unitKey = $this->normalizeUnitName((string) ($user->ten_don_vi ?? ''));
            $phoneKey = $this->normalizePhone($user->so_dien_thoai);

            if ($carrierNameKey !== '' && $unitKey !== '' && $carrierNameKey === $unitKey) {
                return true;
            }

            return $carrierPhoneKey !== '' && $phoneKey !== '' && $carrierPhoneKey === $phoneKey;
        });
    }

    /**
     * Chuan hoa ten don vi de so khop mem giua nha_xe va nguoi_dung.
     */
    private function normalizeUnitName(string $value): string
    {
        // Muc tieu: Chuan hoa du lieu dau vao de xu ly on dinh trong mang nha xe va chanh xe.
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
    }

    /**
     * Chuan hoa so dien thoai ve dang so de so khop.
     */
    private function normalizePhone(?string $value): string
    {
        // Muc tieu: Chuan hoa du lieu dau vao de xu ly on dinh trong mang nha xe va chanh xe.
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    /**
     * Hien thi form sua nha xe.
     */
    public function edit(Nha_Xe $carrier): View
    {
        $this->ensureCanManageCarrier($carrier);

        // Muc tieu: Nap du lieu hien tai de chinh sua theo mang nha xe va chanh xe.
        return view('admin.carriers.edit', ['carrier' => $carrier]);
    }

    /**
     * Cap nhat nha xe.
     */
    public function update(Request $request, Nha_Xe $carrier): RedirectResponse
    {
        $this->ensureCanManageCarrier($carrier);

        // Validate thong tin dau vao.
        $validated = $request->validate([
            'ten_nha_xe' => ['required', 'string', 'max:150'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'tuyen_duong' => ['nullable', 'string', 'max:255'],
        ]);

        // Luu thay doi.
        $carrier->update($validated);

        return redirect()->route('carriers.show', $carrier)->with('success', 'Đã cập nhật nhà xe.');
    }

    /**
     * Xoa nha xe.
     */
    public function destroy(Nha_Xe $carrier): RedirectResponse
    {
        $activeBillCount = External_Route_Bill::query()
            ->where('ma_nha_xe', (int) $carrier->ma_nha_xe)
            ->count();

        if ($activeBillCount > 0) {
            return redirect()
                ->route('carriers.index')
                ->with('error', 'Không thể xóa nhà xe '.$carrier->ten_nha_xe.' vì đang có '.$activeBillCount.' vận đơn ngoài tuyến tham chiếu. Vui lòng cập nhật/xóa các vận đơn liên quan trước.');
        }

        try {
            // Xoa ban ghi nha xe khoi DB.
            $carrier->delete();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return redirect()
                    ->route('carriers.index')
                    ->with('error', 'Không thể xóa nhà xe '.$carrier->ten_nha_xe.' vì dữ liệu đang được tham chiếu bởi bảng khác. Vui lòng gỡ các liên kết trước rồi thử lại.');
            }

            throw $exception;
        }

        return redirect()->route('carriers.index')->with('success', 'Đã xóa nhà xe.');
    }

    /**
     * Role admin co toan quyen quan ly nha xe.
     */
    private function isAdmin(): bool
    {
        return $this->normalizeUnitName((string) (auth()->user()?->vai_tro ?? '')) === 'admin';
    }

    /**
     * Role quan ly chanh xe chi duoc phep thao tac tren ban ghi cua minh.
     */
    private function isTransportManager(): bool
    {
        return $this->normalizeUnitName((string) (auth()->user()?->vai_tro ?? '')) === 'quanlychanhxe';
    }

    /**
     * Kiem tra user hien tai co quyen thao tac voi nha xe dang xet hay khong.
     */
    private function ensureCanManageCarrier(Nha_Xe $carrier): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if (! $this->isTransportManager()) {
            abort(403);
        }

        $linkedUser = $this->resolveLinkedUserForCarrier($carrier, $this->candidateUsersForCarrierMapping());
        $currentUserId = (int) (auth()->user()?->ma_nguoi_dung ?? 0);

        if ((int) ($linkedUser?->ma_nguoi_dung ?? 0) !== $currentUserId) {
            abort(403, 'Bạn chỉ được quản lý chành xe của chính mình.');
        }
    }
}




