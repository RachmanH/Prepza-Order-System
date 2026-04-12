<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Panel Kasir') }}
            </h2>
            <p class="text-sm text-gray-500">Kelola antrian dan order aktif</p>
        </div>
    </x-slot>

    <div class="py-10" x-data="cashierPanel()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Daftar Antrian Aktif</h3>
                        <p class="text-xs text-slate-500">Konfirmasi, batalkan, atau ubah item pesanan.</p>
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
                                        <th class="px-2 py-2">Pelanggan</th>
                                        <th class="px-2 py-2">Status</th>
                                        <th class="px-2 py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="loadingCashierOrders">
                                        <tr>
                                            <td class="px-2 py-3 text-slate-500" colspan="5">Memuat daftar order...</td>
                                        </tr>
                                    </template>

                                    <template x-if="!loadingCashierOrders && cashierOrders.length === 0">
                                        <tr>
                                            <td class="px-2 py-3 text-slate-500" colspan="5">Belum ada order aktif.</td>
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
                                                <p class="font-semibold text-slate-700" x-text="order.customer_name || '-'" ></p>
                                                <p class="text-xs text-slate-500" x-text="genderLabel(order.gender)" ></p>
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
                                    <p class="mt-1 text-xs text-slate-500" x-text="selectedCashierOrder().customer_name || '-'" ></p>
                                    <p class="text-xs text-slate-500" x-text="genderLabel(selectedCashierOrder().gender)" ></p>
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
                                        <button type="button" @click="toggleListening()" :disabled="!speechSupported" class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="isListening ? 'Stop Mic Kasir' : 'Mic Kasir'"></button>
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
            function cashierPanel() {
                return {
                    cashierOrders: [],
                    loadingCashierOrders: false,
                    selectedOrderId: null,
                    cashierAppendText: '',
                    cashierBusy: false,
                    cashierMessage: '',
                    cashierTone: 'text-slate-600',
                    speechSupported: false,
                    recognition: null,
                    isListening: false,
                    cashierSpeechCommitted: '',
                    speechInterimCurrent: '',

                    init() {
                        this.fetchCashierOrders();
                        this.initSpeech();
                        setInterval(() => this.fetchCashierOrders(), 5000);
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

                        this.recognition.onresult = (event) => {
                            let interimTranscript = '';

                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const current = (event.results[i][0]?.transcript || '').trim();
                                if (!current) {
                                    continue;
                                }

                                if (event.results[i].isFinal) {
                                    this.cashierSpeechCommitted = `${this.cashierSpeechCommitted} ${current}`.trim();
                                } else {
                                    interimTranscript = `${interimTranscript} ${current}`.trim();
                                }
                            }

                            this.speechInterimCurrent = interimTranscript;
                            this.cashierAppendText = `${this.cashierSpeechCommitted} ${this.speechInterimCurrent}`.trim();
                        };

                        this.recognition.onend = () => {
                            this.isListening = false;
                        };

                        this.recognition.onerror = () => {
                            this.isListening = false;
                        };
                    },

                    toggleListening() {
                        if (!this.speechSupported || !this.recognition) {
                            return;
                        }

                        if (this.isListening) {
                            this.recognition.stop();
                            this.isListening = false;
                            return;
                        }

                        this.cashierSpeechCommitted = '';
                        this.speechInterimCurrent = '';
                        this.cashierAppendText = '';
                        this.isListening = true;

                        try {
                            this.recognition.start();
                        } catch (error) {
                            this.isListening = false;
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

                    normalizeCommandText(text) {
                        return String(text)
                            .toLowerCase()
                            .replace(/\b(?:saya mau|aku mau|saya pesan|aku pesan|mau|pesan|tolong|dong|ya|kak|nih|deh|please)\b/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
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
