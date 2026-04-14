<div class="min-h-screen flex">
    {{-- Left brand panel --}}
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 flex-col justify-between bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500 p-12 relative overflow-hidden">
        {{-- Decorative circles --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-32 -right-16 w-[28rem] h-[28rem] rounded-full bg-white/5"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full bg-white/5"></div>

        <div class="relative z-10">
            <a href="/" class="inline-flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <span class="text-xl font-bold text-white tracking-tight">Prepza</span>
            </a>
        </div>

        <div class="relative z-10 space-y-6">
            <div class="space-y-3">
                <h2 class="text-3xl font-bold text-white leading-snug">
                    Kelola order lebih cepat,<br>lebih rapi.
                </h2>
                <p class="text-brand-100 text-sm leading-relaxed max-w-xs">
                    Platform manajemen pesanan terpusat untuk bisnis Anda. Dari input hingga antrian, semua dalam satu sistem.
                </p>
            </div>

            <div class="space-y-3">
                @foreach ([['icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Alur order terpusat & terstruktur'], ['icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Manajemen antrian real-time'], ['icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Laporan & analitik mudah dibaca']] as $item)
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-brand-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="text-sm text-brand-100">{{ $item['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative z-10">
            <p class="text-xs text-brand-200">&copy; {{ date('Y') }} Prepza. All rights reserved.</p>
        </div>
    </div>

    {{-- Right form panel --}}
    <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-24">
        {{-- Mobile logo --}}
        <div class="mb-8 flex items-center gap-2 lg:hidden">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-900 tracking-tight">Prepza</span>
        </div>

        <div class="w-full max-w-sm mx-auto">
            {{ $slot }}
        </div>
    </div>
</div>
