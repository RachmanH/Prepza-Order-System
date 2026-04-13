<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Antrian') }}
            </h2>
            <p class="text-sm text-gray-500">Kelola antrian pesanan makanan secara realtime</p>
        </div>
    </x-slot>

    <div class="py-10 bg-gray-100" x-data="queuePanel()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Queue Console</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Antrian Pesanan Makanan</h3>
                        <p class="mt-1 text-sm text-gray-500">Layer 1 dapat menambah atau membatalkan pesanan. Status proses/selesai dikirim dari Layer 2.</p>
                    </div>
                    <button type="button" @click="fetchOrders()" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Refresh Antrian
                    </button>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Menunggu</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-600" x-text="queueCount('waiting')"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Diproses</p>
                        <p class="mt-1 text-3xl font-bold text-amber-600" x-text="queueCount('processing')"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Selesai</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-600" x-text="queueCount('done')"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Dibatalkan</p>
                        <p class="mt-1 text-3xl font-bold text-rose-600" x-text="queueCount('cancelled')"></p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusFilter === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700'" @click="statusFilter = 'all'">Semua</button>
                    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusFilter === 'active' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" @click="statusFilter = 'active'">Aktif</button>
                    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusFilter === 'done' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700'" @click="statusFilter = 'done'">Selesai</button>
                    <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusFilter === 'cancelled' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700'" @click="statusFilter = 'cancelled'">Batal</button>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-7 rounded-2xl border border-gray-200 bg-gray-50 p-3">
                        <div class="max-h-72 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                        <th class="px-2 py-2">Queue</th>
                                        <th class="px-2 py-2">Order</th>
                                        <th class="px-2 py-2">Pelanggan</th>
                                        <th class="px-2 py-2">Status</th>
                                        <th class="px-2 py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="loadingOrders">
                                        <tr>
                                            <td class="px-2 py-3 text-gray-500" colspan="5">Memuat daftar antrian...</td>
                                        </tr>
                                    </template>

                                    <template x-if="!loadingOrders && filteredOrders().length === 0">
                                        <tr>
                                            <td class="px-2 py-3 text-gray-500" colspan="5">Belum ada data antrian.</td>
                                        </tr>
                                    </template>

                                    <template x-for="order in filteredOrders()" :key="order.id">
                                        <tr class="cursor-pointer border-t border-gray-200" @click="selectOrder(order)"
                                            :class="selectedOrderId === order.id ? 'bg-indigo-100/70' : 'hover:bg-gray-100'">
                                            <td class="px-2 py-2 font-semibold text-gray-700" x-text="order.queue ? order.queue.queue_number : '-'" ></td>
                                            <td class="px-2 py-2">
                                                <p class="font-semibold text-gray-800" x-text="order.order_code"></p>
                                                <p class="text-xs text-gray-500" x-text="`#${order.id}`"></p>
                                            </td>
                                            <td class="px-2 py-2">
                                                <p class="font-semibold text-gray-700" x-text="order.customer_name || '-'" ></p>
                                                <p class="text-xs text-gray-500" x-text="genderLabel(order.gender)" ></p>
                                            </td>
                                            <td class="px-2 py-2">
                                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="statusTone(order.status)" x-text="order.status"></span>
                                            </td>
                                            <td class="px-2 py-2 font-semibold text-gray-700" x-text="formatCurrency(order.total_amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="lg:col-span-5 rounded-2xl border border-gray-200 bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Detail Pesanan</p>
                        <template x-if="!selectedOrder()">
                            <p class="mt-3 text-sm text-gray-500">Pilih order dari tabel untuk aksi antrian.</p>
                        </template>

                        <template x-if="selectedOrder()">
                            <div class="mt-3 space-y-3">
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <p class="text-sm font-semibold text-gray-800" x-text="selectedOrder().order_code"></p>
                                    <p class="mt-1 text-xs text-gray-500" x-text="selectedOrder().customer_name || '-'" ></p>
                                    <p class="text-xs text-gray-500" x-text="genderLabel(selectedOrder().gender)" ></p>
                                    <p class="mt-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="externalStatusTone(selectedOrder().external_status)">
                                        Sinkron Layer 2: <span class="ml-1" x-text="externalStatusLabel(selectedOrder().external_status)"></span>
                                    </p>
                                    <ul class="mt-2 space-y-2 text-xs text-gray-700">
                                        <template x-for="item in selectedOrder().items" :key="item.id">
                                            <li class="space-y-2 rounded border border-gray-200 bg-white p-2">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span x-text="`${item.item_name} x${item.qty}`"></span>
                                                    <div class="flex items-center gap-1">
                                                        <button type="button" class="h-6 w-6 rounded border border-gray-300 text-gray-700" :disabled="busy" @click="updateItemQty(item, -1)">-</button>
                                                        <button type="button" class="h-6 w-6 rounded border border-gray-300 text-gray-700" :disabled="busy" @click="updateItemQty(item, 1)">+</button>
                                                        <button type="button" class="rounded border border-rose-200 px-2 py-1 text-[11px] font-semibold text-rose-600" :disabled="busy" @click="removeItem(item)">Hapus</button>
                                                    </div>
                                                </div>
                                                <input type="text"
                                                    :value="getItemNoteDraft(item)"
                                                    @focus="isEditingNote = true"
                                                    @input="setItemNoteDraft(item, $event.target.value)"
                                                    @blur="isEditingNote = false; updateItemNote(item, getItemNoteDraft(item))"
                                                    class="w-full rounded-md border-gray-300 text-[11px] focus:border-indigo-500 focus:ring-indigo-500"
                                                    placeholder="Keterangan item (contoh: jangan pedas)">
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div class="flex flex-wrap gap-2" x-show="['queued', 'waiting', 'processing'].includes(String(selectedOrder()?.status || '').toLowerCase())">
                                    <button type="button" @click="cancelSelectedOrder()" :disabled="busy" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Batalkan</button>
                                </div>

                                <div class="rounded-lg border border-gray-200 p-3">
                                    <p class="text-xs font-semibold text-gray-600">Tambah Item Lagi (Suara)</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            @click="toggleListening()"
                                            :disabled="busy || !speechSupported"
                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
                                            :class="isListening ? 'bg-rose-600 hover:bg-rose-700' : 'bg-slate-700 hover:bg-slate-800'"
                                        >
                                            <span x-text="isListening ? 'Stop Mic' : 'Mulai Mic'"></span>
                                        </button>
                                        <p class="text-[11px] text-gray-500" x-show="speechSupported">
                                            <span x-text="isListening ? 'Mikrofon aktif, silakan bicara...' : 'Klik Mulai Mic untuk isi teks otomatis.'"></span>
                                        </p>
                                        <p class="text-[11px] text-rose-600" x-show="!speechSupported">
                                            Browser ini belum mendukung fitur mic (Speech Recognition).
                                        </p>
                                    </div>
                                    <textarea x-model="appendText" rows="2" class="mt-2 w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: tambahkan 1 teh manis dingin"></textarea>
                                    <button type="button" @click="appendVoiceToSelected()" :disabled="busy || !appendText.trim()" class="mt-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Tambahkan ke Order</button>
                                </div>

                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 text-[11px] text-gray-600">
                                    Status proses dan selesai dikendalikan Layer 2 melalui endpoint: PATCH /api/queue/orders/{id}/external-update
                                </div>

                                <p class="text-xs" :class="messageTone" x-text="message"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <script>
            function queuePanel() {
                return {
                    orders: [],
                    loadingOrders: false,
                    selectedOrderId: null,
                    appendText: '',
                    busy: false,
                    message: '',
                    messageTone: 'text-slate-600',
                    statusFilter: 'active',
                    speechSupported: false,
                    isListening: false,
                    recognition: null,
                    silenceTimeoutId: null,
                    silenceDelayMs: 3000,
                    shouldKeepListening: false,
                    speechCommitted: '',
                    speechInterimCurrent: '',
                    noteDrafts: {},
                    isEditingNote: false,

                    init() {
                        this.initSpeech();
                        this.fetchOrders();
                        setInterval(() => {
                            if (!this.isEditingNote) {
                                this.fetchOrders();
                            }
                        }, 5000);
                    },

                    initSpeech() {
                        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                        this.speechSupported = !!SpeechRecognition;

                        if (!this.speechSupported) {
                            return;
                        }

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
                                    this.speechCommitted = `${this.speechCommitted} ${current}`.trim();
                                    continue;
                                }

                                interimTranscript = `${interimTranscript} ${current}`.trim();
                            }

                            this.speechInterimCurrent = interimTranscript;
                            this.appendText = `${this.speechCommitted} ${this.speechInterimCurrent}`.trim();
                        };

                        this.recognition.onend = () => {
                            this.clearSilenceTimer();

                            if (this.shouldKeepListening) {
                                try {
                                    this.recognition.start();
                                    return;
                                } catch (error) {
                                    this.shouldKeepListening = false;
                                }
                            }

                            this.isListening = false;
                        };

                        this.recognition.onerror = () => {
                            this.clearSilenceTimer();
                            this.isListening = false;
                            this.shouldKeepListening = false;
                            this.message = 'Mic tidak bisa digunakan. Pastikan izin mikrofon di browser sudah diizinkan.';
                            this.messageTone = 'text-rose-600';
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

                            if (!this.recognition) {
                                this.isListening = false;
                                return;
                            }

                            try {
                                this.recognition.stop();
                            } catch (error) {
                                this.isListening = false;
                            }
                        }, this.silenceDelayMs);
                    },

                    toggleListening() {
                        if (!this.speechSupported || !this.recognition || this.busy) {
                            return;
                        }

                        if (this.isListening) {
                            this.shouldKeepListening = false;
                            this.clearSilenceTimer();
                            this.recognition.stop();
                            this.isListening = false;
                            return;
                        }

                        this.message = '';
                        this.speechCommitted = String(this.appendText || '').trim();
                        this.speechInterimCurrent = '';
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

                    async fetchOrders() {
                        this.loadingOrders = true;
                        try {
                            const response = await axios.get('/api/queue/orders', {
                                params: {
                                    status: 'queued,waiting,processing,done,cancelled',
                                },
                            });
                            this.orders = response.data.data || [];
                            this.pruneNoteDrafts();
                            if (this.selectedOrderId && !this.orders.find((order) => order.id === this.selectedOrderId)) {
                                this.selectedOrderId = null;
                            }
                        } catch (error) {
                            this.message = 'Gagal memuat daftar antrian.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.loadingOrders = false;
                        }
                    },

                    selectOrder(order) {
                        this.selectedOrderId = order.id;
                        this.message = '';
                    },

                    selectedOrder() {
                        return this.orders.find((order) => order.id === this.selectedOrderId) || null;
                    },

                    getItemNoteDraft(item) {
                        const key = String(item?.id || '');
                        if (!key) {
                            return '';
                        }

                        if (Object.prototype.hasOwnProperty.call(this.noteDrafts, key)) {
                            return this.noteDrafts[key];
                        }

                        return String(item?.note || '');
                    },

                    setItemNoteDraft(item, value) {
                        const key = String(item?.id || '');
                        if (!key) {
                            return;
                        }

                        this.noteDrafts[key] = String(value || '');
                    },

                    pruneNoteDrafts() {
                        const validIds = new Set(
                            this.orders.flatMap((order) => (order.items || []).map((item) => String(item.id)))
                        );

                        Object.keys(this.noteDrafts).forEach((key) => {
                            if (!validIds.has(String(key))) {
                                delete this.noteDrafts[key];
                            }
                        });
                    },

                    filteredOrders() {
                        if (this.statusFilter === 'all') {
                            return this.orders;
                        }

                        if (this.statusFilter === 'active') {
                            return this.orders.filter((order) => ['queued', 'waiting', 'processing'].includes(String(order.status || '').toLowerCase()));
                        }

                        return this.orders.filter((order) => String(order.status || '').toLowerCase() === this.statusFilter);
                    },

                    queueCount(status) {
                        return this.orders.filter((order) => String(order.status || '').toLowerCase() === status).length;
                    },

                    async cancelSelectedOrder() {
                        await this.patchQueueAction('/cancel', 'Order dibatalkan.');
                    },

                    async patchQueueAction(suffix, fallbackMessage) {
                        const order = this.selectedOrder();
                        if (!order) {
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.patch(`/api/queue/orders/${order.id}${suffix}`);
                            this.message = response.data.message || fallbackMessage;
                            this.messageTone = 'text-emerald-600';
                            await this.fetchOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Aksi antrian gagal diproses.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    async appendVoiceToSelected() {
                        const order = this.selectedOrder();
                        if (!order || !this.appendText.trim()) {
                            return;
                        }

                        const handledNoteCommand = await this.applyVoiceNoteCommandToSelected(this.appendText);
                        if (handledNoteCommand) {
                            this.appendText = '';
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.post(`/api/queue/orders/${order.id}/append-voice`, {
                                raw_text: this.appendText,
                            });

                            this.message = response.data.message || 'Item tambahan berhasil ditambahkan.';
                            this.messageTone = 'text-emerald-600';
                            this.appendText = '';
                            await this.fetchOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal menambah item ke order.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    normalizeCommandText(text) {
                        return String(text || '')
                            .toLowerCase()
                            .replace(/\b(?:saya mau|aku mau|saya pesan|aku pesan|mau|pesan|tolong|dong|ya|kak|nih|deh|please)\b/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                    },

                    normalizePossessiveSuffix(text) {
                        return String(text || '')
                            .replace(/\b([a-z0-9]{3,})nya\b/g, '$1')
                            .replace(/\s+/g, ' ')
                            .trim();
                    },

                    async resolveMenuCandidate(candidate) {
                        const normalized = this.normalizePossessiveSuffix(this.normalizeCommandText(candidate));

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

                    async applyVoiceNoteCommandToSelected(rawText) {
                        const order = this.selectedOrder();
                        if (!order || !Array.isArray(order.items) || order.items.length === 0) {
                            return false;
                        }

                        const normalized = this.normalizeCommandText(rawText);
                        const directPattern = normalized.match(/^(.*?)\s*(?:keterangan|catatan|note)\s+(.+)$/);

                        if (!directPattern) {
                            return false;
                        }

                        const rawTargetPart = this.normalizePossessiveSuffix(String(directPattern[1] || '').trim());
                        const rawNotePart = String(directPattern[2] || '').replace(/\b(?:oke|ok|okay)\b$/g, '').trim();

                        if (!rawTargetPart || !rawNotePart) {
                            this.message = 'Format keterangan belum lengkap.';
                            this.messageTone = 'text-rose-600';
                            return true;
                        }

                        const normalizedTargetPart = this.normalizePossessiveSuffix(rawTargetPart);
                        const orderItemNames = (order.items || [])
                            .map((item) => this.normalizePossessiveSuffix(String(item.item_name || '').toLowerCase()))
                            .filter(Boolean)
                            .sort((a, b) => b.length - a.length);

                        let resolvedName = '';

                        // Primary: direct match from currently selected order items (most reliable).
                        resolvedName = orderItemNames.find((name) => normalizedTargetPart.includes(name)) || '';

                        // Secondary: trim leading words and retry local match.
                        if (!resolvedName && normalizedTargetPart.includes(' ')) {
                            const tokens = normalizedTargetPart.split(/\s+/).filter(Boolean);
                            for (let start = 0; start < tokens.length; start++) {
                                const candidate = this.normalizePossessiveSuffix(tokens.slice(start).join(' '));
                                const localMatch = orderItemNames.find((name) => candidate.includes(name));
                                if (localMatch) {
                                    resolvedName = localMatch;
                                    break;
                                }
                            }
                        }

                        // Fallback: resolve via menu API.
                        if (!resolvedName) {
                            const resolved = await this.resolveMenuCandidate(normalizedTargetPart);
                            if (resolved?.matched_name) {
                                resolvedName = this.normalizePossessiveSuffix(String(resolved.matched_name).toLowerCase());
                            }
                        }

                        if (!resolvedName) {
                            this.message = 'Target menu untuk keterangan tidak dikenali.';
                            this.messageTone = 'text-rose-600';
                            return true;
                        }

                        let note = rawNotePart;
                        if (note.startsWith(`${resolvedName} `)) {
                            note = note.slice(resolvedName.length).trim();
                        }

                        // In the "Tambah Item Lagi" panel, this command should add a new item
                        // and attach note to the newly added row, not overwrite existing notes.
                        this.busy = true;
                        try {
                            await axios.post(`/api/queue/orders/${order.id}/append-voice`, {
                                raw_text: resolvedName,
                            });

                            await this.fetchOrders();

                            const refreshedOrder = this.selectedOrder();
                            const matches = (refreshedOrder?.items || [])
                                .filter((item) => this.normalizePossessiveSuffix(String(item.item_name || '').toLowerCase()) === resolvedName);

                            const targetItem = [...matches].reverse().find((item) => !String(item.note || '').trim())
                                || matches[matches.length - 1]
                                || null;

                            if (!targetItem) {
                                this.message = `Item ${resolvedName} berhasil ditambahkan, tetapi note belum bisa dipasang.`;
                                this.messageTone = 'text-amber-600';
                                return true;
                            }

                            await axios.patch(`/api/queue/orders/${order.id}/items/${targetItem.id}`, {
                                note: note || null,
                            });

                            this.noteDrafts[String(targetItem.id)] = note;
                            await this.fetchOrders();
                            this.message = `Item ${resolvedName} ditambahkan dengan keterangan.`;
                            this.messageTone = 'text-emerald-600';
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal menambahkan item dengan keterangan.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }

                        return true;
                    },

                    async updateItemQty(item, delta) {
                        const order = this.selectedOrder();
                        if (!order || !item) {
                            return;
                        }

                        const nextQty = Number(item.qty || 1) + delta;
                        if (nextQty <= 0) {
                            await this.removeItem(item);
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.patch(`/api/queue/orders/${order.id}/items/${item.id}`, {
                                qty: nextQty,
                            });
                            this.message = response.data.message || 'Qty item diperbarui.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal mengubah qty item.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    async updateItemNote(item, noteValue) {
                        const order = this.selectedOrder();
                        if (!order || !item) {
                            return;
                        }

                        const nextNote = String(noteValue || '').trim();
                        const currentNote = String(item.note || '').trim();

                        if (nextNote === currentNote) {
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.patch(`/api/queue/orders/${order.id}/items/${item.id}`, {
                                note: nextNote || null,
                            });
                            this.noteDrafts[String(item.id)] = nextNote;
                            this.message = response.data.message || 'Keterangan item diperbarui.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal memperbarui keterangan item.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    async removeItem(item) {
                        const order = this.selectedOrder();
                        if (!order || !item) {
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.delete(`/api/queue/orders/${order.id}/items/${item.id}`);
                            this.message = response.data.message || 'Item berhasil dihapus.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchOrders();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal menghapus item.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    statusTone(value) {
                        const status = String(value || '').toLowerCase();

                        if (status === 'processing') {
                            return 'bg-amber-100 text-amber-700';
                        }

                        if (status === 'done') {
                            return 'bg-emerald-100 text-emerald-700';
                        }

                        if (status === 'cancelled') {
                            return 'bg-rose-100 text-rose-700';
                        }

                        return 'bg-slate-200 text-slate-700';
                    },

                    normalizeExternalStatus(value) {
                        const status = String(value || '').toLowerCase();

                        if (status === 'processing' || status === 'done' || status === 'waiting') {
                            return status;
                        }

                        if (status === 'received' || status === 'not_set') {
                            return 'waiting';
                        }

                        return 'waiting';
                    },

                    externalStatusLabel(value) {
                        const status = this.normalizeExternalStatus(value);

                        if (status === 'processing') {
                            return 'Diproses';
                        }

                        if (status === 'done') {
                            return 'Selesai';
                        }

                        return 'Menunggu';
                    },

                    externalStatusTone(value) {
                        const status = this.normalizeExternalStatus(value);

                        if (status === 'processing') {
                            return 'bg-amber-100 text-amber-700';
                        }

                        if (status === 'done') {
                            return 'bg-emerald-100 text-emerald-700';
                        }

                        return 'bg-blue-100 text-blue-700';
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
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
