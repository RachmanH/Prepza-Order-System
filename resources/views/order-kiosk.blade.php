<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Order Kiosk') }}
            </h2>
            <p class="text-sm text-gray-500">Voice-first ordering with deterministic validation</p>
        </div>
    </x-slot>

    <div class="py-5" x-data="orderKiosk()" x-init="init()">
        <!-- Modal Konfirmasi Order — Wide Layout -->
        <div x-show="showConfirmModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeConfirmModal()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="flex w-full max-w-4xl flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl"
                 style="max-height: 90vh;"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Konfirmasi Pesanan</h3>
                        <p class="text-xs text-slate-500">Review dan edit item sebelum dikonfirmasi</p>
                    </div>
                    <button type="button" @click="closeConfirmModal()"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body: split layout -->
                <div class="flex flex-1 overflow-hidden">

                    <!-- LEFT: Customer Info (fixed, tidak scroll) -->
                    <div class="flex w-56 shrink-0 flex-col gap-4 border-r border-slate-100 bg-slate-50 p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pelanggan</p>
                            <div class="mt-3 space-y-3">
                                <div class="rounded-xl border border-slate-200 bg-white p-3">
                                    <p class="text-xs text-slate-500">Nama</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-900" x-text="customerName || '-'"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-3">
                                    <p class="text-xs text-slate-500">Jenis Kelamin</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-900" x-text="genderLabel(customerGender)"></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">Total Item</p>
                            <p class="mt-0.5 text-2xl font-bold text-brand-600" x-text="confirmingItems.reduce((s, i) => s + i.qty, 0)"></p>
                        </div>
                    </div>

                    <!-- RIGHT: Item Grid (scrollable) -->
                    <div class="flex-1 overflow-y-auto p-5">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Daftar Item (<span x-text="confirmingItems.length"></span>)
                        </p>

                        <template x-if="confirmingItems.length === 0">
                            <div class="flex h-32 items-center justify-center rounded-xl border-2 border-dashed border-slate-200">
                                <p class="text-sm text-slate-400">Belum ada item</p>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="(item, itemIndex) in confirmingItems" :key="`${item.name}-${itemIndex}`">
                                <div class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <!-- Item name + remove -->
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold capitalize text-slate-900 leading-snug" x-text="item.name"></p>
                                        <button type="button"
                                                @click="removeConfirmingItem(item.name)"
                                                class="shrink-0 rounded-lg border border-rose-100 p-1 text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Qty controls -->
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                @click="changeConfirmingItemQty(item.name, -1)"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition text-sm font-bold">−</button>
                                        <span class="min-w-[2rem] text-center text-sm font-bold text-slate-900" x-text="item.qty"></span>
                                        <button type="button"
                                                @click="changeConfirmingItemQty(item.name, 1)"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition text-sm font-bold">+</button>
                                        <span class="ml-auto rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700" x-text="`×${item.qty}`"></span>
                                    </div>

                                    <!-- Note input -->
                                    <input type="text"
                                           :value="item.note || ''"
                                           @input="updateConfirmingItemNote(item.name, $event.target.value)"
                                           class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-700 placeholder:text-slate-400 focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 transition"
                                           placeholder="Catatan (contoh: tidak pedas)">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <p class="text-xs text-slate-500">
                        <span x-text="confirmingItems.length"></span> jenis item,
                        <span x-text="confirmingItems.reduce((s, i) => s + i.qty, 0)"></span> total qty
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="closeConfirmModal()"
                                class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Batal
                        </button>
                        <button type="button" @click="confirmOrderSubmit()"
                                :disabled="submitting || confirmingItems.length === 0"
                                class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50 transition">
                            <span x-text="submitting ? 'Memproses...' : 'Konfirmasi Pesanan'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <style>
                .kiosk-grid {
                    background-image: radial-gradient(circle at 10% 10%, rgba(14, 165, 233, 0.08), transparent 36%), radial-gradient(circle at 90% 0%, rgba(34, 211, 238, 0.12), transparent 33%);
                }
                .pulse-ring {
                    animation: pulse-ring 1.6s ease-out infinite;
                }
                @keyframes pulse-ring {
                    0% { transform: scale(1); opacity: 1; }
                    100% { transform: scale(1.25); opacity: 0; }
                }
                .chip-in {
                    animation: chip-in 0.35s ease-out;
                }
                @keyframes chip-in {
                    0% { opacity: 0; transform: translateY(6px) scale(0.97); }
                    100% { opacity: 1; transform: translateY(0) scale(1); }
                }
                .queue-pop {
                    animation: queue-pop 0.45s cubic-bezier(0.2, 0.8, 0.2, 1);
                }
                @keyframes queue-pop {
                    0% { transform: scale(0.88); opacity: 0.3; }
                    100% { transform: scale(1); opacity: 1; }
                }

            </style>

