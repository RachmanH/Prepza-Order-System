<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Menu (Super Admin)') }}
            </h2>
            <p class="text-sm text-gray-500">Kelola menu, harga, status aktif, dan alias</p>
        </div>
    </x-slot>

    <div class="py-10" x-data="menuManagement()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <input x-model="form.name" type="text" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Nama menu" />
                    <input x-model="form.price" type="number" min="0" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Harga" />
                    <input x-model="form.aliases" type="text" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Alias (pisah koma)" />
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                            <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                            Aktif
                        </label>
                        <button type="button" @click="submitForm()" :disabled="busy" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="editingId ? 'Update Menu' : 'Tambah Menu'"></button>
                        <button type="button" x-show="editingId" @click="resetForm()" :disabled="busy" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50">Batal Edit</button>
                    </div>
                </div>

                <p class="text-xs" :class="messageTone" x-text="message"></p>

                <div class="overflow-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Harga</th>
                                <th class="px-3 py-2">Alias</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-slate-500">Memuat menu...</td>
                                </tr>
                            </template>

                            <template x-if="!loading && menus.length === 0">
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-slate-500">Belum ada menu.</td>
                                </tr>
                            </template>

                            <template x-for="menu in menus" :key="menu.id">
                                <tr class="border-t border-slate-200">
                                    <td class="px-3 py-2 font-semibold text-slate-800" x-text="menu.name"></td>
                                    <td class="px-3 py-2 text-slate-700" x-text="formatCurrency(menu.price)"></td>
                                    <td class="px-3 py-2 text-slate-600" x-text="(menu.aliases || []).map((alias) => alias.alias).join(', ') || '-'"></td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="menu.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'" x-text="menu.is_active ? 'active' : 'inactive'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="startEdit(menu)" :disabled="busy" class="rounded border border-slate-300 px-2 py-1 text-[11px] font-semibold text-slate-700 disabled:opacity-50">Edit</button>
                                            <button type="button" @click="toggleMenu(menu)" :disabled="busy" class="rounded border border-amber-300 px-2 py-1 text-[11px] font-semibold text-amber-700 disabled:opacity-50">Toggle</button>
                                            <button type="button" @click="deleteMenu(menu)" :disabled="busy" class="rounded border border-rose-300 px-2 py-1 text-[11px] font-semibold text-rose-700 disabled:opacity-50">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <script>
            function menuManagement() {
                return {
                    menus: [],
                    loading: false,
                    busy: false,
                    editingId: null,
                    message: '',
                    messageTone: 'text-slate-600',
                    form: {
                        name: '',
                        price: '',
                        aliases: '',
                        is_active: true,
                    },

                    init() {
                        this.fetchMenus();
                    },

                    async fetchMenus() {
                        this.loading = true;
                        try {
                            const response = await axios.get('/api/admin/menus');
                            this.menus = response.data.data || [];
                        } catch (error) {
                            this.message = 'Gagal memuat data menu.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.loading = false;
                        }
                    },

                    aliasList() {
                        return String(this.form.aliases || '')
                            .split(',')
                            .map((v) => v.trim())
                            .filter((v) => v.length > 0);
                    },

                    payload() {
                        return {
                            name: this.form.name,
                            price: Number(this.form.price || 0),
                            is_active: Boolean(this.form.is_active),
                            aliases: this.aliasList(),
                        };
                    },

                    async submitForm() {
                        if (!this.form.name.trim()) {
                            this.message = 'Nama menu wajib diisi.';
                            this.messageTone = 'text-rose-600';
                            return;
                        }

                        this.busy = true;
                        try {
                            if (this.editingId) {
                                const response = await axios.patch(`/api/admin/menus/${this.editingId}`, this.payload());
                                this.message = response.data.message || 'Menu berhasil diperbarui.';
                            } else {
                                const response = await axios.post('/api/admin/menus', this.payload());
                                this.message = response.data.message || 'Menu berhasil ditambahkan.';
                            }

                            this.messageTone = 'text-emerald-600';
                            this.resetForm();
                            await this.fetchMenus();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal menyimpan menu.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    startEdit(menu) {
                        this.editingId = menu.id;
                        this.form.name = menu.name || '';
                        this.form.price = Number(menu.price || 0);
                        this.form.aliases = (menu.aliases || []).map((alias) => alias.alias).join(', ');
                        this.form.is_active = Boolean(menu.is_active);
                        this.message = `Mode edit: ${menu.name}`;
                        this.messageTone = 'text-slate-600';
                    },

                    async toggleMenu(menu) {
                        this.busy = true;
                        try {
                            const response = await axios.patch(`/api/admin/menus/${menu.id}/toggle`);
                            this.message = response.data.message || 'Status menu berhasil diubah.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchMenus();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal mengubah status menu.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    async deleteMenu(menu) {
                        if (!confirm(`Hapus menu ${menu.name}?`)) {
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.delete(`/api/admin/menus/${menu.id}`);
                            this.message = response.data.message || 'Menu berhasil dihapus.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchMenus();
                        } catch (error) {
                            const data = error?.response?.data || {};
                            this.message = data.message || 'Gagal menghapus menu.';
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    resetForm() {
                        this.editingId = null;
                        this.form = {
                            name: '',
                            price: '',
                            aliases: '',
                            is_active: true,
                        };
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
