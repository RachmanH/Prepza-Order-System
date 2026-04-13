<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Display Antrian') }}
            </h2>
            <div class="text-right text-xs text-gray-500" x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)">
                <p>Realtime Queue Board</p>
                <p x-text="now.toLocaleTimeString('id-ID')"></p>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-gray-100" x-data="queueBoard()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Queue Display</p>
                        <h3 class="text-lg font-semibold text-gray-900">Antrian Pesanan Makanan</h3>
                        <p class="text-sm text-gray-500">Status diproses dan selesai dikirim dari Layer 2.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                          :class="boardState === 'online' ? 'bg-emerald-100 text-emerald-700' : (boardState === 'standby' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')"
                          x-text="boardState"></span>
                </div>

                <div class="mt-4 flex gap-3 overflow-x-auto pb-1">
                    <div class="min-w-[220px] flex-1 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Sedang Dilayani</p>
                        <p class="mt-2 text-4xl font-bold text-indigo-600" x-text="current ? queueLabel(current.display_queue_number ?? current.queue_number) : '-' "></p>
                    </div>
                    <div class="min-w-[220px] flex-1 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Antrian Menunggu</p>
                        <p class="mt-2 text-4xl font-bold text-indigo-600" x-text="upcoming.length"></p>
                    </div>
                    <div class="min-w-[220px] flex-1 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Selesai Terbaru</p>
                        <p class="mt-2 text-4xl font-bold text-indigo-600" x-text="recentDone.length"></p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="lg:col-span-7 space-y-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Nomor Antrian Aktif</p>
                        <div class="mt-4 rounded-2xl bg-indigo-50 p-6 text-center">
                            <p class="text-7xl font-bold text-indigo-700" x-text="current ? queueLabel(current.display_queue_number ?? current.queue_number) : '-' "></p>
                            <p class="mt-3 text-sm font-semibold text-gray-700" x-text="current ? current.order_code : 'Belum ada pesanan aktif'"></p>
                            <p class="text-sm text-gray-500" x-text="current?.customer_name || 'Pelanggan Umum'"></p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Antrian Berikutnya</p>
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <template x-if="upcoming.length === 0">
                                <p class="col-span-full rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">Belum ada antrian menunggu.</p>
                            </template>
                            <template x-for="(item, idx) in upcoming" :key="item.order_id">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                                    <p class="text-[11px] uppercase tracking-wide text-gray-500" x-text="`Antrian ${idx + 1}`"></p>
                                    <p class="mt-2 text-3xl font-bold text-gray-800" x-text="queueLabel(item.display_queue_number ?? item.queue_number)"></p>
                                    <p class="truncate text-xs text-gray-500" x-text="item.customer_name || 'Pelanggan Umum'"></p>
                                    <button
                                        type="button"
                                        class="mt-2 w-full rounded-md border border-indigo-200 bg-white px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"
                                        :disabled="!audioEnabled"
                                        @click="replayQueueCall(item.display_queue_number ?? item.queue_number, item.customer_name)"
                                    >
                                        Replay
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Tren Makanan Sekarang</p>
                            <template x-if="trend && trend.score !== null && trend.score !== undefined">
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700" x-text="`Skor ${trend.score}`"></span>
                            </template>
                        </div>

                        <template x-if="trend">
                            <div class="mt-3 space-y-2">
                                <img :src="trend.image_url" alt="Tren makanan" class="h-40 w-full rounded-xl border border-gray-200 object-cover">
                                <p class="text-sm font-semibold text-gray-900" x-text="trend.title"></p>
                                <p class="text-xs text-gray-600" x-text="trend.caption || '-'"></p>
                            </div>
                        </template>

                        <template x-if="!trend">
                            <div class="mt-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-sm text-gray-500">
                                Belum ada tren makanan terbaru dari Layer 2.
                            </div>
                        </template>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Audio Pengumuman</p>
                        <p class="mt-2 text-sm text-gray-600">Pengumuman otomatis: nama pelanggan dan nomor antrian dipanggil.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" @click="toggleAudio()" class="rounded-lg px-3 py-2 text-xs font-semibold"
                                    :class="audioEnabled ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white'"
                                    x-text="audioEnabled ? 'Audio Aktif' : 'Aktifkan Audio'"></button>
                            <button
                                type="button"
                                class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"
                                :disabled="!audioEnabled || !lastAnnouncementText"
                                @click="replayLastAnnouncement()"
                            >
                                Replay Terakhir
                            </button>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500">Saat audio aktif, Anda juga bisa replay panggilan secara manual.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Pesanan Selesai Terbaru</p>
                            <p class="text-[11px] text-gray-400">max 8</p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-if="recentDone.length === 0">
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-500">Belum ada pesanan selesai.</span>
                            </template>
                            <template x-for="item in recentDone.slice(0, 8)" :key="`done-${item.order_id}`">
                                <button
                                    type="button"
                                    class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-200 disabled:opacity-50"
                                    :disabled="!audioEnabled"
                                    @click="replayDoneAnnouncement(item.display_queue_number ?? item.queue_number, item.customer_name)"
                                    x-text="queueLabel(item.display_queue_number ?? item.queue_number)">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                    <p>Warung Makan/Cafe Queue Board</p>
                    <p>Terakhir sinkron: <span class="font-semibold text-gray-700" x-text="lastSyncedAt"></span></p>
                </div>
            </section>
        </div>

        <script>
            function queueBoard() {
                return {
                    current: null,
                    upcoming: [],
                    recentDone: [],
                    trend: null,
                    boardState: 'connecting',
                    lastSyncedAt: '-',
                    audioEnabled: false,
                    lastAnnouncementText: '',
                    announcedDoneSet: new Set(),
                    hasHydratedDoneState: false,

                    init() {
                        const saved = localStorage.getItem('queue-board-audio-enabled');
                        this.audioEnabled = saved === '1';
                        this.lastAnnouncementText = localStorage.getItem('queue-board-last-announcement') || '';

                        try {
                            const announcedRaw = localStorage.getItem('queue-board-announced-done') || '[]';
                            JSON.parse(announcedRaw).forEach((value) => this.announcedDoneSet.add(String(value)));
                        } catch (error) {
                            this.announcedDoneSet = new Set();
                        }

                        this.fetchBoard();
                        setInterval(() => this.fetchBoard(), 2500);
                    },

                    async fetchBoard() {
                        try {
                            const response = await axios.get('/api/queue/board');
                            const data = response.data?.data || {};
                            this.current = data.current || null;
                            this.upcoming = data.upcoming || [];
                            this.recentDone = data.recent_done || [];
                            this.trend = data.trend || null;
                            this.lastSyncedAt = new Date().toLocaleTimeString('id-ID');
                            this.boardState = this.current ? 'online' : 'standby';
                            this.announceDoneIfNeeded();
                        } catch (error) {
                            this.boardState = 'offline';
                        }
                    },

                    queueLabel(value) {
                        if (value === null || value === undefined) {
                            return '-';
                        }

                        const asNumber = Number(value);
                        if (Number.isNaN(asNumber)) {
                            return String(value);
                        }

                        return `A${asNumber}`;
                    },

                    toggleAudio() {
                        this.audioEnabled = !this.audioEnabled;
                        localStorage.setItem('queue-board-audio-enabled', this.audioEnabled ? '1' : '0');
                    },

                    speakText(text, cancelPrevious = true) {
                        if (!this.audioEnabled || !window.speechSynthesis) {
                            return;
                        }

                        const normalizedText = String(text || '').trim();
                        if (!normalizedText) {
                            return;
                        }

                        this.lastAnnouncementText = normalizedText;
                        localStorage.setItem('queue-board-last-announcement', normalizedText);

                        const utterance = new SpeechSynthesisUtterance(normalizedText);
                        utterance.lang = 'id-ID';
                        utterance.rate = 0.95;
                        utterance.pitch = 1.0;
                        if (cancelPrevious) {
                            window.speechSynthesis.cancel();
                        }
                        window.speechSynthesis.speak(utterance);
                    },

                    replayCurrentCall() {
                        if (!this.current?.queue_number) {
                            return;
                        }

                        this.replayQueueCall(this.current.display_queue_number ?? this.current.queue_number, this.current.customer_name);
                    },

                    replayQueueCall(queueNumber, customerName = null, cancelPrevious = true) {
                        const label = this.queueLabel(queueNumber);
                        const nameText = String(customerName || '').trim();
                        const prefix = nameText ? `${nameText}, ` : '';
                        this.speakText(`${prefix}nomor antrian ${label}, silakan menuju kasir.`, cancelPrevious);
                    },

                    replayDoneAnnouncement(queueNumber, customerName = null, cancelPrevious = true) {
                        const label = this.queueLabel(queueNumber);
                        const nameText = String(customerName || '').trim();
                        const prefix = nameText ? `${nameText}, ` : '';
                        this.speakText(`${prefix}pesanan nomor antrian ${label} sudah selesai.`, cancelPrevious);
                    },

                    replayLastAnnouncement() {
                        if (!this.lastAnnouncementText) {
                            return;
                        }

                        this.speakText(this.lastAnnouncementText);
                    },

                    announceDoneIfNeeded() {
                        if (!this.audioEnabled || !window.speechSynthesis) {
                            if (!this.hasHydratedDoneState) {
                                for (const item of this.recentDone) {
                                    this.announcedDoneSet.add(String(item.announce_key || item.queue_number));
                                }

                                this.hasHydratedDoneState = true;
                            }

                            return;
                        }

                        if (!this.hasHydratedDoneState) {
                            for (const item of this.recentDone) {
                                this.announcedDoneSet.add(String(item.announce_key || item.queue_number));
                            }

                            this.hasHydratedDoneState = true;
                            localStorage.setItem('queue-board-announced-done', JSON.stringify(Array.from(this.announcedDoneSet).slice(-50)));
                            return;
                        }

                        const newDone = [];
                        for (const item of this.recentDone) {
                            const key = String(item.announce_key || item.queue_number);
                            if (this.announcedDoneSet.has(key)) {
                                continue;
                            }

                            newDone.push(item);
                            this.announcedDoneSet.add(key);
                        }

                        if (!newDone.length) {
                            return;
                        }

                        localStorage.setItem('queue-board-announced-done', JSON.stringify(Array.from(this.announcedDoneSet).slice(-50)));

                        let shouldCancel = true;
                        for (const doneItem of newDone.reverse()) {
                            this.replayDoneAnnouncement(doneItem.display_queue_number ?? doneItem.queue_number, doneItem.customer_name, shouldCancel);
                            shouldCancel = false;
                        }
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