<!-- Order Success Modal -->
            <div x-show="showSuccessModal" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeSuccessModal()"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-4xl rounded-2xl border border-slate-200 bg-white shadow-2xl"
                     style="max-height: 90vh;"
                     @click.stop
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-base font-bold text-slate-900">Pesanan Berhasil!</p>
                                <p class="text-xs text-slate-500">Silakan tunggu giliran Anda</p>
                            </div>
                        </div>
                        <button type="button" @click="closeSuccessModal()"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body: 3 kolom -->
                    <div class="grid grid-cols-3 gap-0 overflow-hidden" style="max-height: calc(90vh - 130px);">

                        <!-- LEFT: Nomor Antrian -->
                        <div class="flex flex-col items-center justify-center gap-3 border-r border-slate-100 bg-gradient-to-br from-brand-50 to-cyan-50 p-8">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Nomor Antrian</p>
                            <p class="text-7xl font-black tracking-wider text-brand-600 queue-pop" x-text="queueNumber ?? '-'" :key="queueNumber"></p>
                            <div class="mt-2 rounded-xl border border-brand-200 bg-white px-4 py-2 text-center">
                                <p class="text-xs text-slate-500">Order ID</p>
                                <p class="font-mono text-xs font-bold text-slate-800" x-text="orderCode || '-'"></p>
                            </div>
                        </div>

                        <!-- MIDDLE: Customer + Items (scrollable) -->
                        <div class="flex flex-col overflow-hidden border-r border-slate-100">
                            <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Detail Pesanan</p>
                            </div>
                            <div class="space-y-2 overflow-y-auto p-4">
                                <div class="rounded-xl border border-slate-200 bg-white p-3">
                                    <p class="text-xs text-slate-500">Nama</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-900" x-text="lastOrderCustomerName || '-'"></p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                    <p class="mb-2 text-xs font-semibold text-slate-500">Item Pesanan</p>
                                    <template x-if="finalItems.length === 0">
                                        <p class="text-xs text-slate-400">-</p>
                                    </template>
                                    <div class="space-y-1.5">
                                        <template x-for="item in finalItems" :key="item.name + item.qty + (item.note || '')">
                                            <div class="flex items-start justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                                <div>
                                                    <p class="text-xs font-semibold capitalize text-slate-800" x-text="item.name"></p>
                                                    <p x-show="item.note" class="text-[11px] text-slate-500" x-text="item.note"></p>
                                                </div>
                                                <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700" x-text="`×${item.qty}`"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: Actions -->
                        <div class="flex flex-col justify-between p-5">
                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cetak Struk</p>
                                <button type="button" @click="printSuccessModal('pdf')"
                                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    Cetak PDF
                                </button>
                                <button type="button" @click="printSuccessModal('thermal')"
                                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                    </svg>
                                    Cetak Thermal
                                </button>
                            </div>

                            <button type="button" @click="closeSuccessModal()"
                                    class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-slate-800 transition">
                                Lanjut Order Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Layout: Menu (Left - Dominant) + Voice Input (Right - Compact) -->
            <section class="kiosk-grid rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:p-7">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    
                    <!-- MENU GRID - DOMINANT (Left, 7-8 cols) -->
                    <aside class="lg:col-span-7 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">📋 Daftar Menu</h3>
                                <p class="mt-1 text-xs text-slate-500">Pilih dari menu yang tersedia</p>
                            </div>
                            <template x-if="loadingMenus">
                                <div class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-sky-600"></div>
                            </template>
                        </div>

                        <!-- Category Filter Pills -->
                        <div class="mt-4 flex gap-2 overflow-auto pb-2 pr-2">
                            <button @click="selectCategory(null)"
                                class="inline-flex flex-shrink-0 items-center rounded-full px-4 py-2 text-xs font-semibold transition"
                                :class="selectedCategoryId === null 
                                    ? 'bg-sky-600 text-white' 
                                    : 'border border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'">
                                Semua
                            </button>
                            <template x-for="category in categories" :key="category.id">
                                <button @click="selectCategory(category.id)"
                                    class="inline-flex flex-shrink-0 items-center rounded-full px-4 py-2 text-xs font-semibold transition"
                                    :class="selectedCategoryId === category.id 
                                        ? 'bg-sky-600 text-white' 
                                        : 'border border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'"
                                    x-text="category.name">
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 max-h-[500px] space-y-0 overflow-auto pr-2">
                            <template x-if="!loadingMenus && filteredMenus().length === 0">
                                <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-6 text-center">
                                    <p class="text-sm font-semibold text-slate-600" x-show="menus.length === 0">Menu belum tersedia</p>
                                    <p class="text-sm font-semibold text-slate-600" x-show="menus.length > 0">Tidak ada menu di kategori ini</p>
                                    <p class="mt-1 text-xs text-slate-500" x-show="menus.length === 0">Tambahkan data menu terlebih dahulu</p>
                                </div>
                            </template>

                            <!-- Grid Menu Cards: 3-4 per row -->
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-4">
                                <template x-for="menu in filteredMenus()" :key="menu.id">
                                    <div class="group cursor-pointer overflow-hidden rounded-xl border-2 p-0 transition-all"
                                        @click="addMenuToDraft(menu)"
                                        :class="isMenuHighlighted(menu.name) 
                                            ? 'border-cyan-400 bg-cyan-50 shadow-md' 
                                            : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'">
                                        
                                        <!-- Image Container -->
                                        <div class="relative overflow-hidden rounded-t-lg bg-slate-100">
                                            <img x-show="menu.image_url" 
                                                 :src="menu.image_url" 
                                                 alt="Gambar menu" 
                                                 class="h-32 w-full object-cover transition-transform group-hover:scale-105" 
                                                 loading="lazy">
                                            <div x-show="!menu.image_url" class="flex h-32 items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            
                                            <!-- Highlight Badge -->
                                            <template x-if="isMenuHighlighted(menu.name)">
                                                <div class="absolute right-2 top-2 rounded-full bg-cyan-400 px-2 py-1">
                                                    <p class="text-xs font-bold text-white">✓</p>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Info -->
                                        <div class="space-y-1.5 p-3">
                                            <p class="text-xl font-bold leading-tight text-slate-900" x-text="menu.name"></p>
                                            <template x-if="menu.category && menu.category.id">
                                                <div>
                                                    <span class="inline-flex max-w-full items-center truncate rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-700" x-text="menu.category.name"></span>
                                                </div>
                                            </template>
                                            <p class="text-xs leading-snug text-slate-600" x-text="menu.description || 'Menu pilihan hari ini'"></p>
                                            <p class="pt-0.5 text-sm font-bold text-sky-700" x-text="formatCurrency(menu.price)"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </aside>

                    <!-- VOICE INPUT - COMPACT (Right, 4-5 cols) -->
                    <main class="lg:col-span-5 flex flex-col rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-5 min-h-[600px]">
                        <h3 class="text-lg font-bold text-slate-900">🎤 Voice Order</h3>
                        <p class="mt-1 text-xs text-slate-500">Bicara pesanan atau ketik manual</p>

                        <!-- Customer Info - Compact -->
                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="customer_name_kiosk" class="text-xs font-bold uppercase text-slate-600">Nama</label>
                                <input id="customer_name_kiosk" x-model="customerName" type="text" placeholder="Nama Anda" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                            </div>
                            <div>
                                <label for="customer_gender_kiosk" class="text-xs font-bold uppercase text-slate-600">Gender (optional)</label>
                                <select id="customer_gender_kiosk" x-model="customerGender" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">-</option>
                                    <option value="male">Pria</option>
                                    <option value="female">Wanita</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <!-- Voice Controls - Compact -->
                        <div class="mt-4 flex items-end gap-2">
                            <button type="button"
                                @click="toggleListening('customer')"
                                :disabled="!speechSupported"
                                class="relative inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-white transition"
                                :class="isListening ? 'bg-rose-500 hover:bg-rose-600' : 'bg-sky-600 hover:bg-sky-700'">
                                <span x-show="isListening" class="absolute inset-0 rounded-lg border-2 border-rose-300 pulse-ring"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 16.91c-1.48 1.46-3.51 2.36-5.77 2.36-2.26 0-4.29-.9-5.77-2.36l-1.1 1.1c1.72 1.64 4.05 2.67 6.87 2.67s5.15-1.03 6.87-2.67l-1.1-1.1zM19 28h-2v-2h2v2z"/>
                                </svg>
                            </button>
                            <button type="button"
                                @click="toggleListening('customer')"
                                :disabled="!speechSupported"
                                class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="isListening ? 'bg-rose-500 hover:bg-rose-600' : 'bg-sky-600 hover:bg-sky-700'"
                                x-text="isListening ? 'Stop' : 'Mulai'">
                            </button>
                        </div>

                        <p class="mt-2 text-xs" :class="isListening ? 'text-rose-600 font-semibold' : 'text-slate-500'">
                            <span x-text="isListening ? '🔴 Mendengarkan...' : (speechSupported ? '✓ Siap' : '✗ Browser tidak support')"></span>
                        </p>

                        <!-- Transcript / Manual Input -->
                        <div class="mt-3 flex flex-1 flex-col min-h-0">
                            <label for="raw_text_kiosk" class="text-xs font-bold uppercase text-slate-600">Pesanan</label>
                            <textarea id="raw_text_kiosk" x-model="rawText" @input="scheduleDraftPreview()"
                                class="mt-1 flex-1 w-full resize-none rounded-lg border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500 min-h-[80px]"
                                placeholder="Contoh: nasgor, teh anget"></textarea>
                        </div>

                        <!-- Detected Items -->
                        <div class="mt-3">
                            <p class="text-xs font-bold uppercase text-slate-600">Item Terdeteksi</p>
                            <div class="mt-1 flex min-h-[28px] flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-2">
                                <template x-if="detectedItems.length === 0">
                                    <span class="text-xs text-slate-400">Belum ada</span>
                                </template>
                                <template x-for="item in detectedItems" :key="item.name + item.qty">
                                    <span class="chip-in rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-800" x-text="item.qty > 1 ? `${item.name} ×${item.qty}` : item.name"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-3 flex gap-2">
                            <button type="button" @click="submitOrder()" :disabled="submitting || !rawText.trim() || !customerName.trim()" class="flex-1 rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white disabled:opacity-50 hover:bg-slate-800 transition">
                                <span x-text="submitting ? '...' : 'Proses'"></span>
                            </button>
                            <button type="button" @click="resetState()" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Reset</button>
                        </div>

                        <!-- Status -->
                        <div class="mt-3 rounded-lg border border-slate-200 bg-white p-2">
                            <p class="text-xs" :class="statusTone" x-text="statusMessage || 'Siap menerima pesanan'"></p>
                        </div>
                    </main>

                </div>
            </section>

        </div>

        <script>
            function orderKiosk() {
                return {
                    menus: [],
                    categories: [],
                    selectedCategoryId: null,
                    loadingMenus: false,
                    submitting: false,
                    rawText: '',
                    detectedItems: [],
                    finalItems: [],
                    queueNumber: null,
                    orderCode: '',
                    statusMessage: '',
                    statusTone: 'text-slate-600',
                    isListening: false,
                    recognition: null,
                    speechSupported: false,
                    listeningTarget: 'customer',
                    cashierOrders: [],
                    loadingCashierOrders: false,
                    selectedOrderId: null,
                    cashierAppendText: '',
                    cashierBusy: false,
                    cashierMessage: '',
                    cashierTone: 'text-slate-600',
                    customerName: '',
                    customerGender: '',
                    lastOrderCustomerName: '',
                    lastOrderCustomerGender: '',
                    draftPreviewTimer: null,
                    draftPreviewRequestId: 0,

                    showConfirmModal: false,
                    showSuccessModal: false,
                    confirmingItems: [],
                    escapeKeyListener: null,
                    silenceTimeoutId: null,
                    silenceDelayMs: 3000,
                    shouldKeepListening: false,
                    customerSpeechCommitted: '',
                    cashierSpeechCommitted: '',
                    speechInterimCurrent: '',

                    init() {
                        this.fetchCategories();
                        this.fetchMenus();
                        this.initSpeech();
                        this.setupKeyboardListener();
                    },

                    setupKeyboardListener() {
                        document.addEventListener('keydown', (e) => {
                            if (e.key === 'Escape' && this.showConfirmModal) {
                                this.closeConfirmModal();
                            }
                        });
                    },

                    async fetchMenus() {
                        this.loadingMenus = true;
                        try {
                            const response = await axios.get('/api/menus');
                            this.menus = response.data.data || [];
                        } catch (error) {
                            this.statusMessage = 'Gagal memuat menu.';
                            this.statusTone = 'text-rose-600';
                        } finally {
                            this.loadingMenus = false;
                        }
                    },

                    async fetchCategories() {
                        try {
                            const response = await axios.get('/api/categories');
                            this.categories = response.data.data || [];
                        } catch (error) {
                            console.error('Gagal memuat kategori:', error);
                        }
                    },

                    filteredMenus() {
                        if (this.selectedCategoryId === null) {
                            return this.menus;
                        }
                        return this.menus.filter(menu => menu.category_id === this.selectedCategoryId);
                    },

                    selectCategory(categoryId) {
                        this.selectedCategoryId = categoryId === this.selectedCategoryId ? null : categoryId;
                    },

                    initSpeech() {
                        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                        if (!SpeechRecognition) {
                            this.speechSupported = false;
                            return;
                        }

                        this.speechSupported = true;
                        this.recognition = new SpeechRecognition();
                        this.recognition.lang = 'id-ID';
                        this.recognition.continuous = true;
                        this.recognition.interimResults = true;

                        this.recognition.onstart = () => {
                            this.resetSilenceTimer();
                        };

                        this.recognition.onresult = (event) => {
                            this.resetSilenceTimer();

                            let interimTranscript = '';
                            let finalizeDraft = false;

                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const current = (event.results[i][0]?.transcript || '').trim();
                                if (!current) {
                                    continue;
                                }

                                if (event.results[i].isFinal) {
                                    if (this.listeningTarget === 'customer' && this.isFinalizeCommand(current)) {
                                        finalizeDraft = true;
                                        continue;
                                    }

                                    this.appendFinalSpeech(current);
                                    continue;
                                }

                                interimTranscript = `${interimTranscript} ${current}`.trim();
                            }

                            this.speechInterimCurrent = interimTranscript;
                            this.syncListeningTargetText();

                            if (finalizeDraft && this.listeningTarget === 'customer' && this.customerSpeechCommitted.trim()) {
                                this.shouldKeepListening = false;
                                this.clearSilenceTimer();
                                if (this.recognition) {
                                    try {
                                        this.recognition.stop();
                                    } catch (error) {
                                        this.isListening = false;
                                    }
                                }

                                this.rawText = this.customerSpeechCommitted.trim();
                                this.scheduleDraftPreview(true);
                            }
                        };

                        this.recognition.onend = () => {
                            this.clearSilenceTimer();

                            if (this.shouldKeepListening) {
                                try {
                                    this.recognition.start();
                                } catch (error) {
                                    this.isListening = false;
                                    this.shouldKeepListening = false;
                                }

                                return;
                            }

                            this.isListening = false;
                        };

                        this.recognition.onerror = () => {
                            this.clearSilenceTimer();
                            this.isListening = false;
                            this.shouldKeepListening = false;
                        };
                    },

                    clearSilenceTimer() {
                        if (!this.silenceTimeoutId) {
                            return;
                        }

                        clearTimeout(this.silenceTimeoutId);
                        this.silenceTimeoutId = null;
                    },

                    resetSilenceTimer() {
                        this.clearSilenceTimer();

                        this.silenceTimeoutId = setTimeout(() => {
                            this.shouldKeepListening = false;
                            if (this.recognition) {
                                try {
                                    this.recognition.stop();
                                } catch (error) {
                                    this.isListening = false;
                                }
                            }
                        }, this.silenceDelayMs);
                    },

                    appendFinalSpeech(text) {
                        if (this.listeningTarget === 'customer' && this.isFinalizeCommand(text)) {
                            return;
                        }

                        if (this.listeningTarget === 'cashier') {
                            this.cashierSpeechCommitted = `${this.cashierSpeechCommitted} ${text}`.trim();
                            return;
                        }

                        this.customerSpeechCommitted = `${this.customerSpeechCommitted} ${text}`.trim();
                    },

                    isFinalizeCommand(text) {
                        const normalized = String(text)
                            .toLowerCase()
                            .replace(/[^a-z\s]/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();

                        return ['oke', 'ok', 'okay'].includes(normalized);
                    },

                    syncListeningTargetText() {
                        if (this.listeningTarget === 'cashier') {
                            this.cashierAppendText = `${this.cashierSpeechCommitted} ${this.speechInterimCurrent}`.trim();
                            return;
                        }

                        this.rawText = `${this.customerSpeechCommitted} ${this.speechInterimCurrent}`.trim();
                        this.scheduleDraftPreview();
                    },

                    toggleListening(target = 'customer') {
                        if (!this.speechSupported || !this.recognition) {
                            return;
                        }

                        if (this.isListening) {
                            this.shouldKeepListening = false;
                            this.clearSilenceTimer();
                            this.recognition.stop();
                            this.isListening = false;
                            return;
                        }

                        this.listeningTarget = target;
                        this.speechInterimCurrent = '';

                        // Reset transcript only when user starts a brand-new mic session.
                        if (target === 'cashier') {
                            this.cashierSpeechCommitted = '';
                            this.cashierAppendText = '';
                        } else {
                            this.customerSpeechCommitted = String(this.rawText || '').trim();
                        }

                        this.shouldKeepListening = true;
                        this.isListening = true;
                        this.resetSilenceTimer();

                        try {
                            this.recognition.start();
                        } catch (error) {
                            this.isListening = false;
                            this.shouldKeepListening = false;
                            this.clearSilenceTimer();
                        }
                    },

                    async fetchCashierOrders() {
                        this.loadingCashierOrders = true;
                        try {
                            const response = await axios.get('/api/cashier/orders');
                            this.cashierOrders = response.data.data || [];
                            if (this.selectedOrderId && !this.cashierOrders.find((order) => order.id === this.selectedOrderId)) {
                                this.selectedOrderId = null;
                            }
                        } catch (error) {
                            this.cashierMessage = 'Gagal memuat daftar order kasir.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.loadingCashierOrders = false;
                        }
                    },

                    selectCashierOrder(order) {
                        this.selectedOrderId = order.id;
                        this.cashierMessage = '';
                    },

                    selectedCashierOrder() {
                        return this.cashierOrders.find((order) => order.id === this.selectedOrderId) || null;
                    },

                    async confirmSelectedOrder() {
                        const order = this.selectedCashierOrder();
                        if (!order) {
                            return;
                        }

                        this.cashierBusy = true;
                        try {
                            const response = await axios.patch(`/api/cashier/orders/${order.id}/confirm`);
                            this.cashierMessage = response.data.message || 'Order dikonfirmasi.';
                            this.cashierTone = 'text-emerald-600';
                            await this.fetchCashierOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.cashierMessage = data.message || 'Gagal konfirmasi order.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.cashierBusy = false;
                        }
                    },

                    async cancelSelectedOrder() {
                        const order = this.selectedCashierOrder();
                        if (!order) {
                            return;
                        }

                        this.cashierBusy = true;
                        try {
                            const response = await axios.patch(`/api/cashier/orders/${order.id}/cancel`);
                            this.cashierMessage = response.data.message || 'Order dibatalkan.';
                            this.cashierTone = 'text-emerald-600';
                            await this.fetchCashierOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.cashierMessage = data.message || 'Gagal batalkan order.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.cashierBusy = false;
                        }
                    },

                    async appendVoiceToSelected() {
                        const order = this.selectedCashierOrder();
                        if (!order || !this.cashierAppendText.trim()) {
                            return;
                        }

                        const handledCommand = await this.applyVoiceCommandToCashierOrder(order, this.cashierAppendText);

                        if (handledCommand) {
                            this.cashierAppendText = '';
                            return;
                        }

                        this.cashierBusy = true;
                        try {
                            const response = await axios.post(`/api/cashier/orders/${order.id}/append-voice`, {
                                raw_text: this.cashierAppendText,
                            });

                            this.cashierMessage = response.data.message || 'Item tambahan berhasil ditambahkan.';
                            this.cashierTone = 'text-emerald-600';
                            this.cashierAppendText = '';
                            await this.fetchCashierOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.cashierMessage = data.message || 'Gagal menambah item ke order.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.cashierBusy = false;
                        }
                    },

                    async updateCashierItemQty(item, delta) {
                        const order = this.selectedCashierOrder();
                        if (!order || !item) {
                            return;
                        }

                        const nextQty = Number(item.qty || 1) + delta;
                        if (nextQty <= 0) {
                            await this.removeCashierItem(item);
                            return;
                        }

                        this.cashierBusy = true;
                        try {
                            const response = await axios.patch(`/api/cashier/orders/${order.id}/items/${item.id}`, {
                                qty: nextQty,
                            });
                            this.cashierMessage = response.data.message || 'Qty item diperbarui.';
                            this.cashierTone = 'text-emerald-600';
                            await this.fetchCashierOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.cashierMessage = data.message || 'Gagal mengubah qty item.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.cashierBusy = false;
                        }
                    },

                    async removeCashierItem(item) {
                        const order = this.selectedCashierOrder();
                        if (!order || !item) {
                            return;
                        }

                        this.cashierBusy = true;
                        try {
                            const response = await axios.delete(`/api/cashier/orders/${order.id}/items/${item.id}`);
                            this.cashierMessage = response.data.message || 'Item berhasil dihapus.';
                            this.cashierTone = 'text-emerald-600';
                            await this.fetchCashierOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.cashierMessage = data.message || 'Gagal menghapus item.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.cashierBusy = false;
                        }
                    },

                    async requestValidatedItems(rawText) {
                        const normalized = String(rawText || '').trim();

                        if (!normalized) {
                            return {
                                status: 'invalid',
                                message: 'Pesanan kosong.',
                                items: [],
                            };
                        }

                        try {
                            const response = await axios.post('/api/orders/voice/preview', {
                                raw_text: normalized,
                            });

                            return {
                                status: response.data.status || 'valid',
                                message: response.data.message || '',
                                items: response.data.items || [],
                                invalid: response.data.invalid || [],
                            };
                        } catch (error) {
                            const data = error?.response?.data || {};

                            return {
                                status: data.status || 'invalid',
                                message: data.message || 'Validasi pesanan gagal.',
                                items: data.items || [],
                                invalid: data.invalid || [],
                            };
                        }
                    },

                    scheduleDraftPreview(force = false) {
                        if (this.listeningTarget === 'cashier') {
                            return;
                        }

                        if (this.draftPreviewTimer) {
                            clearTimeout(this.draftPreviewTimer);
                            this.draftPreviewTimer = null;
                        }

                        if (force) {
                            this.refreshDraftPreview();
                            return;
                        }

                        this.draftPreviewTimer = setTimeout(() => {
                            this.refreshDraftPreview();
                        }, 250);
                    },

                    async refreshDraftPreview() {
                        const currentRawText = String(this.rawText || '').trim();

                        if (!currentRawText) {
                            this.detectedItems = [];
                            if (!this.showConfirmModal) {
                                this.confirmingItems = [];
                            }
                            return;
                        }

                        const requestId = ++this.draftPreviewRequestId;
                        const preview = await this.requestValidatedItems(currentRawText);

                        if (requestId !== this.draftPreviewRequestId) {
                            return;
                        }

                        if (preview.status === 'invalid') {
                            this.detectedItems = [];
                            if (!this.showConfirmModal) {
                                this.confirmingItems = [];
                            }
                            this.statusMessage = preview.message || 'Pesanan tidak dikenali, silakan ulangi.';
                            this.statusTone = 'text-rose-600';
                            return;
                        }

                        this.detectedItems = (preview.items || []).map((item) => ({
                            name: String(item.name || '').toLowerCase(),
                            qty: Math.max(1, Number(item.qty || 1)),
                            note: String(item.note || '').trim() || null,
                        }));

                        if (!this.showConfirmModal) {
                            this.confirmingItems = this.detectedItems.map((item) => ({
                                ...item,
                                note: item.note || null,
                            }));
                        }

                        if (this.detectedItems.length === 0) {
                            this.statusMessage = 'Pesanan belum berisi item yang valid.';
                            this.statusTone = 'text-amber-600';
                            return;
                        }

                        if (preview.status === 'partial') {
                            this.statusMessage = preview.message || 'Sebagian menu tidak tersedia, hanya item valid yang ditampilkan.';
                            this.statusTone = 'text-amber-600';
                        } else {
                            this.statusMessage = preview.message || 'Pesanan siap dikonfirmasi.';
                            this.statusTone = 'text-emerald-600';
                        }
                    },

                    normalizeNumberWords(segment) {
                        const map = {
                            sepuluh: '10',
                            sembilan: '9',
                            delapan: '8',
                            tujuh: '7',
                            enam: '6',
                            lima: '5',
                            empat: '4',
                            tiga: '3',
                            dua: '2',
                            satu: '1',
                            se: '1',
                        };

                        return String(segment)
                            .split(/\s+/)
                            .map((token) => map[token] ?? token)
                            .join(' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                    },

                    normalizePossessiveSuffix(segment) {
                        return String(segment)
                            .replace(/\b([a-z0-9]{3,})nya\b/g, '$1')
                            .replace(/\s+/g, ' ')
                            .trim();
                    },

                    extractQtyAndNameFromSegment(segment) {
                        let qty = 1;
                        let name = segment;

                        const leadQty = segment.match(/^(\d+)\s*(?:x|kali)?\s+(.+)$/);
                        const tailQty = segment.match(/^(.+?)\s+(?:x\s*)?(\d+)\s*(?:x|kali)?$/);

                        if (leadQty) {
                            qty = Math.max(1, parseInt(leadQty[1], 10));
                            name = leadQty[2].trim();
                        } else if (tailQty) {
                            qty = Math.max(1, parseInt(tailQty[2], 10));
                            name = tailQty[1].trim();
                        }

                        name = name
                            .replace(/\b(?:x|kali)\b/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();

                        return { name, qty };
                    },

                    isMenuHighlighted(menuName) {
                        return this.detectedItems.some((item) => item.name === String(menuName).toLowerCase());
                    },

                    addMenuToDraft(menu) {
                        const normalizedName = String(menu?.name || '').trim().toLowerCase();

                        if (!normalizedName) {
                            return;
                        }

                        const existing = this.detectedItems.find((item) => item.name === normalizedName);

                        if (existing) {
                            existing.qty = Math.max(1, Number(existing.qty || 1) + 1);
                        } else {
                            this.detectedItems = [
                                ...this.detectedItems,
                                {
                                    name: normalizedName,
                                    qty: 1,
                                    note: null,
                                },
                            ];
                        }

                        this.detectedItems = this.detectedItems.map((item) => ({
                            ...item,
                            qty: Math.max(1, Number(item.qty || 1)),
                            note: String(item.note || '').trim() || null,
                        }));

                        if (!this.showConfirmModal) {
                            this.confirmingItems = this.detectedItems.map((item) => ({
                                ...item,
                                note: item.note || null,
                            }));
                        }

                        this.rawText = this.buildRawTextFromConfirmingItems();
                        this.customerSpeechCommitted = this.rawText;
                        this.statusMessage = `${menu.name} ditambahkan ke pesanan.`;
                        this.statusTone = 'text-emerald-600';
                    },

                    async submitOrder() {
                        if (!this.rawText.trim()) {
                            return;
                        }

                        if (!this.customerName.trim()) {
                            this.statusMessage = 'Nama pelanggan wajib diisi sebelum proses order.';
                            this.statusTone = 'text-rose-600';
                            return;
                        }

                        if (this.showConfirmModal && this.confirmingItems.length > 0) {
                            const handledCommand = await this.applyVoiceCommandToConfirmingItems(this.rawText);

                            if (handledCommand) {
                                return;
                            }
                        }

                        if (this.draftPreviewTimer) {
                            clearTimeout(this.draftPreviewTimer);
                            this.draftPreviewTimer = null;
                        }

                        this.statusMessage = 'Menganalisa pesanan...';
                        this.statusTone = 'text-slate-600';
                        await this.refreshDraftPreview();

                        if (this.detectedItems.length === 0) {
                            return;
                        }

                        this.confirmingItems = this.detectedItems.map((item) => ({
                            ...item,
                            note: item.note || null,
                        }));
                        await this.applyVoiceNoteCommand(this.rawText);
                        this.showConfirmModal = true;
                        this.$nextTick(() => {
                            document.body.style.overflow = 'hidden';
                        });
                    },

                    closeConfirmModal() {
                        this.showConfirmModal = false;
                        this.confirmingItems = [];
                        document.body.style.overflow = 'auto';
                    },

                    closeSuccessModal() {
                        this.showSuccessModal = false;
                        document.body.style.overflow = 'auto';
                        this.clearDraftState();
                    },

                    async printSuccessModal(type = 'pdf') {
                        const receiptContent = this.generateReceiptHTML();

                        if (type === 'pdf') {
                            // Browser print untuk PDF
                            const printWindow = window.open('', '_blank');
                            printWindow.document.write(receiptContent);
                            printWindow.document.close();
                            printWindow.print();
                        } else if (type === 'thermal') {
                            // Kirim ke backend untuk printer thermal
                            try {
                                await axios.post('/api/orders/print-thermal', {
                                    order_code: this.orderCode,
                                    queue_number: this.queueNumber,
                                    customer_name: this.lastOrderCustomerName,
                                    gender: this.lastOrderCustomerGender,
                                    items: this.finalItems,
                                });
                                this.statusMessage = 'Dikirim ke printer thermal.';
                                this.statusTone = 'text-emerald-600';
                            } catch (error) {
                                this.statusMessage = 'Gagal cetak ke thermal printer.';
                                this.statusTone = 'text-rose-600';
                            }
                        }
                    },

                    generateReceiptHTML() {
                        const itemsHTML = this.finalItems.map((item) => `
                            <tr>
                                <td style="padding: 8px; text-align: left;">
                                    ${item.name}
                                    ${item.note ? `<div style="font-size: 11px; color: #666; margin-top: 3px;">(${item.note})</div>` : ''}
                                </td>
                                <td style="padding: 8px; text-align: right;">x${item.qty}</td>
                            </tr>
                        `).join('');

                        const now = new Date();
                        const dateTime = now.toLocaleString('id-ID');

                        return `
                            <!DOCTYPE html>
                            <html lang="id">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Struk Pesanan #${this.orderCode}</title>
                                <style>
                                    body {
                                        font-family: Arial, sans-serif;
                                        margin: 0;
                                        padding: 20px;
                                        background-color: #f5f5f5;
                                    }
                                    .receipt {
                                        max-width: 400px;
                                        background: white;
                                        margin: 0 auto;
                                        padding: 20px;
                                        border: 1px solid #ddd;
                                        border-radius: 8px;
                                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                    }
                                    .header {
                                        text-align: center;
                                        border-bottom: 2px solid #333;
                                        padding-bottom: 15px;
                                        margin-bottom: 15px;
                                    }
                                    .header h1 {
                                        margin: 0;
                                        font-size: 24px;
                                        color: #333;
                                    }
                                    .header p {
                                        margin: 5px 0 0 0;
                                        color: #666;
                                        font-size: 12px;
                                    }
                                    .info {
                                        margin-bottom: 15px;
                                        font-size: 14px;
                                    }
                                    .info-row {
                                        display: flex;
                                        justify-content: space-between;
                                        margin: 5px 0;
                                    }
                                    .label {
                                        font-weight: bold;
                                        color: #333;
                                    }
                                    .value {
                                        color: #666;
                                    }
                                    .queue-number {
                                        text-align: center;
                                        background: linear-gradient(135deg, #0ea5e9, #0284c7);
                                        color: white;
                                        padding: 20px;
                                        border-radius: 8px;
                                        margin: 15px 0;
                                    }
                                    .queue-number .label {
                                        color: rgba(255,255,255,0.8);
                                        font-weight: normal;
                                        font-size: 12px;
                                        display: block;
                                        margin-bottom: 5px;
                                    }
                                    .queue-number .number {
                                        font-size: 48px;
                                        font-weight: bold;
                                        color: white;
                                    }
                                    .items {
                                        border-top: 1px solid #ddd;
                                        border-bottom: 1px solid #ddd;
                                        padding: 15px 0;
                                        margin: 15px 0;
                                    }
                                    .items table {
                                        width: 100%;
                                        font-size: 14px;
                                    }
                                    .items td {
                                        padding: 8px;
                                    }
                                    .footer {
                                        text-align: center;
                                        color: #666;
                                        font-size: 12px;
                                        margin-top: 15px;
                                    }
                                    @media print {
                                        body {
                                            background: white;
                                            padding: 0;
                                        }
                                        .receipt {
                                            box-shadow: none;
                                            border: none;
                                        }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="receipt">
                                    <div class="header">
                                        <h1>STRUK PESANAN</h1>
                                        <p>Order #${this.orderCode}</p>
                                    </div>

                                    <div class="info">
                                        <div class="info-row">
                                            <span class="label">Nama:</span>
                                            <span class="value">${this.lastOrderCustomerName}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="label">Gender:</span>
                                            <span class="value">${this.genderLabel(this.lastOrderCustomerGender)}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="label">Tanggal:</span>
                                            <span class="value">${new Date().toLocaleString('id-ID')}</span>
                                        </div>
                                    </div>

                                    <div class="queue-number">
                                        <span class="label">NOMOR ANTRIAN</span>
                                        <span class="number">${this.queueNumber ?? '-'}</span>
                                    </div>

                                    <div class="items">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Menu</th>
                                                    <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${itemsHTML}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="footer">
                                        <p>Terima kasih telah memesan!</p>
                                        <p>Tunggu antrian sesuai nomor di atas</p>
                                    </div>
                                </div>
                            </body>
                            </html>
                        `;
                    },

                    changeConfirmingItemQty(name, delta) {
                        this.updateConfirmingItemQty(name, delta);
                    },

                    removeConfirmingItem(name) {
                        this.confirmingItems = this.confirmingItems.filter((item) => item.name !== name);
                    },

                    updateConfirmingItemNote(name, note) {
                        const normalizedNote = String(note || '').trim();
                        this.confirmingItems = this.confirmingItems.map((item) => {
                            if (item.name !== name) {
                                return item;
                            }

                            return {
                                ...item,
                                note: normalizedNote || null,
                            };
                        });

                        this.detectedItems = this.detectedItems.map((item) => {
                            if (item.name !== name) {
                                return item;
                            }

                            return {
                                ...item,
                                note: normalizedNote || null,
                            };
                        });
                    },

                    buildRawTextFromConfirmingItems() {
                        return this.confirmingItems
                            .map((item) => {
                                const qty = Math.max(1, Number(item.qty || 1));
                                const base = qty > 1 ? `${qty} ${item.name}` : item.name;
                                const note = String(item.note || '').trim();

                                return note ? `${base} (${note})` : base;
                            })
                            .join(', ');
                    },

                    buildStructuredItemsPayload() {
                        return this.confirmingItems.map((item) => ({
                            name: String(item.name || '').trim(),
                            qty: Math.max(1, Number(item.qty || 1)),
                            note: String(item.note || '').trim() || null,
                        }));
                    },

                    normalizeCommandText(text) {
                        return String(text)
                            .toLowerCase()
                            .replace(/\b(?:saya mau|aku mau|saya pesan|aku pesan|mau|pesan|tolong|dong|ya|kak|nih|deh|please)\b/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                    },

                    parseVoiceCommand(text) {
                        const normalized = this.normalizeCommandText(text);
                        const match = normalized.match(/^(hapus|hapuskan|buang|kurangi|kurang|tambah|tambahkan|nambah|nambahin|add)\s+(.+)$/);

                        if (!match) {
                            return null;
                        }

                        const actionWord = match[1];
                        const targetSegment = this.normalizePossessiveSuffix(this.normalizeNumberWords(match[2]));
                        const parsedTarget = this.extractQtyAndNameFromSegment(targetSegment);

                        if (!parsedTarget.name) {
                            return null;
                        }

                        let action = 'add';

                        if (['hapus', 'hapuskan', 'buang'].includes(actionWord)) {
                            action = 'remove';
                        } else if (['kurangi', 'kurang'].includes(actionWord)) {
                            action = 'decrease';
                        }

                        return {
                            action,
                            targetName: parsedTarget.name,
                            qty: Math.max(1, Number(parsedTarget.qty || 1)),
                        };
                    },

                    async applyVoiceNoteCommand(rawText) {
                        const normalized = this.normalizeCommandText(rawText);
                        const directPattern = normalized.match(/^(.*?)\s*(?:keterangan|catatan|note)\s+(.+)$/);

                        if (directPattern) {
                            const rawTargetPart = this.normalizePossessiveSuffix(String(directPattern[1] || '').trim());
                            const notePart = String(directPattern[2] || '').replace(/\b(?:oke|ok|okay)\b$/g, '').trim();

                            if (!notePart) {
                                this.statusMessage = 'Format keterangan belum lengkap.';
                                this.statusTone = 'text-rose-600';
                                return true;
                            }

                            let targetPart = rawTargetPart;

                            if (targetPart.includes(' ')) {
                                const tokens = targetPart.split(/\s+/).filter(Boolean);
                                for (let start = 0; start < tokens.length; start++) {
                                    const candidate = this.normalizePossessiveSuffix(tokens.slice(start).join(' '));
                                    if (!candidate) {
                                        continue;
                                    }

                                    const resolvedCandidate = await this.resolveMenuCandidate(candidate);
                                    if (resolvedCandidate?.matched_name) {
                                        targetPart = candidate;
                                        break;
                                    }
                                }
                            }

                            if (!targetPart) {
                                this.statusMessage = 'Tidak bisa mengenali target menu untuk keterangan.';
                                this.statusTone = 'text-rose-600';
                                return true;
                            }

                            const resolvedDirect = await this.resolveMenuCandidate(targetPart);
                            if (resolvedDirect?.matched_name) {
                                const resolvedName = String(resolvedDirect.matched_name).toLowerCase();
                                const exists = this.confirmingItems.find((item) => item.name === resolvedName);

                                if (!exists) {
                                    this.statusMessage = `Item ${resolvedName} tidak ada di daftar konfirmasi.`;
                                    this.statusTone = 'text-rose-600';
                                    return true;
                                }

                                let cleanedNote = notePart;
                                if (cleanedNote.startsWith(`${resolvedName} `)) {
                                    cleanedNote = cleanedNote.slice(resolvedName.length).trim();
                                }

                                this.updateConfirmingItemNote(resolvedName, cleanedNote || notePart);
                                this.rawText = this.buildRawTextFromConfirmingItems();
                                this.statusMessage = `Keterangan untuk ${resolvedName} diperbarui.`;
                                this.statusTone = 'text-emerald-600';
                                return true;
                            }
                        }

                        const notePrefixMatch = normalized.match(/(?:^|\s)(?:keterangan|catatan|note)\s+(.+)$/);

                        if (!notePrefixMatch) {
                            return false;
                        }

                        const body = String(notePrefixMatch[1] || '').replace(/\b(?:oke|ok|okay)\b$/g, '').trim();
                        if (!body) {
                            this.statusMessage = 'Format keterangan belum lengkap.';
                            this.statusTone = 'text-rose-600';
                            return true;
                        }

                        const tokens = body.split(/\s+/).filter(Boolean);
                        if (tokens.length < 2) {
                            this.statusMessage = 'Gunakan format: keterangan <menu> <catatan>.';
                            this.statusTone = 'text-rose-600';
                            return true;
                        }

                        for (let cut = tokens.length - 1; cut >= 1; cut--) {
                            const targetPart = this.normalizePossessiveSuffix(tokens.slice(0, cut).join(' '));
                            const notePart = tokens.slice(cut).join(' ').trim();

                            if (!targetPart || !notePart) {
                                continue;
                            }

                            const resolved = await this.resolveMenuCandidate(targetPart);
                            if (!resolved || !resolved.matched_name) {
                                continue;
                            }

                            const resolvedName = String(resolved.matched_name).toLowerCase();
                            const exists = this.confirmingItems.find((item) => item.name === resolvedName);

                            if (!exists) {
                                this.statusMessage = `Item ${resolvedName} tidak ada di daftar konfirmasi.`;
                                this.statusTone = 'text-rose-600';
                                return true;
                            }

                            this.updateConfirmingItemNote(resolvedName, notePart);
                            this.rawText = this.buildRawTextFromConfirmingItems();
                            this.statusMessage = `Keterangan untuk ${resolvedName} diperbarui.`;
                            this.statusTone = 'text-emerald-600';
                            return true;
                        }

                        this.statusMessage = 'Tidak bisa mengenali target menu untuk keterangan.';
                        this.statusTone = 'text-rose-600';
                        return true;
                    },

                    async resolveMenuCandidate(candidate) {
                        const normalized = this.normalizePossessiveSuffix(this.normalizeNumberWords(this.normalizeCommandText(candidate)));

                        if (!normalized) {
                            return null;
                        }

                        try {
                            const response = await axios.post('/api/menus/resolve', {
                                candidate: normalized,
                            });

                            return response.data.data || null;
                        } catch (error) {
                            return null;
                        }
                    },

                    updateConfirmingItemQty(name, delta) {
                        let found = false;
                        const nextItems = [];

                        this.confirmingItems.forEach((item) => {
                            if (item.name !== name) {
                                nextItems.push(item);
                                return;
                            }

                            found = true;
                            const nextQty = Number(item.qty || 1) + delta;

                            if (nextQty > 0) {
                                nextItems.push({
                                    ...item,
                                    qty: nextQty,
                                });
                            }
                        });

                        this.confirmingItems = nextItems;
                        return found;
                    },

                    async applyVoiceCommandToConfirmingItems(rawText) {
                        const command = this.parseVoiceCommand(rawText);

                        if (!command) {
                            return this.applyVoiceNoteCommand(rawText);
                        }

                        const resolved = await this.resolveMenuCandidate(command.targetName);

                        if (!resolved || !resolved.matched_name) {
                            this.statusMessage = 'Menu tujuan tidak ditemukan untuk perintah suara.';
                            this.statusTone = 'text-rose-600';
                            return true;
                        }

                        const name = String(resolved.matched_name).toLowerCase();
                        const qty = Math.max(1, Number(command.qty || 1));

                        if (command.action === 'remove') {
                            this.removeConfirmingItem(name);
                        } else if (command.action === 'decrease') {
                            const found = this.confirmingItems.find((item) => item.name === name);

                            if (!found) {
                                this.statusMessage = `Item ${name} tidak ada di daftar konfirmasi.`;
                                this.statusTone = 'text-rose-600';
                                return true;
                            }

                            if (Number(found.qty || 1) <= qty) {
                                this.removeConfirmingItem(name);
                            } else {
                                this.updateConfirmingItemQty(name, -qty);
                            }
                        } else {
                            const found = this.confirmingItems.find((item) => item.name === name);

                            if (found) {
                                this.updateConfirmingItemQty(name, qty);
                            } else {
                                this.confirmingItems = [
                                    ...this.confirmingItems,
                                    {
                                        name,
                                        qty,
                                        note: null,
                                    },
                                ];
                            }
                        }

                        this.rawText = this.buildRawTextFromConfirmingItems();
                        this.detectedItems = [...this.confirmingItems];
                        this.statusMessage = 'Daftar pesanan diperbarui lewat perintah suara.';
                        this.statusTone = 'text-emerald-600';
                        return true;
                    },

                    async applyVoiceCommandToCashierOrder(order, rawText) {
                        const command = this.parseVoiceCommand(rawText);

                        if (!command) {
                            return false;
                        }

                        const resolved = await this.resolveMenuCandidate(command.targetName);

                        if (!resolved || !resolved.matched_name) {
                            this.cashierMessage = 'Menu tujuan tidak ditemukan untuk perintah suara.';
                            this.cashierTone = 'text-rose-600';
                            return true;
                        }

                        const name = String(resolved.matched_name).toLowerCase();
                        const qty = Math.max(1, Number(command.qty || 1));
                        const currentItem = order.items?.find((item) => String(item.item_name || '').toLowerCase() === name) || null;

                        if (command.action === 'remove') {
                            if (!currentItem) {
                                this.cashierMessage = `Item ${name} tidak ada di order ini.`;
                                this.cashierTone = 'text-rose-600';
                                return true;
                            }

                            this.cashierBusy = true;
                            try {
                                const response = await axios.delete(`/api/cashier/orders/${order.id}/items/${currentItem.id}`);
                                this.cashierMessage = response.data.message || 'Item berhasil dihapus dari order.';
                                this.cashierTone = 'text-emerald-600';
                                await this.fetchCashierOrders();
                            } catch (error) {
                                const data = error?.response?.data || {};
                                this.cashierMessage = data.message || 'Gagal menghapus item.';
                                this.cashierTone = 'text-rose-600';
                            } finally {
                                this.cashierBusy = false;
                            }

                            return true;
                        }

                        if (command.action === 'decrease') {
                            if (!currentItem) {
                                this.cashierMessage = `Item ${name} tidak ada di order ini.`;
                                this.cashierTone = 'text-rose-600';
                                return true;
                            }

                            const nextQty = Number(currentItem.qty || 1) - qty;

                            if (nextQty <= 0) {
                                this.cashierBusy = true;
                                try {
                                    const response = await axios.delete(`/api/cashier/orders/${order.id}/items/${currentItem.id}`);
                                    this.cashierMessage = response.data.message || 'Item berhasil dihapus dari order.';
                                    this.cashierTone = 'text-emerald-600';
                                    await this.fetchCashierOrders();
                                } catch (error) {
                                    const data = error?.response?.data || {};
                                    this.cashierMessage = data.message || 'Gagal menghapus item.';
                                    this.cashierTone = 'text-rose-600';
                                } finally {
                                    this.cashierBusy = false;
                                }

                                return true;
                            }

                            this.cashierBusy = true;
                            try {
                                const response = await axios.patch(`/api/cashier/orders/${order.id}/items/${currentItem.id}`, {
                                    qty: nextQty,
                                });
                                this.cashierMessage = response.data.message || 'Qty item diperbarui.';
                                this.cashierTone = 'text-emerald-600';
                                await this.fetchCashierOrders();
                            } catch (error) {
                                const data = error?.response?.data || {};
                                this.cashierMessage = data.message || 'Gagal mengubah qty item.';
                                this.cashierTone = 'text-rose-600';
                            } finally {
                                this.cashierBusy = false;
                            }

                            return true;
                        }

                        const commandText = `${qty > 1 ? `${qty} ` : ''}${name}`;
                        this.cashierBusy = true;
                        try {
                            const response = await axios.post(`/api/cashier/orders/${order.id}/append-voice`, {
                                raw_text: commandText,
                            });

                            this.cashierMessage = response.data.message || 'Item tambahan berhasil ditambahkan.';
                            this.cashierTone = 'text-emerald-600';
                            this.cashierAppendText = '';
                            await this.fetchCashierOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.cashierMessage = data.message || 'Gagal menambah item ke order.';
                            this.cashierTone = 'text-rose-600';
                        } finally {
                            this.cashierBusy = false;
                        }

                        return true;
                    },

                    async confirmOrderSubmit() {
                        if (this.confirmingItems.length === 0) {
                            this.statusMessage = 'Pesanan kosong. Tambahkan item dulu.';
                            this.statusTone = 'text-rose-600';
                            return;
                        }

                        this.submitting = true;
                        this.statusMessage = 'Mengirim pesanan...';
                        this.statusTone = 'text-slate-600';

                        try {
                            const confirmedRawText = this.buildRawTextFromConfirmingItems();
                            const response = await axios.post('/api/orders/voice', {
                                raw_text: confirmedRawText,
                                items: this.buildStructuredItemsPayload(),
                                customer_name: this.customerName,
                                gender: this.customerGender || null,
                            });

                            const data = response.data;

                            this.finalItems = data.items || [];
                            this.queueNumber = data.display_queue_number ?? data.queue_number ?? null;
                            this.orderCode = data.order_code || '';
                            this.lastOrderCustomerName = this.customerName;
                            this.lastOrderCustomerGender = this.customerGender;
                            this.statusMessage = data.message || 'Order berhasil dibuat.';
                            this.statusTone = 'text-emerald-600';
                            this.closeConfirmModal();
                            this.showSuccessModal = true;
                            this.$nextTick(() => {
                                document.body.style.overflow = 'hidden';
                            });
                        } catch (error) {
                            const data = error?.response?.data || {};

                            if (data.status === 'partial') {
                                this.finalItems = [];
                                this.statusMessage = data.message || 'Sebagian menu tidak tersedia.';
                                this.statusTone = 'text-amber-600';
                            } else {
                                this.finalItems = [];
                                this.statusMessage = data.message || 'Pesanan tidak dikenali, silakan ulangi.';
                                this.statusTone = 'text-rose-600';
                            }
                            this.closeConfirmModal();
                        } finally {
                            this.submitting = false;
                        }
                    },

                    resetState() {
                        this.clearDraftState();
                        this.finalItems = [];
                        this.queueNumber = null;
                        this.orderCode = '';
                        this.lastOrderCustomerName = '';
                        this.lastOrderCustomerGender = '';
                        this.statusMessage = '';
                        this.statusTone = 'text-slate-600';
                    },

                    clearDraftState() {
                        if (this.draftPreviewTimer) {
                            clearTimeout(this.draftPreviewTimer);
                            this.draftPreviewTimer = null;
                        }

                        this.rawText = '';
                        this.detectedItems = [];
                        this.confirmingItems = [];
                        this.customerName = '';
                        this.customerGender = '';
                    },

                    genderLabel(value) {
                        const normalized = String(value || '').toLowerCase();

                        if (normalized === 'male') {
                            return 'Laki-laki';
                        }

                        if (normalized === 'female') {
                            return 'Perempuan';
                        }

                        if (normalized === 'other') {
                            return 'Lainnya';
                        }

                        return '-';
                    },

formatCurrency(value) {
                        const amount = Number(value || 0);
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0,
                        }).format(amount);
                    }
                };
            }
        </script>
    </div>
</x-app-layout>
