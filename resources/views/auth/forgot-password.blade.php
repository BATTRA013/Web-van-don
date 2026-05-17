<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Quên mật khẩu</h1>
        <p class="mt-2 text-sm text-slate-500">Nhập email để nhận liên kết đặt lại mật khẩu.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-700" />
            <x-text-input id="email" class="mt-1 block w-full rounded-xl border-slate-300 px-4 py-2.5" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center rounded-xl py-3 text-sm font-semibold">
                  Gửi liên kết đặt lại mật khẩu
        </x-primary-button>

        <p class="text-center text-sm text-slate-600">
            <a class="font-semibold text-slate-900 hover:underline" href="{{ route('login') }}">Quay lại đăng nhập</a>
        </p>
    </form>
</x-guest-layout>
