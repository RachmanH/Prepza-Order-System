<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500 mt-0.5">Selamat datang kembali, {{ Auth::user()->name }}.</p>
            </div>
            <a href="{{ route('order.kiosk') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                      hover:bg-brand-700 hover:-translate-y-px hover:shadow-md transition duration-150">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Order Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Quick Action Banner --}}
            <div class="rounded-2xl bg-gradient-to-r from-brand-600 to-brand-500 p-6 shadow-card-md">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-100">Aksi Cepat</p>
                        <h3 class="mt-1 text-xl font-bold text-white">Buka Order Kiosk</h3>
                        <p class="mt-1 text-sm text-brand-100">Terima pesanan pelanggan langsung dari kiosk.</p>
                    </div>
                    <a href="{{ route('order.kiosk') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 shadow-sm
                              hover:bg-brand-50 hover:-translate-y-px transition duration-150 shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                        Buka Kiosk
                    </a>
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    [
                        'label' => 'Total Order Hari Ini',
                        'value' => $orderHariIni,
                        'icon'  => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
                        'color' => 'brand',
                        'desc'  => 'Sejak 00:00 hari ini',
                    ],
                    [
                        'label' => 'Menunggu',
                        'value' => $orderWaiting,
                        'icon'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                        'color' => 'amber',
                        'desc'  => 'Belum diproses',
                    ],
                    [
                        'label' => 'Sedang Diproses',
                        'value' => $orderProses,
                        'icon'  => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
                        'color' => 'violet',
                        'desc'  => 'Sedang dikerjakan',
                    ],
                    [
                        'label' => 'Selesai Hari Ini',
                        'value' => $orderSelesai,
                        'icon'  => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        'color' => 'emerald',
                        'desc'  => 'Sudah selesai hari ini',
                    ],
                ] as $stat)
                    <div class="card p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-slate-500">{{ $stat['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $stat['desc'] }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl
                                @if($stat['color'] === 'brand') bg-brand-50 text-brand-600
                                @elseif($stat['color'] === 'amber') bg-amber-50 text-amber-600
                                @elseif($stat['color'] === 'emerald') bg-emerald-50 text-emerald-600
                                @else bg-violet-50 text-violet-600 @endif">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" />
                                </svg>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Quick Links --}}
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Navigasi Cepat</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                    @foreach ([
                        ['href' => 'order.kiosk', 'label' => 'Order Kiosk', 'desc' => 'Terima pesanan baru', 'icon' => 'M12 4.5v15m7.5-7.5h-15', 'color' => 'brand'],
                        ['href' => 'queue.management', 'label' => 'Manajemen Antrian', 'desc' => 'Kelola antrian aktif', 'icon' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z', 'color' => 'amber'],
                        ['href' => 'queue.board', 'label' => 'Display Antrian', 'desc' => 'Tampilan layar antrian', 'icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3', 'color' => 'emerald'],
                        ['href' => 'profile.show', 'label' => 'Profil Saya', 'desc' => 'Pengaturan akun', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'color' => 'violet'],
                    ] as $link)
                        <a href="{{ route($link['href']) }}"
                           class="flex items-center gap-4 px-6 py-5 hover:bg-slate-50 transition duration-150 group">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                @if($link['color'] === 'brand') bg-brand-50 text-brand-600 group-hover:bg-brand-100
                                @elseif($link['color'] === 'amber') bg-amber-50 text-amber-600 group-hover:bg-amber-100
                                @elseif($link['color'] === 'emerald') bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100
                                @else bg-violet-50 text-violet-600 group-hover:bg-violet-100 @endif
                                transition duration-150">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $link['label'] }}</p>
                                <p class="text-xs text-slate-500">{{ $link['desc'] }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
