<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Order Kiosk') }}
            </h2>
            <p class="text-sm text-gray-500">Voice-first ordering with deterministic validation</p>
        </div>
    </x-slot>

    <div class="py-10" x-data="orderKiosk()" x-init="init()">
        <!-- Modal Konfirmasi Order -->
        <div x-show="showConfirmModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeConfirmModal()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <h3 class="text-lg font-bold text-slate-900">Konfirmasi Pesanan</h3>
                <p class="mt-2 text-sm text-slate-600">Silakan review item pesanan Anda:</p>
                
                <div class="mt-4 space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <template x-for="item in confirmingItems" :key="item.name + item.qty">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-800" x-text="item.name"></span>
                            <div class="flex items-center gap-1">
                                <button type="button" class="h-7 w-7 rounded border border-slate-300 text-slate-700" @click="changeConfirmingItemQty(item.name, -1)">-</button>
                                <span class="rounded-full bg-cyan-100 px-2 py-1 text-xs font-bold text-cyan-700" x-text="`x${item.qty}`"></span>
                                <button type="button" class="h-7 w-7 rounded border border-slate-300 text-slate-700" @click="changeConfirmingItemQty(item.name, 1)">+</button>
                                <button type="button" class="rounded border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600" @click="removeConfirmingItem(item.name)">Hapus</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="button" @click="closeConfirmModal()" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="button" @click="confirmOrderSubmit()" :disabled="submitting || confirmingItems.length === 0" class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50 hover:bg-emerald-700">Konfirmasi Ya</button>
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
                .marquee {
                    white-space: nowrap;
                    overflow: hidden;
                }
                .marquee span {
                    display: inline-block;
                    padding-left: 100%;
                    animation: marquee 18s linear infinite;
                }
                @keyframes marquee {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-100%); }
                }
            </style>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm">
                <div class="marquee text-sm font-medium text-amber-900">
                    <span x-text="activeBanner"></span>
                </div>
            </section>

            <section class="kiosk-grid rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:p-7">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <aside class="lg:col-span-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <h3 class="text-base font-bold text-slate-900">Daftar Menu</h3>
                        <p class="mt-1 text-xs text-slate-500">Menu aktif dari API</p>

                        <div class="mt-4 max-h-[460px] space-y-2 overflow-auto pr-1">
                            <template x-if="loadingMenus">
                                <p class="text-sm text-slate-500">Memuat menu...</p>
                            </template>

                            <template x-if="!loadingMenus && menus.length === 0">
                                <p class="rounded-lg bg-white p-3 text-sm text-slate-600">Menu belum tersedia. Tambahkan data menu dulu.</p>
                            </template>

                            <template x-for="menu in menus" :key="menu.id">
                                <div class="rounded-xl border p-3 transition"
                                    :class="isMenuHighlighted(menu.name) ? 'border-cyan-400 bg-cyan-50 shadow-sm' : 'border-slate-200 bg-white'">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-semibold text-slate-800" x-text="menu.name"></p>
                                        <span class="text-xs font-bold text-sky-700" x-text="formatCurrency(menu.price)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </aside>

                    <main class="lg:col-span-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <h3 class="text-base font-bold text-slate-900">Voice Input</h3>
                        <p class="mt-1 text-xs text-slate-500">Klik mic, bicara, lalu submit order.</p>

                        <div class="mt-5 flex flex-wrap items-center gap-4">
                            <button type="button"
                                @click="toggleListening('customer')"
                                :disabled="!speechSupported"
                                class="relative inline-flex h-14 w-14 items-center justify-center rounded-full text-white transition"
                                :class="isListening ? 'bg-rose-500' : 'bg-sky-600 hover:bg-sky-700'">
                                <span x-show="isListening" class="absolute inset-0 rounded-full border-2 border-rose-300 pulse-ring"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18v3m0 0h3m-3 0H9m8-9a5 5 0 00-10 0v2a5 5 0 0010 0v-2z" />
                                </svg>
                            </button>

                            <button type="button"
                                @click="toggleListening('customer')"
                                :disabled="!speechSupported"
                                class="rounded-xl px-4 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="isListening ? 'bg-rose-500 hover:bg-rose-600' : 'bg-sky-600 hover:bg-sky-700'"
                                x-text="isListening ? 'Stop Input Suara' : 'Mulai Input Suara'">
                            </button>

                            <div>
                                <p class="text-sm font-semibold" x-text="isListening ? 'Mendengarkan...' : 'Siap menerima suara'"></p>
                                <p class="text-xs text-slate-500" x-show="speechSupported">Gunakan Chrome/Edge terbaru untuk hasil terbaik.</p>
                                <p class="text-xs text-rose-500" x-show="!speechSupported">Browser tidak mendukung Web Speech API. Pakai input manual di textarea.</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="raw_text" class="text-xs font-semibold text-slate-600">Transkrip / Input Manual</label>
                            <textarea id="raw_text" x-model="rawText" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Contoh: saya mau nasgor sama teh anget"></textarea>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" @click="submitOrder()" :disabled="submitting || !rawText.trim()" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                                <span x-text="submitting ? 'Memproses...' : 'Proses Order'"></span>
                            </button>
                            <button type="button" @click="resetState()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</button>
                        </div>

                        <div class="mt-5 min-h-[92px] rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold text-slate-600">Status</p>
                            <p class="mt-1 text-sm" :class="statusTone" x-text="statusMessage || 'Belum ada proses.'"></p>
                        </div>

                        <div class="mt-5">
                            <p class="text-xs font-semibold text-slate-600">Makanan terpilih (berdasarkan kata user)</p>
                            <div class="mt-2 flex min-h-[44px] flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-2">
                                <template x-if="detectedItems.length === 0">
                                    <span class="text-xs text-slate-400">Belum ada item terdeteksi</span>
                                </template>
                                <template x-for="item in detectedItems" :key="item.name + item.qty">
                                    <span class="chip-in rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800" x-text="item.qty > 1 ? `${item.name} x${item.qty}` : item.name"></span>
                                </template>
                            </div>
                        </div>
                    </main>

                    <aside class="lg:col-span-3 rounded-2xl border border-slate-200 bg-slate-900 p-4 text-white">
                        <h3 class="text-base font-bold">Antrian</h3>
                        <p class="mt-1 text-xs text-slate-300">Update dari response backend</p>

                        <div class="mt-4 rounded-xl bg-white/10 p-4">
                            <p class="text-xs text-slate-300">Nomor Antrian</p>
                            <p class="mt-1 text-4xl font-black tracking-wide queue-pop" x-text="queueNumber ?? '-'" :key="queueNumber"></p>
                        </div>

                        <div class="mt-4 rounded-xl bg-white/10 p-4">
                            <p class="text-xs text-slate-300">Order ID / Code</p>
                            <p class="mt-2 text-sm font-semibold" x-text="orderCode || '-'"></p>
                        </div>

                        <div class="mt-4 rounded-xl bg-white/10 p-4">
                            <p class="text-xs text-slate-300">Ringkasan Pesanan</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                <template x-if="finalItems.length === 0">
                                    <li class="text-slate-400">Belum ada order valid.</li>
                                </template>
                                <template x-for="item in finalItems" :key="item.name + item.qty">
                                    <li x-text="item.qty > 1 ? `${item.name} x${item.qty}` : item.name"></li>
                                </template>
                            </ul>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Panel Kasir</h3>
                        <p class="text-xs text-slate-500">Konfirmasi, batalkan, atau tambah item suara ke order aktif.</p>
                    </div>
                    <button type="button" @click="fetchCashierOrders()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Refresh Antrian
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-7 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="max-h-72 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th class="px-2 py-2">Queue</th>
                                        <th class="px-2 py-2">Order</th>
                                        <th class="px-2 py-2">Status</th>
                                        <th class="px-2 py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="loadingCashierOrders">
                                        <tr>
                                            <td class="px-2 py-3 text-slate-500" colspan="4">Memuat daftar order...</td>
                                        </tr>
                                    </template>

                                    <template x-if="!loadingCashierOrders && cashierOrders.length === 0">
                                        <tr>
                                            <td class="px-2 py-3 text-slate-500" colspan="4">Belum ada order aktif.</td>
                                        </tr>
                                    </template>

                                    <template x-for="order in cashierOrders" :key="order.id">
                                        <tr class="cursor-pointer border-t border-slate-200" @click="selectCashierOrder(order)"
                                            :class="selectedOrderId === order.id ? 'bg-cyan-100/70' : 'hover:bg-slate-100'">
                                            <td class="px-2 py-2 font-semibold text-slate-700" x-text="order.queue ? order.queue.queue_number : '-'" ></td>
                                            <td class="px-2 py-2">
                                                <p class="font-semibold text-slate-800" x-text="order.order_code"></p>
                                                <p class="text-xs text-slate-500" x-text="`#${order.id}`"></p>
                                            </td>
                                            <td class="px-2 py-2">
                                                <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700" x-text="order.status"></span>
                                            </td>
                                            <td class="px-2 py-2 font-semibold text-slate-700" x-text="formatCurrency(order.total_amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="lg:col-span-5 rounded-2xl border border-slate-200 bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Detail Order Terpilih</p>
                        <template x-if="!selectedCashierOrder()">
                            <p class="mt-3 text-sm text-slate-500">Pilih order dari tabel untuk aksi kasir.</p>
                        </template>

                        <template x-if="selectedCashierOrder()">
                            <div class="mt-3 space-y-3">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-sm font-semibold text-slate-800" x-text="selectedCashierOrder().order_code"></p>
                                    <ul class="mt-2 space-y-2 text-xs text-slate-700">
                                        <template x-for="item in selectedCashierOrder().items" :key="item.id">
                                            <li class="flex items-center justify-between gap-2 rounded border border-slate-200 bg-white p-2">
                                                <span x-text="`${item.item_name} x${item.qty}`"></span>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" class="h-6 w-6 rounded border border-slate-300 text-slate-700" :disabled="cashierBusy" @click="updateCashierItemQty(item, -1)">-</button>
                                                    <button type="button" class="h-6 w-6 rounded border border-slate-300 text-slate-700" :disabled="cashierBusy" @click="updateCashierItemQty(item, 1)">+</button>
                                                    <button type="button" class="rounded border border-rose-200 px-2 py-1 text-[11px] font-semibold text-rose-600" :disabled="cashierBusy" @click="removeCashierItem(item)">Hapus</button>
                                                </div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="confirmSelectedOrder()" :disabled="cashierBusy" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Konfirmasi</button>
                                    <button type="button" @click="cancelSelectedOrder()" :disabled="cashierBusy" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Batalkan</button>
                                </div>

                                <div class="rounded-lg border border-slate-200 p-3">
                                    <p class="text-xs font-semibold text-slate-600">Tambah Item Lagi (Suara)</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <button type="button" @click="toggleListening('cashier')" :disabled="!speechSupported" class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="isListening && listeningTarget === 'cashier' ? 'Stop Mic Kasir' : 'Mic Kasir'"></button>
                                    </div>
                                    <textarea x-model="cashierAppendText" rows="2" class="mt-2 w-full rounded-lg border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500" placeholder="Contoh: tambahkan 1 teh manis dingin"></textarea>
                                    <button type="button" @click="appendVoiceToSelected()" :disabled="cashierBusy || !cashierAppendText.trim()" class="mt-2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Tambahkan ke Order</button>
                                </div>

                                <p class="text-xs" :class="cashierTone" x-text="cashierMessage"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <script>
            function orderKiosk() {
                return {
                    menus: [],
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
                    banners: [
                        'Promo dummy: Diskon 20% untuk semua minuman panas di jam 08:00-10:00.',
                        'Banner dummy: Paket hemat nasi goreng + teh tersedia hari ini.',
                        'Banner dummy: Jalur express aktif untuk pesanan voice yang valid.'
                    ],
                    activeBanner: '',
                    bannerTimer: null,
                    showConfirmModal: false,
                    confirmingItems: [],
                    escapeKeyListener: null,
                    silenceTimeoutId: null,
                    silenceDelayMs: 3000,
                    shouldKeepListening: false,
                    customerSpeechCommitted: '',
                    cashierSpeechCommitted: '',
                    speechInterimCurrent: '',

                    init() {
                        this.fetchMenus();
                        this.fetchCashierOrders();
                        this.initSpeech();
                        this.rotateBanner();
                        this.setupKeyboardListener();
                        setInterval(() => this.fetchCashierOrders(), 5000);
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

                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const current = (event.results[i][0]?.transcript || '').trim();
                                if (!current) {
                                    continue;
                                }

                                if (event.results[i].isFinal) {
                                    this.appendFinalSpeech(current);
                                    continue;
                                }

                                interimTranscript = `${interimTranscript} ${current}`.trim();
                            }

                            this.speechInterimCurrent = interimTranscript;
                            this.syncListeningTargetText();
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
                        if (this.listeningTarget === 'cashier') {
                            this.cashierSpeechCommitted = `${this.cashierSpeechCommitted} ${text}`.trim();
                            return;
                        }

                        this.customerSpeechCommitted = `${this.customerSpeechCommitted} ${text}`.trim();
                    },

                    syncListeningTargetText() {
                        if (this.listeningTarget === 'cashier') {
                            this.cashierAppendText = `${this.cashierSpeechCommitted} ${this.speechInterimCurrent}`.trim();
                            return;
                        }

                        this.rawText = `${this.customerSpeechCommitted} ${this.speechInterimCurrent}`.trim();
                        this.detectedItems = this.previewDetectedItems(this.rawText);
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
                            this.customerSpeechCommitted = '';
                            this.rawText = '';
                            this.detectedItems = [];
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

                    previewDetectedItems(rawText) {
                        if (!rawText || this.menus.length === 0) {
                            return [];
                        }

                        const clean = rawText
                            .toLowerCase()
                            .replace(/\b(?:saya mau|aku mau|saya pesan|aku pesan|mau|pesan|tolong|dong|ya|kak)\b/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                        const parts = clean
                            .split(/\s*(?:,| dan | sama | plus |\+)\s*/)
                            .map((p) => p.trim())
                            .filter((p) => p.length > 0);

                        const lookup = this.buildMenuLookup();
                        const grouped = new Map();

                        parts.forEach((part) => {
                            const extractedItems = this.extractItemsFromSegment(this.normalizeNumberWords(part));

                            extractedItems.forEach((parsed) => {
                                const canonicalName = lookup.get(parsed.name);
                                if (!canonicalName) {
                                    return;
                                }

                                grouped.set(canonicalName, (grouped.get(canonicalName) || 0) + parsed.qty);
                            });
                        });

                        return Array.from(grouped.entries()).map(([name, qty]) => ({ name, qty }));
                    },

                    buildMenuLookup() {
                        const lookup = new Map();

                        this.menus.forEach((menu) => {
                            const canonical = String(menu.name || '').toLowerCase().trim();
                            if (!canonical) {
                                return;
                            }

                            lookup.set(canonical, canonical);

                            if (Array.isArray(menu.aliases)) {
                                menu.aliases.forEach((alias) => {
                                    const aliasName = String(alias.normalized_alias || alias.alias || '').toLowerCase().trim();
                                    if (aliasName) {
                                        lookup.set(aliasName, canonical);
                                    }
                                });
                            }
                        });

                        return lookup;
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

                    extractItemsFromSegment(segment) {
                        const clean = String(segment)
                            .replace(/\bx(\d+)\b/g, '$1')
                            .replace(/\b(\d+)x\b/g, '$1')
                            .replace(/\s+/g, ' ')
                            .trim();

                        const result = [];

                        if (/^\d+/.test(clean)) {
                            const leadPattern = /(\d+)\s*(?:x|kali)?\s+([a-z0-9\s]+?)(?=(?:\s+\d+\s*(?:x|kali)?\s+[a-z]|$))/g;
                            let match;
                            while ((match = leadPattern.exec(clean)) !== null) {
                                const name = match[2]
                                    .replace(/\b(?:x|kali)\b/g, ' ')
                                    .replace(/\s+/g, ' ')
                                    .trim();

                                if (name) {
                                    result.push({ name, qty: Math.max(1, parseInt(match[1], 10)) });
                                }
                            }
                        } else {
                            const tailPattern = /([a-z0-9\s]+?)\s+(?:x\s*)?(\d+)\s*(?:x|kali)?(?=(?:\s+[a-z][a-z0-9\s]*?\s+(?:x\s*)?\d+\s*(?:x|kali)?|$))/g;
                            let match;
                            while ((match = tailPattern.exec(clean)) !== null) {
                                const name = match[1]
                                    .replace(/\b(?:x|kali)\b/g, ' ')
                                    .replace(/\s+/g, ' ')
                                    .trim();

                                if (name) {
                                    result.push({ name, qty: Math.max(1, parseInt(match[2], 10)) });
                                }
                            }
                        }

                        if (result.length > 0) {
                            return result;
                        }

                        const lookup = this.buildMenuLookup();
                        const repeated = this.resolveRepeatedMenuName(clean, lookup);
                        if (repeated) {
                            return [repeated];
                        }

                        const composite = this.resolveCompositeMenuSequence(clean, lookup);
                        if (composite.length > 0) {
                            return composite;
                        }

                        const single = this.extractQtyAndNameFromSegment(clean);
                        return single.name ? [single] : [];
                    },

                    resolveRepeatedMenuName(segment, lookup) {
                        const normalized = String(segment).replace(/\s+/g, ' ').trim();
                        if (!normalized) {
                            return null;
                        }

                        const canonicalNames = Array.from(new Set(Array.from(lookup.values())))
                            .sort((a, b) => b.length - a.length);

                        for (const name of canonicalNames) {
                            const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            const pattern = new RegExp(`^(?:${escaped})(?:\\s+${escaped})+$`);
                            if (!pattern.test(normalized)) {
                                continue;
                            }

                            const count = (normalized.match(new RegExp(escaped, 'g')) || []).length;
                            if (count >= 2) {
                                return { name, qty: count };
                            }
                        }

                        return null;
                    },

                    resolveCompositeMenuSequence(segment, lookup) {
                        const normalized = String(segment).replace(/\s+/g, ' ').trim();
                        if (!normalized) {
                            return [];
                        }

                        const words = normalized.split(' ').filter(Boolean);
                        if (words.length < 2) {
                            return [];
                        }

                        const canonicalNames = Array.from(new Set(Array.from(lookup.values())))
                            .map((name) => ({ name, tokens: name.split(' ') }))
                            .sort((a, b) => (b.tokens.length * 1000 + b.name.length) - (a.tokens.length * 1000 + a.name.length));

                        if (canonicalNames.length === 0) {
                            return [];
                        }

                        const counts = new Map();
                        let cursor = 0;

                        while (cursor < words.length) {
                            let matched = null;

                            for (const item of canonicalNames) {
                                if (cursor + item.tokens.length > words.length) {
                                    continue;
                                }

                                const slice = words.slice(cursor, cursor + item.tokens.length).join(' ');
                                if (slice === item.name) {
                                    matched = item;
                                    break;
                                }
                            }

                            if (!matched) {
                                // Skip unknown token so known menu names can still be extracted.
                                cursor += 1;
                                continue;
                            }

                            counts.set(matched.name, (counts.get(matched.name) || 0) + 1);
                            cursor += matched.tokens.length;
                        }

                        if (counts.size === 0) {
                            return [];
                        }

                        return Array.from(counts.entries()).map(([name, qty]) => ({ name, qty }));
                    },

                    isMenuHighlighted(menuName) {
                        return this.detectedItems.some((item) => item.name === String(menuName).toLowerCase());
                    },

                    async submitOrder() {
                        if (!this.rawText.trim()) {
                            return;
                        }

                        this.statusMessage = 'Menganalisa pesanan...';
                        this.statusTone = 'text-slate-600';
                        this.detectedItems = this.previewDetectedItems(this.rawText);

                        if (this.detectedItems.length === 0) {
                            this.statusMessage = 'Preview lokal belum mengenali item, validasi server tetap akan dijalankan.';
                            this.statusTone = 'text-amber-600';
                        }

                        this.confirmingItems = [...this.detectedItems];
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

                    changeConfirmingItemQty(name, delta) {
                        this.confirmingItems = this.confirmingItems.map((item) => {
                            if (item.name !== name) {
                                return item;
                            }

                            return {
                                ...item,
                                qty: Math.max(1, Number(item.qty || 1) + delta),
                            };
                        });
                    },

                    removeConfirmingItem(name) {
                        this.confirmingItems = this.confirmingItems.filter((item) => item.name !== name);
                    },

                    buildRawTextFromConfirmingItems() {
                        return this.confirmingItems
                            .map((item) => {
                                const qty = Math.max(1, Number(item.qty || 1));
                                return qty > 1 ? `${qty} ${item.name}` : item.name;
                            })
                            .join(', ');
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
                            });

                            const data = response.data;

                            this.finalItems = data.items || [];
                            this.queueNumber = data.queue_number || null;
                            this.orderCode = data.order_code || '';
                            this.statusMessage = data.message || 'Order berhasil dibuat.';
                            this.statusTone = 'text-emerald-600';
                            this.closeConfirmModal();
                            this.resetState();
                            await this.fetchCashierOrders();
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
                        this.rawText = '';
                        this.detectedItems = [];
                        this.confirmingItems = [];
                        this.finalItems = [];
                        this.queueNumber = null;
                        this.orderCode = '';
                        this.statusMessage = '';
                        this.statusTone = 'text-slate-600';
                    },

                    rotateBanner() {
                        let index = 0;
                        this.activeBanner = this.banners[index];
                        this.bannerTimer = setInterval(() => {
                            index = (index + 1) % this.banners.length;
                            this.activeBanner = this.banners[index];
                        }, 4500);
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
