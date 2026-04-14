<x-guest-layout>
    <x-authentication-card>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Buat akun baru</h1>
            <p class="mt-1.5 text-sm text-slate-500">Daftarkan diri Anda untuk mulai menggunakan Prepza.</p>
        </div>

        <x-validation-errors class="mb-5" />

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="name" value="{{ __('Nama Lengkap') }}" />
                <x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name"
                    placeholder="Nama Anda" />
            </div>

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username"
                    placeholder="nama@email.com" />
            </div>

            <div>
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" type="password" name="password" required autocomplete="new-password"
                    placeholder="Min. 8 karakter" />
            </div>

            <div>
                <x-label for="password_confirmation" value="{{ __('Konfirmasi Password') }}" />
                <x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    placeholder="Ulangi password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="flex items-start gap-2.5">
                    <x-checkbox name="terms" id="terms" required class="mt-0.5" />
                    <label for="terms" class="text-sm text-slate-600 cursor-pointer leading-relaxed">
                        Saya menyetujui
                        <a href="{{ route('terms.show') }}" target="_blank" class="font-medium text-brand-600 hover:text-brand-700">Syarat Layanan</a>
                        dan
                        <a href="{{ route('policy.show') }}" target="_blank" class="font-medium text-brand-600 hover:text-brand-700">Kebijakan Privasi</a>
                    </label>
                </div>
            @endif

            <x-button class="w-full justify-center py-3">
                Buat Akun
            </x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700 transition">Masuk di sini</a>
        </p>
    </x-authentication-card>
</x-guest-layout>
