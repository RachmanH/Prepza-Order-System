<x-guest-layout>
    <x-authentication-card>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Selamat datang kembali</h1>
            <p class="mt-1.5 text-sm text-slate-500">Masuk ke akun Prepza Anda untuk melanjutkan.</p>
        </div>

        <x-validation-errors class="mb-5" />

        @session('status')
            <div class="mb-5 flex items-center gap-2.5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                    placeholder="nama@email.com" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <x-label for="password" value="{{ __('Password') }}" class="mb-0" />
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700 transition">
                            Lupa password?
                        </a>
                    @endif
                </div>
                <x-input id="password" type="password" name="password" required autocomplete="current-password"
                    placeholder="••••••••" />
            </div>

            <div class="flex items-center gap-2.5">
                <x-checkbox id="remember_me" name="remember" />
                <label for="remember_me" class="text-sm text-slate-600 cursor-pointer select-none">Ingat saya</label>
            </div>

            <x-button class="w-full justify-center py-3">
                Masuk
            </x-button>
        </form>

        @if (Route::has('register'))
            <p class="mt-6 text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700 transition">Daftar sekarang</a>
            </p>
        @endif
    </x-authentication-card>
</x-guest-layout>
