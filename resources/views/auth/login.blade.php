<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Đăng nhập hệ thống</h1>
        <p class="mt-2 text-sm text-slate-500">Quản lý vận đơn và đối soát COD.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="login" :value="__('Tài khoản')" class="text-slate-700" />
            <x-text-input id="login" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Mật khẩu')" class="mb-1 text-slate-700" />

            <x-text-input id="password" class="block w-full rounded-xl border-slate-300 px-4 py-2.5"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900" name="remember">
            <span>Ghi nhớ đăng nhập</span>
        </label>

        <x-primary-button class="w-full justify-center rounded-xl py-3 text-sm font-semibold">
            Đăng nhập
        </x-primary-button>

        {{--
        @if (Route::has('password.request'))
            <div class="text-center">
                <a class="text-xs font-medium text-slate-600 hover:text-slate-900" href="{{ route('password.request') }}">
                    Quên mật khẩu?
                </a>
            </div>
        @endif
        --}}

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-600">
                Chưa có tài khoản?
                <a class="font-semibold text-slate-900 hover:underline" href="{{ route('register') }}">Đăng ký</a>
            </p>
        @endif
    </form>
</x-guest-layout>
