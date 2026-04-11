<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Sistem Order') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --ink: #0f172a;
                --soft-ink: #334155;
                --accent: #0ea5e9;
                --accent-2: #22d3ee;
                --paper: #f8fafc;
                --card: #ffffff;
            }

            body {
                font-family: 'Space Grotesk', sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at 12% 12%, rgba(34, 211, 238, 0.2), transparent 36%),
                    radial-gradient(circle at 88% 14%, rgba(14, 165, 233, 0.16), transparent 33%),
                    linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            }

            .grid-line {
                background-image:
                    linear-gradient(to right, rgba(15, 23, 42, 0.04) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(15, 23, 42, 0.04) 1px, transparent 1px);
                background-size: 28px 28px;
            }

            .glass {
                background: rgba(255, 255, 255, 0.86);
                backdrop-filter: blur(10px);
            }
        </style>
    </head>
    <body class="min-h-screen grid-line">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-7 lg:px-10">
            <header class="mb-10 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Aplikasi Internal</p>
                    <h1 class="text-xl font-bold">Sistem Order</h1>
                </div>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3 text-sm font-semibold">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-white transition hover:-translate-y-0.5 hover:bg-slate-800">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-400 hover:bg-white">Masuk</a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-xl bg-sky-500 px-4 py-2 text-white transition hover:-translate-y-0.5 hover:bg-sky-600">Daftar</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="grid flex-1 items-center gap-8 pb-8 lg:grid-cols-5">
                <section class="lg:col-span-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                        Siap dipakai untuk manajemen pesanan harian
                    </div>

                    <h2 class="mt-6 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">
                        Kelola order lebih cepat,
                        <span class="text-sky-600">lebih rapi</span>,
                        dan minim salah input.
                    </h2>

                    <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600">
                        Halaman awal ini sudah disiapkan khusus untuk proyek Sistem Order. Lanjutkan dengan login untuk mulai mengelola data pelanggan, produk, dan transaksi.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-slate-800">Buka Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-slate-800">Masuk Sekarang</a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-400">Buat Akun</a>
                            @endif
                        @endauth
                    </div>
                </section>

                <aside class="glass rounded-3xl border border-white/70 p-6 shadow-xl shadow-slate-900/10 lg:col-span-2">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Ringkasan Cepat</p>
                    <ul class="mt-4 space-y-3 text-sm text-slate-700">
                        <li class="rounded-xl bg-sky-50 p-3">
                            <p class="font-semibold text-slate-900">Alur Terpusat</p>
                            <p>Semua proses order ada di satu sistem.</p>
                        </li>
                        <li class="rounded-xl bg-cyan-50 p-3">
                            <p class="font-semibold text-slate-900">Data Tertata</p>
                            <p>Input pelanggan, produk, dan transaksi lebih konsisten.</p>
                        </li>
                        <li class="rounded-xl bg-slate-100 p-3">
                            <p class="font-semibold text-slate-900">Siap Dikembangkan</p>
                            <p>Fondasi Laravel + Jetstream sudah aktif untuk pengembangan fitur lanjutan.</p>
                        </li>
                    </ul>
                </aside>
            </main>
        </div>
    </body>
</html>
