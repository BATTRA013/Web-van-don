<x-guest-layout>
    <style>
        .register-layout {
            display: grid;
            gap: 2rem;
        }

        .register-fields-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 1024px) {
            .register-layout {
                grid-template-columns: minmax(280px, 1fr) minmax(0, 2fr);
                gap: 2.5rem;
            }
        }

        @media (min-width: 900px) {
            .register-fields-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="register-layout">
        <aside class="relative overflow-hidden rounded-2xl p-6 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #155e75 100%);">
            <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-cyan-300/20 blur-2xl"></div>
            <div class="absolute -bottom-14 -left-10 h-40 w-40 rounded-full bg-sky-300/20 blur-2xl"></div>

            <div class="relative space-y-5">
                <h1 class="text-2xl font-bold leading-tight">Tạo tài khoản mới</h1>
                <p class="text-sm leading-6 text-slate-200">
                    Điền thông tin đăng ký để bắt đầu sử dụng hệ thống Web Vận Đơn. Sau khi đăng ký, quản trị viên sẽ duyệt trước khi bạn thao tác nghiệp vụ.
                </p>

                <div class="space-y-3 text-sm text-slate-100">
                    <div class="rounded-xl border border-white/25 px-4 py-3" style="background-color: rgba(255, 255, 255, 0.14); color: #f8fafc;">Khai báo thông tin đơn vị và người đại diện.</div>
                    <div class="rounded-xl border border-white/25 px-4 py-3" style="background-color: rgba(255, 255, 255, 0.14); color: #f8fafc;">Chọn đúng vai trò để phân quyền chính xác.</div>
                    <div class="rounded-xl border border-white/25 px-4 py-3" style="background-color: rgba(255, 255, 255, 0.14); color: #f8fafc;">Hệ thống gửi thông báo ngay khi tài khoản được duyệt.</div>
                </div>
            </div>
        </aside>

        <div>
            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <div class="register-fields-grid">
                    <div>
                        <x-input-label for="ho_ten" :value="__('Họ và tên')" class="text-slate-700" />
                        <x-text-input id="ho_ten" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="ho_ten" :value="old('ho_ten')" required autofocus autocomplete="name" />
                        <x-input-error :messages="$errors->get('ho_ten')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="ten_don_vi" :value="__('Tên shop/chành xe')" class="text-slate-700" />
                        <x-text-input id="ten_don_vi" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="ten_don_vi" :value="old('ten_don_vi')" required autocomplete="organization" />
                        <x-input-error :messages="$errors->get('ten_don_vi')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="vai_tro" :value="__('Bạn là')" class="text-slate-700" />
                        <select id="vai_tro" name="vai_tro" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5 text-slate-900 focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="" disabled @selected(old('vai_tro') === null)>Chọn loại tài khoản</option>
                            <option value="chu_shop" @selected(old('vai_tro') === 'chu_shop')>Chủ shop</option>
                            <option value="quan_ly_chanh_xe" @selected(old('vai_tro') === 'quan_ly_chanh_xe')>Quản lý chành xe</option>
                        </select>
                        <x-input-error :messages="$errors->get('vai_tro')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="login" :value="__('Tài khoản')" class="text-slate-700" />
                        <x-text-input id="login" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="login" :value="old('login')" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('login')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="so_dien_thoai" :value="__('Số điện thoại')" class="text-slate-700" />
                        <x-text-input id="so_dien_thoai" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="so_dien_thoai" :value="old('so_dien_thoai')" required autocomplete="tel" />
                        <x-input-error :messages="$errors->get('so_dien_thoai')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" class="text-slate-700" />
                        <x-text-input id="email" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="email" name="email" :value="old('email')" required autocomplete="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="mst" :value="__('MST (nếu có)')" class="text-slate-700" />
                        <x-text-input id="mst" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="mst" :value="old('mst')" autocomplete="off" />
                        <x-input-error :messages="$errors->get('mst')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="dia_chi" :value="__('Địa chỉ')" class="text-slate-700" />
                        <x-text-input id="dia_chi" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="dia_chi" :value="old('dia_chi')" required autocomplete="street-address" />
                        <x-input-error :messages="$errors->get('dia_chi')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Mật khẩu')" class="text-slate-700" />
                        <x-text-input id="password" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5"
                                        type="password"
                                        name="password"
                                        required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Xác nhận mật khẩu')" class="text-slate-700" />
                        <x-text-input id="password_confirmation" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5"
                                        type="password"
                                        name="password_confirmation" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div class="space-y-4 border-t border-slate-200 pt-5">
                    <label for="dong_y_dieu_khoan" class="inline-flex items-start gap-2 text-sm text-slate-600">
                        <input id="dong_y_dieu_khoan" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900" name="dong_y_dieu_khoan" value="1" @checked(old('dong_y_dieu_khoan'))>
                        <span>Tôi xác nhận thông tin đã khai báo là chính xác và đồng ý chờ quản trị viên duyệt tài khoản trước khi sử dụng nghiệp vụ.</span>
                    </label>
                    <x-input-error :messages="$errors->get('dong_y_dieu_khoan')" class="mt-2" />

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-600">
                            Đã có tài khoản?
                            <a class="font-semibold text-slate-900 hover:underline" href="{{ route('login') }}">Đăng nhập</a>
                        </p>

                        <x-primary-button class="w-full justify-center rounded-xl px-8 py-3 text-sm font-semibold sm:w-auto">
                            Đăng ký
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
