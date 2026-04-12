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

                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                    <p><span class="font-semibold text-slate-800">Nama:</span> <span x-text="customerName || '-'">-</span></p>
                    <p class="mt-1"><span class="font-semibold text-slate-800">Jenis kelamin:</span> <span x-text="genderLabel(customerGender)">-</span></p>
                </div>
                
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

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="customer_name" class="text-xs font-semibold text-slate-600">Nama Pelanggan</label>
                                <input id="customer_name" x-model="customerName" type="text" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Masukkan nama pelanggan">
                            </div>
                            <div>
                                <label for="customer_gender" class="text-xs font-semibold text-slate-600">Jenis Kelamin (opsional)</label>
                                <select id="customer_gender" x-model="customerGender" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">- Tidak diisi -</option>
                                    <option value="male">Laki-laki</option>
                                    <option value="female">Perempuan</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                        </div>

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
                            <textarea id="raw_text" x-model="rawText" @input="scheduleDraftPreview()" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Contoh: saya mau nasgor sama teh anget"></textarea>
                        </div>

                        <div class="mt-4">
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

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" @click="submitOrder()" :disabled="submitting || !rawText.trim() || !customerName.trim()" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                                <span x-text="submitting ? 'Memproses...' : 'Proses Order'"></span>
                            </button>
                            <button type="button" @click="resetState()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</button>
                        </div>

                        <div class="mt-5 min-h-[92px] rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold text-slate-600">Status</p>
                            <p class="mt-1 text-sm" :class="statusTone" x-text="statusMessage || 'Belum ada proses.'"></p>
                        </div>

                    </main>

                    <aside class="lg:col-span-3 rounded-2xl border border-slate-200 bg-slate-900 p-4 text-white">
                        <h3 class="text-base font-bold">Antrian</h3>
                        <p class="mt-1 text-xs text-slate-300">Update dari response backend</p>

                        <div class="mt-4 rounded-xl bg-white/10 p-4">
                            <p class="text-xs text-slate-300">Nama Pelanggan</p>
                            <p class="mt-1 text-sm font-semibold" x-text="lastOrderCustomerName || '-'" ></p>
                            <p class="mt-2 text-xs text-slate-300">Jenis Kelamin</p>
                            <p class="mt-1 text-sm font-semibold" x-text="genderLabel(lastOrderCustomerGender)"></p>
                        </div>

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
                    customerName: '',
                    customerGender: '',
                    lastOrderCustomerName: '',
                    lastOrderCustomerGender: '',
                    draftPreviewTimer: null,
                    draftPreviewRequestId: 0,
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
                        this.initSpeech();
                        this.rotateBanner();
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
                        }));

                        if (!this.showConfirmModal) {
                            this.confirmingItems = [...this.detectedItems];
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
                        this.updateConfirmingItemQty(name, delta);
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
                            return false;
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
                                customer_name: this.customerName,
                                gender: this.customerGender || null,
                            });

                            const data = response.data;

                            this.finalItems = data.items || [];
                            this.queueNumber = data.queue_number || null;
                            this.orderCode = data.order_code || '';
                            this.lastOrderCustomerName = this.customerName;
                            this.lastOrderCustomerGender = this.customerGender;
                            this.statusMessage = data.message || 'Order berhasil dibuat.';
                            this.statusTone = 'text-emerald-600';
                            this.closeConfirmModal();
                            this.clearDraftState();
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
