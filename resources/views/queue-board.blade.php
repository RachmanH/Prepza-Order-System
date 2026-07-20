<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Display Antrian</h2>
                <p class="text-sm text-slate-500 mt-0.5">Realtime queue board — diperbarui otomatis setiap 2.5 detik</p>
            </div>
            <div class="text-right" x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)">
                <p class="text-xs text-slate-400">Waktu Server</p>
                <p class="text-sm font-semibold text-slate-700" x-text="now.toLocaleTimeString('id-ID')"></p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="queueBoard()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-5">

            <!-- {{-- Status Bar --}}
            <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-white px-5 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                              :class="boardState === 'online' ? 'bg-emerald-400' : boardState === 'standby' ? 'bg-amber-400' : 'bg-rose-400'"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full"
                              :class="boardState === 'online' ? 'bg-emerald-500' : boardState === 'standby' ? 'bg-amber-500' : 'bg-rose-500'"></span>
                    </span>
                    <span class="text-sm font-semibold capitalize text-slate-700" x-text="boardState"></span>
                </div>
                <p class="text-xs text-slate-400">Sinkron: <span class="font-medium text-slate-600" x-text="lastSyncedAt"></span></p>
            </div> -->

            {{-- Main Grid --}}
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

                {{-- LEFT: Queue Numbers --}}
                <div class="lg:col-span-7 space-y-5">

                    {{-- Active Queue — Big Display --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-md">
                        <div class="border-b border-slate-100 px-5 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sedang Dilayani</p>
                        </div>
                        <div class="flex flex-col items-center justify-center bg-gradient-to-br from-sky-600 to-sky-500 px-6 py-10">
                            <p class="text-[5rem] font-black leading-none tracking-wider text-white queue-pop"
                               x-text="current ? queueLabel(current.display_queue_number ?? current.queue_number) : '—'"
                               :key="current?.queue_number"></p>
                            <div class="mt-4 text-center" x-show="current">
                                <p class="text-base font-semibold text-sky-100" x-text="current?.customer_name || 'Pelanggan'"></p>
                                <p class="mt-1 font-mono text-xs text-sky-200" x-text="current?.order_code || ''"></p>
                            </div>
                            <p class="mt-3 text-sm text-sky-200" x-show="!current">Belum ada pesanan aktif</p>
                        </div>
                    </div>

                    {{-- Upcoming Queue --}}
                    <div class="rounded-2xl border border-slate-100 bg-white shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Antrian Berikutnya</p>
                            <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-bold text-sky-700"
                                  x-text="`${upcoming.length} antrian`"></span>
                        </div>
                        <div class="p-4">
                            <template x-if="upcoming.length === 0">
                                <div class="flex h-20 items-center justify-center rounded-xl border-2 border-dashed border-slate-200">
                                    <p class="text-sm text-slate-400">Belum ada antrian menunggu</p>
                                </div>
                            </template>
                            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4">
                                <template x-for="(item, idx) in upcoming" :key="item.order_id">
                                                <div class="flex flex-col items-center rounded-xl border p-3 text-center"
                                                      :class="item.status === 'processing' ? 'border-sky-200 bg-sky-50/70' : 'border-slate-200 bg-slate-50'">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                                           x-text="`#${idx + 1}`"></p>
                                                     <span class="mt-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                             :class="item.status === 'processing' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700'"
                                                             x-text="item.status === 'processing' ? 'Sedang Diproses' : 'Menunggu'"></span>
                                        <p class="mt-1 text-3xl font-black text-slate-800"
                                           x-text="queueLabel(item.display_queue_number ?? item.queue_number)"></p>
                                        <p class="mt-1 truncate text-[11px] text-slate-500 w-full"
                                           x-text="item.customer_name || 'Pelanggan'"></p>
                                        <button type="button"
                                                @click="replayQueueCall(item.display_queue_number ?? item.queue_number, item.customer_name)"
                                                class="mt-2 w-full rounded-lg border border-sky-200 bg-white px-2 py-1 text-[10px] font-semibold text-sky-700 hover:bg-sky-50 disabled:opacity-40 transition">
                                            Replay
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Row --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm text-center">
                            <p class="text-xs font-medium text-slate-500">Dilayani</p>
                            <p class="mt-1 text-2xl font-bold text-sky-600"
                               x-text="current ? queueLabel(current.display_queue_number ?? current.queue_number) : '—'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm text-center">
                            <p class="text-xs font-medium text-slate-500">Menunggu</p>
                            <p class="mt-1 text-2xl font-bold text-amber-600" x-text="upcoming.length"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm text-center">
                            <p class="text-xs font-medium text-slate-500">Selesai</p>
                            <p class="mt-1 text-2xl font-bold text-emerald-600" x-text="recentDone.length"></p>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Trends Carousel + Audio --}}
                <div class="lg:col-span-5 space-y-5">

                    {{-- Trends Carousel: Male --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-md"
                         x-data="trendCarousel('male')"
                         @trends-updated.window="setSlides($event.detail || {})">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Rekomendasi Menu Pria</p>
                            <div class="flex items-center gap-2" x-show="slides.length > 1">
                                <button @click="prev()" class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                    </svg>
                                </button>
                                <span class="text-xs text-slate-400" x-text="`${current + 1}/${slides.length}`"></span>
                                <button @click="next()" class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <template x-if="slides.length > 0">
                            <div>
                                <div class="relative overflow-hidden">
                                    <img :src="slides[current].image_url"
                                         :alt="slides[current].title"
                                         class="h-48 w-full object-cover object-center transition-opacity duration-300 lg:h-52"
                                         :key="slides[current].id">
                                    <div class="absolute left-3 top-3">
                                        <span class="rounded-full bg-blue-500 px-2.5 py-1 text-xs font-bold text-white shadow-sm">♂ Pria</span>
                                    </div>
                                    <template x-if="slides[current].score !== null">
                                        <div class="absolute right-3 top-3">
                                            <span class="rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-sm"
                                                  x-text="`🔥 ${slides[current].score}`"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm font-bold text-slate-900" x-text="slides[current].title"></p>
                                    <p class="mt-1 text-xs text-slate-500 leading-relaxed" x-text="slides[current].caption || ''"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="slides.length === 0">
                            <div class="flex h-28 items-center justify-center">
                                <p class="text-sm text-slate-400">Belum ada rekomendasi pria</p>
                            </div>
                        </template>
                    </div>

                    {{-- Trends Carousel: Female --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-md"
                         x-data="trendCarousel('female')"
                         @trends-updated.window="setSlides($event.detail || {})">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Rekomendasi Menu Wanita</p>
                            <div class="flex items-center gap-2" x-show="slides.length > 1">
                                <button @click="prev()" class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                    </svg>
                                </button>
                                <span class="text-xs text-slate-400" x-text="`${current + 1}/${slides.length}`"></span>
                                <button @click="next()" class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <template x-if="slides.length > 0">
                            <div>
                                <div class="relative overflow-hidden">
                                    <img :src="slides[current].image_url"
                                         :alt="slides[current].title"
                                         class="h-48 w-full object-cover object-center transition-opacity duration-300 lg:h-52"
                                         :key="slides[current].id">
                                    <div class="absolute left-3 top-3">
                                        <span class="rounded-full bg-pink-500 px-2.5 py-1 text-xs font-bold text-white shadow-sm">♀ Wanita</span>
                                    </div>
                                    <template x-if="slides[current].score !== null">
                                        <div class="absolute right-3 top-3">
                                            <span class="rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-sm"
                                                  x-text="`🔥 ${slides[current].score}`"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm font-bold text-slate-900" x-text="slides[current].title"></p>
                                    <p class="mt-1 text-xs text-slate-500 leading-relaxed" x-text="slides[current].caption || ''"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="slides.length === 0">
                            <div class="flex h-28 items-center justify-center">
                                <p class="text-sm text-slate-400">Belum ada rekomendasi wanita</p>
                            </div>
                        </template>
                    </div>

                </div>
            </div>

            {{-- Recent Done --}}
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pesanan Selesai</p>
                    <span class="text-[11px] text-slate-400">max 8</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-if="recentDone.length === 0">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">Belum ada</span>
                    </template>
                    <template x-for="item in recentDone.slice(0, 8)" :key="`done-${item.order_id}`">
                        <button type="button"
                                @click="replayDoneAnnouncement(item.display_queue_number ?? item.queue_number, item.customer_name)"
                                class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-200 disabled:opacity-40 transition"
                                x-text="queueLabel(item.display_queue_number ?? item.queue_number)">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-white px-5 py-3 text-xs text-slate-400 shadow-sm">
                <p class="font-medium text-slate-600">Prepza — Queue Board</p>
                <p>Sinkron terakhir: <span class="font-semibold text-slate-600" x-text="lastSyncedAt"></span></p>
            </div>
        </div>

        <style>
            .queue-pop { animation: queue-pop 0.45s cubic-bezier(0.2, 0.8, 0.2, 1); }
            @keyframes queue-pop {
                0%   { transform: scale(0.85); opacity: 0.3; }
                100% { transform: scale(1);    opacity: 1;   }
            }
        </style>

        <script>
            function trendCarousel(genderKey = 'male') {
                return {
                    slides: [],
                    current: 0,
                    timer: null,

                    init() {
                        this.startAutoPlay();
                    },

                    setSlides(payload) {
                        const isLegacyArray = Array.isArray(payload);
                        const trends = isLegacyArray ? payload : (payload.trends || []);
                        const trendsByGender = !isLegacyArray && payload.trendsByGender && typeof payload.trendsByGender === 'object'
                            ? payload.trendsByGender
                            : {};

                        const selected = trendsByGender[genderKey] || trends;
                        this.slides = Array.isArray(selected) ? selected : [];
                        if (this.current >= this.slides.length) {
                            this.current = 0;
                        }
                    },

                    next() {
                        this.current = (this.current + 1) % Math.max(1, this.slides.length);
                        this.restartAutoPlay();
                    },

                    prev() {
                        this.current = (this.current - 1 + Math.max(1, this.slides.length)) % Math.max(1, this.slides.length);
                        this.restartAutoPlay();
                    },

                    startAutoPlay() {
                        this.timer = setInterval(() => {
                            if (this.slides.length > 1) {
                                this.current = (this.current + 1) % this.slides.length;
                            }
                        }, 4000);
                    },

                    restartAutoPlay() {
                        clearInterval(this.timer);
                        this.startAutoPlay();
                    },
                };
            }

            function queueBoard() {
                return {
                    current: null,
                    upcoming: [],
                    recentDone: [],
                    __trends: [],          // shared with trendCarousel via $root
                    __trendsByGender: {
                        all: [],
                        male: [],
                        female: [],
                    },
                    boardState: 'connecting',
                    lastSyncedAt: '-',
                    lastAnnouncementText: '',
                    announcedDoneSet: new Set(),
                    hasHydratedDoneState: false,

                    init() {
                        this.lastAnnouncementText = localStorage.getItem('queue-board-last-announcement') || '';

                        try {
                            const raw = localStorage.getItem('queue-board-announced-done') || '[]';
                            JSON.parse(raw).forEach((v) => this.announcedDoneSet.add(String(v)));
                        } catch {
                            this.announcedDoneSet = new Set();
                        }

                        this.fetchBoard();
                        setInterval(() => this.fetchBoard(), 2500);
                    },

                    async fetchBoard() {
                        try {
                            const res  = await axios.get('/api/queue/board');
                            const data = res.data?.data || {};

                            this.current    = data.current    || null;
                            this.upcoming   = data.upcoming   || [];
                            this.recentDone = data.recent_done || [];
                            this.__trends   = data.trends     || [];
                            this.__trendsByGender = data.trends_by_gender || { all: [], male: [], female: [] };
                            const trendPayload = {
                                trends: this.__trends,
                                trendsByGender: this.__trendsByGender,
                            };

                            this.$dispatch('trends-updated', trendPayload);
                            window.dispatchEvent(new CustomEvent('trends-updated', { detail: trendPayload }));

                            this.lastSyncedAt = new Date().toLocaleTimeString('id-ID');
                            this.boardState   = this.current ? 'online' : 'standby';
                            this.announceDoneIfNeeded();
                        } catch {
                            this.boardState = 'offline';
                        }
                    },

                    queueLabel(value) {
                        if (value === null || value === undefined) return '-';
                        const n = Number(value);
                        return Number.isNaN(n) ? String(value) : `A${n}`;
                    },

                    speakText(text, cancelPrevious = true) {
                        if (!window.speechSynthesis) return;
                        const t = String(text || '').trim();
                        if (!t) return;
                        this.lastAnnouncementText = t;
                        localStorage.setItem('queue-board-last-announcement', t);
                        const u = new SpeechSynthesisUtterance(t);
                        u.lang  = 'id-ID';
                        u.rate  = 0.95;
                        u.pitch = 1.0;
                        if (cancelPrevious) window.speechSynthesis.cancel();
                        window.speechSynthesis.speak(u);
                    },

                    replayQueueCall(queueNumber, customerName = null, cancelPrevious = true) {
                        const label  = this.queueLabel(queueNumber);
                        const prefix = String(customerName || '').trim();
                        this.speakText(`${prefix ? prefix + ', ' : ''}nomor antrian ${label}, silakan menuju kasir.`, cancelPrevious);
                    },

                    replayDoneAnnouncement(queueNumber, customerName = null, cancelPrevious = true) {
                        const label  = this.queueLabel(queueNumber);
                        const prefix = String(customerName || '').trim();
                        this.speakText(`${prefix ? prefix + ', ' : ''}pesanan nomor antrian ${label} sudah selesai.`, cancelPrevious);
                    },

                    announceDoneIfNeeded() {
                        if (!window.speechSynthesis) {
                            if (!this.hasHydratedDoneState) {
                                this.recentDone.forEach((i) => this.announcedDoneSet.add(String(i.announce_key || i.queue_number)));
                                this.hasHydratedDoneState = true;
                            }
                            return;
                        }

                        if (!this.hasHydratedDoneState) {
                            this.recentDone.forEach((i) => this.announcedDoneSet.add(String(i.announce_key || i.queue_number)));
                            this.hasHydratedDoneState = true;
                            localStorage.setItem('queue-board-announced-done', JSON.stringify([...this.announcedDoneSet].slice(-50)));
                            return;
                        }

                        const newDone = [];
                        for (const item of this.recentDone) {
                            const key = String(item.announce_key || item.queue_number);
                            if (!this.announcedDoneSet.has(key)) {
                                newDone.push(item);
                                this.announcedDoneSet.add(key);
                            }
                        }

                        if (!newDone.length) return;

                        localStorage.setItem('queue-board-announced-done', JSON.stringify([...this.announcedDoneSet].slice(-50)));
                        let cancel = true;
                        for (const item of newDone.reverse()) {
                            this.replayDoneAnnouncement(item.display_queue_number ?? item.queue_number, item.customer_name, cancel);
                            cancel = false;
                        }
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
