<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Controllers/Admin/Quan_Ly_Nguoi_DungController.php
| - Buoc 1: Nhan request tu route va kiem tra middleware da cho phep truy cap.
| - Buoc 2: Chuan hoa/validate input (FormRequest hoac validate inline).
| - Buoc 3: Dieu phoi nghiep vu qua Service/Model va xu ly transaction neu can.
| - Buoc 4: Tra ve view/redirect/json kem message phu hop cho UI.
*/

/*
|--------------------------------------------------------------------------
| CONTROLLER QUAN LY NGUOI DUNG
|--------------------------------------------------------------------------
| Dung cho admin quan ly tai khoan: tao, sua, xoa, duyet, tu choi user.
| File nay dong vai tro quan tri va kiem soat quyen truy cap.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nha_Xe;
use App\Models\Nguoi_Dung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class Quan_Ly_Nguoi_DungController extends Controller
{
    /**
     * Chan truy cap neu user hien tai khong phai admin.
     */
    private function abortIfNotAdmin(Request $request): void
    {
        // Chuan hoa role de so sanh on dinh.
        $normalizedRole = Str::of((string) ($request->user()?->vai_tro ?? ''))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        // Role khac admin -> cam truy cap toan bo module quan ly user.
        if ($normalizedRole !== 'admin') {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }
    }

    /**
     * Danh sach user.
     */
    public function index(Request $request): View
    {
        // Muc tieu: Tai danh sach ban ghi theo bo loc va pham vi quyen cua mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        return view('admin.users.index', [
            'users' => Nguoi_Dung::query()->orderByDesc('ma_nguoi_dung')->get(),
        ]);
    }

    /**
     * Form tao user moi.
     */
    public function create(Request $request): View
    {
        // Muc tieu: Nap form tao moi theo quy trinh nghiep vu cua mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        return view('admin.users.create');
    }

    /**
     * Luu user moi do admin tao.
     */
    public function store(Request $request): RedirectResponse
    {
        // Muc tieu: Luu du lieu nghiep vu theo quy tac cua mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        // Validate day du thong tin user + role + trang thai.
        $validated = $request->validate([
            'ho_ten' => ['required', 'string', 'max:150'],
            'ten_don_vi' => ['nullable', 'string', 'max:150'],
            'ten_dang_nhap' => ['required', 'string', 'max:150', 'unique:nguoi_dung,ten_dang_nhap'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150', 'unique:nguoi_dung,email'],
            'mst' => ['nullable', 'string', 'max:50'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'mat_khau' => ['required', 'string', 'min:6'],
            'vai_tro' => ['required', 'string', Rule::in(['admin', 'chu_shop', 'quan_ly_chanh_xe'])],
            'trang_thai' => ['required', 'integer', Rule::in([0, 1])],
        ]);

        // Tao user moi, mat khau duoc hash truoc khi luu.
        $createdUser = Nguoi_Dung::query()->create([
            'ho_ten' => $validated['ho_ten'],
            'ten_don_vi' => $validated['ten_don_vi'] ?? null,
            'ten_dang_nhap' => $validated['ten_dang_nhap'],
            'so_dien_thoai' => $validated['so_dien_thoai'] ?? null,
            'email' => $validated['email'] ?? null,
            'mst' => $validated['mst'] ?? null,
            'dia_chi' => $validated['dia_chi'] ?? null,
            'mat_khau' => Hash::make($validated['mat_khau']),
            'vai_tro' => $validated['vai_tro'],
            'trang_thai' => (int) $validated['trang_thai'],
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ly_do_tu_choi' => null,
        ]);

        $this->syncApprovedTransportManagerToCarrier($createdUser);

        return redirect()->route('users.index')->with('success', 'Đã thêm người dùng.');
    }

    /**
     * Xem chi tiet 1 nguoi dung.
     */
    public function show(Request $request, Nguoi_Dung $user): View
    {
        // Muc tieu: Tai chi tiet ban ghi de hien thi theo mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        return view('admin.users.show', ['userModel' => $user]);
    }

    /**
     * Duyet tai khoan dang ky.
     */
    public function approve(Request $request, Nguoi_Dung $user): RedirectResponse
    {
        // Muc tieu: Xu ly nghiep vu ham approve trong mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        $user->update([
            'trang_thai_duyet' => Nguoi_Dung::DUYET_DA_DUYET,
            'ly_do_tu_choi' => null,
        ]);

        $this->syncApprovedTransportManagerToCarrier($user);

        return redirect()->back()->with('success', 'Đã duyệt tài khoản người dùng.');
    }

    /**
     * Tu choi tai khoan dang ky va luu ly do.
     */
    public function reject(Request $request, Nguoi_Dung $user): RedirectResponse
    {
        // Muc tieu: Xu ly nghiep vu ham reject trong mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        // Bat buoc co ly do tu choi de gui lai cho shop.
        $validated = $request->validate([
            'ly_do_tu_choi' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'trang_thai_duyet' => Nguoi_Dung::DUYET_TU_CHOI,
            'ly_do_tu_choi' => $validated['ly_do_tu_choi'],
        ]);

        return redirect()->back()->with('success', 'Đã từ chối tài khoản người dùng.');
    }

    /**
     * Form sua user.
     */
    public function edit(Request $request, Nguoi_Dung $user): View
    {
        // Muc tieu: Nap du lieu hien tai de chinh sua theo mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        return view('admin.users.edit', ['userModel' => $user]);
    }

    /**
     * Cap nhat user.
     */
    public function update(Request $request, Nguoi_Dung $user): RedirectResponse
    {
        // Muc tieu: Cap nhat du lieu da validate theo quy tac cua mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        // Validate du lieu cap nhat, bo qua unique voi chinh user hien tai.
        $validated = $request->validate([
            'ho_ten' => ['required', 'string', 'max:150'],
            'ten_don_vi' => ['nullable', 'string', 'max:150'],
            'ten_dang_nhap' => ['required', 'string', 'max:150', Rule::unique('nguoi_dung', 'ten_dang_nhap')->ignore($user->ma_nguoi_dung, 'ma_nguoi_dung')],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('nguoi_dung', 'email')->ignore($user->ma_nguoi_dung, 'ma_nguoi_dung')],
            'mst' => ['nullable', 'string', 'max:50'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'mat_khau' => ['nullable', 'string', 'min:6'],
            'vai_tro' => ['required', 'string', Rule::in(['admin', 'chu_shop', 'quan_ly_chanh_xe'])],
            'trang_thai' => ['required', 'integer', Rule::in([0, 1])],
            'trang_thai_duyet' => ['required', 'integer', Rule::in([0, 1, 2])],
            'ly_do_tu_choi' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => (int) $request->integer('trang_thai_duyet') === 2)],
        ]);

        // Tao payload cap nhat co kiem soat.
        $payload = [
            'ho_ten' => $validated['ho_ten'],
            'ten_don_vi' => $validated['ten_don_vi'] ?? null,
            'ten_dang_nhap' => $validated['ten_dang_nhap'],
            'so_dien_thoai' => $validated['so_dien_thoai'] ?? null,
            'email' => $validated['email'] ?? null,
            'mst' => $validated['mst'] ?? null,
            'dia_chi' => $validated['dia_chi'] ?? null,
            'vai_tro' => $validated['vai_tro'],
            'trang_thai' => (int) $validated['trang_thai'],
            'trang_thai_duyet' => (int) $validated['trang_thai_duyet'],
            'ly_do_tu_choi' => (int) $validated['trang_thai_duyet'] === 2 ? ($validated['ly_do_tu_choi'] ?? null) : null,
        ];

        // Chi hash/lưu mat khau moi neu admin co nhap.
        if (! empty($validated['mat_khau'])) {
            $payload['mat_khau'] = Hash::make($validated['mat_khau']);
        }

        $user->update($payload);

        return redirect()->route('users.show', $user)->with('success', 'Đã cập nhật người dùng.');
    }

    /**
     * Xoa user.
     */
    public function destroy(Request $request, Nguoi_Dung $user): RedirectResponse
    {
        // Muc tieu: Xoa ban ghi sau khi kiem tra rang buoc cua mang quan tri tai khoan.
        $this->abortIfNotAdmin($request);

        // Chan admin tu xoa chinh minh de tranh mat quyen quan tri.
        if ((int) $request->user()->ma_nguoi_dung === (int) $user->ma_nguoi_dung) {
            return redirect()->route('users.index')->with('success', 'Không thể tự xóa chính mình.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa người dùng.');
    }

    /**
     * Neu user la quan ly chanh xe da duyet thi dam bao co ban ghi trong danh muc nha_xe.
     */
    private function syncApprovedTransportManagerToCarrier(Nguoi_Dung $user): void
    {
        // Muc tieu: Dong bo trang thai tu he thong doi tac cho mang quan tri tai khoan.
        if ((string) $user->vai_tro !== 'quan_ly_chanh_xe' || (int) $user->trang_thai_duyet !== Nguoi_Dung::DUYET_DA_DUYET) {
            return;
        }

        $name = trim((string) ($user->ten_don_vi ?: $user->ho_ten));

        if ($name === '') {
            return;
        }

        $carrier = Nha_Xe::query()->firstOrCreate(
            ['ten_nha_xe' => $name],
            [
                'so_dien_thoai' => $user->so_dien_thoai,
                'tuyen_duong' => null,
            ]
        );

        if (! $carrier->so_dien_thoai && $user->so_dien_thoai) {
            $carrier->so_dien_thoai = $user->so_dien_thoai;
            $carrier->save();
        }
    }
}




