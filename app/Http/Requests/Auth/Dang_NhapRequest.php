<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: app/Http/Requests/Auth/Dang_NhapRequest.php
| - Buoc 1: Chuan hoa du lieu dau vao truoc validate (prepareForValidation neu co).
| - Buoc 2: Ap dung bo rules de dam bao du lieu hop le theo nghiep vu.
| - Buoc 3: Tra thong bao loi than thien de UI hien thi dung field.
*/

/*
|--------------------------------------------------------------------------
| FORM REQUEST DANG NHAP
|--------------------------------------------------------------------------
| Chiu trach nhiem validate va xac thuc thong tin dang nhap.
| Chay truoc khi vao Dang_NhapController::store.
*/

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Nguoi_Dung;

class Dang_NhapRequest extends FormRequest
{
    /**
     * Cho phep moi guest gui request dang nhap.
     */
    public function authorize(): bool
    {
        // Muc tieu: Xac thuc request co du quyen thuc hien nghiep vu module_chung.
        return true;
    }

    /**
        * Dinh nghia rule validate du lieu dau vao truoc khi xac thuc.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Muc tieu: Dinh nghia bo luat validate phu hop du lieu gui di cua mang validate request.
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Xu ly toan bo logic xac thuc dang nhap.
     *
     * Cac buoc chinh:
     * - Chan brute-force bang rate limit.
     * - Tim user theo ten dang nhap.
     * - Kiem tra mat khau + trang thai tai khoan + trang thai duyet.
     * - Goi Auth::attempt de tao phien dang nhap.
     * - Xoa bo dem rate limit neu thanh cong.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        // Buoc 1: kiem tra so lan thu dang nhap that bai.
        $this->ensureIsNotRateLimited();

        // Buoc 2: doc du lieu login/password tu request.
        $login = $this->string('login')->toString();
        $password = $this->string('password')->toString();

        // Buoc 3: tim user theo ten dang nhap trong bang nguoi_dung.
        $user = Nguoi_Dung::query()
            ->where('ten_dang_nhap', $login)
            ->first();

        // Neu khong co user hoac mat khau sai -> tang bo dem that bai va bao loi.
        if (! $user || ! Hash::check($password, (string) $user->mat_khau)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Tai khoan bi khoa thi khong cho dang nhap.
        if ((int) $user->trang_thai !== 1) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        // Tai khoan dang cho duyet thi thong bao cho nguoi dung.
        if ((int) ($user->trang_thai_duyet ?? Nguoi_Dung::DUYET_CHO_DUYET) === Nguoi_Dung::DUYET_CHO_DUYET) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Tài khoản đang chờ duyệt. Bạn sẽ được sử dụng sau khi quản trị viên kích hoạt.',
            ]);
        }

        // Tai khoan bi tu choi duyet: hien ly do neu co.
        if ((int) ($user->trang_thai_duyet ?? Nguoi_Dung::DUYET_CHO_DUYET) === Nguoi_Dung::DUYET_TU_CHOI) {
            $reason = $user->ly_do_tu_choi ? ' Lý do: '.$user->ly_do_tu_choi : '';

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Hồ sơ đăng ký chưa được duyệt.'.$reason,
            ]);
        }

        // Buoc xac thuc cuoi cung cua Laravel (tao session dang nhap neu dung).
        if (! Auth::attempt([
            'ten_dang_nhap' => $login,
            'password' => $this->string('password')->toString(),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Dang nhap thanh cong -> xoa bo dem gioi han thu dang nhap.
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Kiem tra request hien tai co bi khoa tam thoi do dang nhap sai nhieu lan khong.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Nho hon 5 lan sai thi cho phep thu tiep.
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        // Vuot nguong -> ban su kien lockout de he thong ghi nhan.
        event(new Lockout($this));

        // Lay so giay con lai truoc khi thu lai duoc.
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Tao khoa duy nhat cho co che rate-limit theo login + IP.
     */
    public function throttleKey(): string
    {
        // transliterate + lower de dong bo dinh dang, tranh sai key do dau tieng Viet.
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}




