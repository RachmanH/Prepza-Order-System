<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Menu (Super Admin)') }}
            </h2>
            <p class="text-sm text-gray-500">Kelola gambar, nama, deskripsi, harga, status, dan alias menu</p>
        </div>
    </x-slot>

    <div class="py-10" x-data="{ ...menuManagement(), ...categoryManagement() }" x-init="initMenus(); initCategories()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- CATEGORIES SECTION -->
            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Manajemen Kategori</h3>
                    <p class="text-xs text-slate-500">Kelola kategori menu</p>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="openCreateCategoryModal()" :disabled="categoryBusy" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Tambah Kategori</button>
                    <p class="text-xs text-slate-500">Aksi tambah, edit, dan hapus kategori menggunakan modal.</p>
                </div>

                <p class="text-xs" :class="categoryMessageTone" x-text="categoryMessage"></p>

                <!-- Categories Table -->
                <div class="overflow-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Deskripsi</th>
                                <th class="px-3 py-2">Jumlah Menu</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="categoryLoading">
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-slate-500">Memuat kategori...</td>
                                </tr>
                            </template>

                            <template x-if="!categoryLoading && categories.length === 0">
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-slate-500">Belum ada kategori.</td>
                                </tr>
                            </template>

                            <template x-for="category in categories" :key="category.id">
                                <tr class="border-t border-slate-200">
                                    <td class="px-3 py-2 font-semibold text-slate-800" x-text="category.name"></td>
                                    <td class="px-3 py-2 text-slate-600" x-text="category.description || '-'"></td>
                                    <td class="px-3 py-2 text-slate-700">
                                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700" x-text="`${category.menus_count || 0} menu`"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'" x-text="category.is_active ? 'aktif' : 'tidak aktif'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="openEditCategoryModal(category)" :disabled="categoryBusy" class="rounded border border-slate-300 px-2 py-1 text-[11px] font-semibold text-slate-700 disabled:opacity-50">Edit</button>
                                            <button type="button" @click="openDeleteCategoryModal(category)" :disabled="categoryBusy || (category.menus_count || 0) > 0" class="rounded border border-rose-300 px-2 py-1 text-[11px] font-semibold text-rose-700 disabled:opacity-50" :title="(category.menus_count || 0) > 0 ? 'Tidak bisa hapus kategori yang masih memiliki menu' : ''">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- MENUS SECTION -->
            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Manajemen Menu</h3>
                    <p class="text-xs text-slate-500">Kelola Data Menu</p>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" @click="openCreateMenuModal()" :disabled="busy" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Tambah Menu</button>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Filter Kategori</label>
                        <select x-model="selectedCategoryFilter" class="rounded-lg border-slate-300 text-xs focus:border-cyan-500 focus:ring-cyan-500">
                            <option value="all">Semua Kategori</option>
                            <template x-for="category in categories" :key="`filter-${category.id}`">
                                <option :value="String(category.id)" x-text="category.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <p class="text-xs" :class="messageTone" x-text="message"></p>

                <div class="overflow-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Gambar</th>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Kategori</th>
                                <th class="px-3 py-2">Deskripsi</th>
                                <th class="px-3 py-2">Harga</th>
                                <th class="px-3 py-2">Alias</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="8" class="px-3 py-3 text-slate-500">Memuat menu...</td>
                                </tr>
                            </template>

                            <template x-if="!loading && filteredMenus().length === 0">
                                <tr>
                                    <td colspan="8" class="px-3 py-3 text-slate-500">Belum ada menu.</td>
                                </tr>
                            </template>

                            <template x-for="menu in filteredMenus()" :key="menu.id">
                                <tr class="border-t border-slate-200">
                                    <td class="px-3 py-2">
                                        <img x-show="menu.image_url" :src="menu.image_url" alt="Gambar menu" class="h-12 w-12 rounded-md border border-slate-200 object-cover" loading="lazy">
                                        <div x-show="!menu.image_url" class="flex h-12 w-12 items-center justify-center rounded-md border border-dashed border-slate-300 text-[10px] text-slate-400">No Img</div>
                                    </td>
                                    <td class="px-3 py-2 font-semibold text-slate-800" x-text="menu.name"></td>
                                    <td class="px-3 py-2">
                                        <template x-if="menu.category">
                                            <span class="inline-block rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-700" x-text="menu.category.name"></span>
                                        </template>
                                        <template x-if="!menu.category">
                                            <span class="text-xs text-slate-400">-</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600" x-text="menu.description || '-'" ></td>
                                    <td class="px-3 py-2 text-slate-700" x-text="formatCurrency(menu.price)"></td>
                                    <td class="px-3 py-2 text-slate-600" x-text="(menu.aliases || []).map((alias) => alias.alias).join(', ') || '-'"></td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="menu.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'" x-text="menu.is_active ? 'tersedia' : 'tidak tersedia'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="openEditMenuModal(menu)" :disabled="busy" class="rounded border border-slate-300 px-2 py-1 text-[11px] font-semibold text-slate-700 disabled:opacity-50">Edit</button>
                                            <button type="button" @click="toggleMenu(menu)" :disabled="busy" class="rounded border border-amber-300 px-2 py-1 text-[11px] font-semibold text-amber-700 disabled:opacity-50">Toggle</button>
                                            <button type="button" @click="removeMenuImage(menu)" :disabled="busy || !menu.image_url" class="rounded border border-orange-300 px-2 py-1 text-[11px] font-semibold text-orange-700 disabled:opacity-50">Hapus Gambar</button>
                                            <button type="button" @click="openDeleteMenuModal(menu)" :disabled="busy" class="rounded border border-rose-300 px-2 py-1 text-[11px] font-semibold text-rose-700 disabled:opacity-50">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Category Create/Edit Modal -->
        <div x-show="showCategoryModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" style="display: none;">
            <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900" x-text="categoryModalMode === 'edit' ? 'Edit Kategori' : 'Tambah Kategori'"></h3>
                    <button type="button" @click="closeCategoryModal()" class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Tutup</button>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <input x-model="categoryForm.name" type="text" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Nama kategori" />
                    <textarea x-model="categoryForm.description" rows="3" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Deskripsi kategori (opsional)"></textarea>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input x-model="categoryForm.is_active" type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                        Aktif
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="submitCategoryModal()" :disabled="categoryBusy" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="categoryModalMode === 'edit' ? 'Simpan Perubahan' : 'Tambah Kategori'"></button>
                    <button type="button" @click="closeCategoryModal()" :disabled="categoryBusy" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50">Batal</button>
                </div>
            </div>
        </div>

        <!-- Category Delete Modal -->
        <div x-show="showDeleteCategoryModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" style="display: none;">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:p-6 space-y-4">
                <h3 class="text-lg font-bold text-slate-900">Hapus Kategori</h3>
                <p class="text-sm text-slate-600">Yakin ingin menghapus kategori <span class="font-semibold" x-text="categoryToDelete?.name || '-'" ></span>?</p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="confirmDeleteCategory()" :disabled="categoryBusy" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 disabled:opacity-50">Ya, Hapus</button>
                    <button type="button" @click="showDeleteCategoryModal = false" :disabled="categoryBusy" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50">Batal</button>
                </div>
            </div>
        </div>

        <!-- Menu Create/Edit Modal -->
        <div x-show="showMenuModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" style="display: none;">
            <div class="w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900" x-text="menuModalMode === 'edit' ? 'Edit Menu' : 'Tambah Menu'"></h3>
                    <button type="button" @click="closeMenuModal()" class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Tutup</button>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <input x-model="form.name" type="text" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Nama menu" />
                    <select x-model="form.category_id" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                        <option value="">-- Pilih Kategori --</option>
                        <template x-for="category in categories" :key="`modal-cat-${category.id}`">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                    <input x-model="form.image_url" type="url" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="URL gambar eksternal (opsional)" />
                    <textarea x-model="form.description" rows="2" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Deskripsi singkat menu"></textarea>
                    <div>
                        <input type="file" @change="onImageSelected($event)" accept="image/png,image/jpeg,image/jpg,image/webp" class="block w-full rounded-lg border border-slate-300 bg-white text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700">
                        <p class="mt-1 text-[11px] text-slate-500">Upload gambar lokal (jpg/png/webp, max 3MB)</p>
                    </div>
                    <input x-model="form.price" type="number" min="0" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Harga" />
                    <input x-model="form.aliases" type="text" class="rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Alias (pisah koma)" />
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                            <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                            Tersedia
                        </label>
                        <label x-show="editingId" class="inline-flex items-center gap-2 text-xs text-rose-600">
                            <input x-model="form.remove_image" type="checkbox" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500" />
                            Hapus gambar saat simpan
                        </label>
                    </div>
                </div>

                <div x-show="previewImageUrl" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold text-slate-600">Preview Gambar</p>
                    <img :src="previewImageUrl" alt="Preview menu" class="mt-2 h-28 w-28 rounded-lg border border-slate-200 object-cover" loading="lazy">
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="submitMenuModal()" :disabled="busy" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="menuModalMode === 'edit' ? 'Simpan Perubahan' : 'Tambah Menu'"></button>
                    <button type="button" @click="closeMenuModal()" :disabled="busy" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50">Batal</button>
                </div>
            </div>
        </div>

        <!-- Menu Delete Modal -->
        <div x-show="showDeleteMenuModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" style="display: none;">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:p-6 space-y-4">
                <h3 class="text-lg font-bold text-slate-900">Hapus Menu</h3>
                <p class="text-sm text-slate-600">Yakin ingin menghapus menu <span class="font-semibold" x-text="menuToDelete?.name || '-'" ></span>?</p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="confirmDeleteMenu()" :disabled="busy" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 disabled:opacity-50">Ya, Hapus</button>
                    <button type="button" @click="showDeleteMenuModal = false" :disabled="busy" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50">Batal</button>
                </div>
            </div>
        </div>

        <script>
            function menuManagement() {
                return {
                    menus: [],
                    selectedCategoryFilter: 'all',
                    loading: false,
                    busy: false,
                    editingId: null,
                    showMenuModal: false,
                    menuModalMode: 'create',
                    showDeleteMenuModal: false,
                    menuToDelete: null,
                    message: '',
                    messageTone: 'text-slate-600',
                    previewImageUrl: '',
                    form: {
                        name: '',
                        description: '',
                        image_url: '',
                        image_file: null,
                        remove_image: false,
                        price: '',
                        aliases: '',
                        category_id: '',
                        is_active: true,
                    },

                    initMenus() {
                        this.fetchMenus();
                    },

                    async fetchMenus() {
                        this.loading = true;
                        try {
                            const response = await axios.get('/api/admin/menus');
                            this.menus = response.data.data || [];
                        } catch (error) {
                            this.message = this.resolveErrorMessage(error, 'Gagal memuat data menu.');
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.loading = false;
                        }
                    },

                    filteredMenus() {
                        if (String(this.selectedCategoryFilter) === 'all') {
                            return this.menus;
                        }

                        return this.menus.filter((menu) => String(menu.category_id || '') === String(this.selectedCategoryFilter));
                    },

                    aliasList() {
                        return String(this.form.aliases || '')
                            .split(',')
                            .map((v) => v.trim())
                            .filter((v) => v.length > 0);
                    },

                    payload() {
                        const data = new FormData();
                        data.append('name', String(this.form.name || '').trim());
                        data.append('description', String(this.form.description || '').trim());
                        data.append('image_url', String(this.form.image_url || '').trim());
                        data.append('price', Number(this.form.price || 0));
                        data.append('is_active', this.form.is_active ? '1' : '0');
                        data.append('remove_image', this.form.remove_image ? '1' : '0');
                        if (this.form.category_id) {
                            data.append('category_id', this.form.category_id);
                        }

                        this.aliasList().forEach((alias) => {
                            data.append('aliases[]', alias);
                        });

                        if (this.form.image_file) {
                            data.append('image_file', this.form.image_file);
                        }

                        return data;
                    },

                    async submitForm() {
                        if (!this.form.name.trim()) {
                            this.message = 'Nama menu wajib diisi.';
                            this.messageTone = 'text-rose-600';
                            return false;
                        }

                        if (!this.form.category_id) {
                            this.message = 'Kategori menu wajib dipilih.';
                            this.messageTone = 'text-rose-600';
                            return false;
                        }

                        this.busy = true;
                        let success = false;
                        try {
                            if (this.editingId) {
                                const data = this.payload();
                                data.append('_method', 'PATCH');
                                const response = await axios.post(`/api/admin/menus/${this.editingId}`, data, {
                                    headers: { 'Content-Type': 'multipart/form-data' },
                                });
                                this.message = response.data.message || 'Menu berhasil diperbarui.';
                            } else {
                                const response = await axios.post('/api/admin/menus', this.payload(), {
                                    headers: { 'Content-Type': 'multipart/form-data' },
                                });
                                this.message = response.data.message || 'Menu berhasil ditambahkan.';
                            }

                            this.messageTone = 'text-emerald-600';
                            this.resetForm();
                            await this.fetchMenus();
                            success = true;
                        } catch (error) {
                            this.message = this.resolveErrorMessage(error, 'Gagal menyimpan menu.');
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }

                        return success;
                    },

                    async submitMenuModal() {
                        const success = await this.submitForm();
                        if (success) {
                            this.showMenuModal = false;
                        }
                    },

                    startEdit(menu) {
                        this.editingId = menu.id;
                        this.form.name = menu.name || '';
                        this.form.description = menu.description || '';
                        this.form.image_url = menu.image_external_url || '';
                        this.form.image_file = null;
                        this.form.remove_image = false;
                        this.form.price = Number(menu.price || 0);
                        this.form.aliases = (menu.aliases || []).map((alias) => alias.alias).join(', ');
                        this.form.category_id = menu.category_id || '';
                        this.form.is_active = Boolean(menu.is_active);
                        this.previewImageUrl = menu.image_url || '';
                        this.message = `Mode edit: ${menu.name}`;
                        this.messageTone = 'text-slate-600';
                    },

                    openCreateMenuModal() {
                        this.menuModalMode = 'create';
                        this.resetForm();
                        this.showMenuModal = true;
                    },

                    openEditMenuModal(menu) {
                        this.menuModalMode = 'edit';
                        this.startEdit(menu);
                        this.showMenuModal = true;
                    },

                    closeMenuModal() {
                        this.showMenuModal = false;
                        this.resetForm();
                    },

                    openDeleteMenuModal(menu) {
                        this.menuToDelete = menu;
                        this.showDeleteMenuModal = true;
                    },

                    async confirmDeleteMenu() {
                        if (!this.menuToDelete) {
                            return;
                        }

                        await this.deleteMenu(this.menuToDelete, false);
                        if (this.messageTone === 'text-emerald-600') {
                            this.showDeleteMenuModal = false;
                            this.menuToDelete = null;
                        }
                    },

                    async toggleMenu(menu) {
                        this.busy = true;
                        try {
                            const response = await axios.patch(`/api/admin/menus/${menu.id}/toggle`);
                            this.message = response.data.message || 'Status menu berhasil diubah.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchMenus();
                        } catch (error) {
                            this.message = this.resolveErrorMessage(error, 'Gagal mengubah status menu.');
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    async removeMenuImage(menu) {
                        if (!confirm(`Hapus gambar untuk menu ${menu.name}?`)) {
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.delete(`/api/admin/menus/${menu.id}/image`);
                            this.message = response.data.message || 'Gambar menu berhasil dihapus.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchMenus();
                        } catch (error) {
                            this.message = this.resolveErrorMessage(error, 'Gagal menghapus gambar menu.');
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    async deleteMenu(menu, askConfirm = true) {
                        if (askConfirm && !confirm(`Hapus menu ${menu.name}?`)) {
                            return;
                        }

                        this.busy = true;
                        try {
                            const response = await axios.delete(`/api/admin/menus/${menu.id}`);
                            this.message = response.data.message || 'Menu berhasil dihapus.';
                            this.messageTone = 'text-emerald-600';
                            await this.fetchMenus();
                        } catch (error) {
                            this.message = this.resolveErrorMessage(error, 'Gagal menghapus menu.');
                            this.messageTone = 'text-rose-600';
                        } finally {
                            this.busy = false;
                        }
                    },

                    onImageSelected(event) {
                        const file = event?.target?.files?.[0] || null;
                        this.form.image_file = file;

                        if (file) {
                            this.previewImageUrl = URL.createObjectURL(file);
                            this.form.remove_image = false;
                        } else if (!this.editingId) {
                            this.previewImageUrl = String(this.form.image_url || '').trim();
                        }
                    },

                    resolveErrorMessage(error, fallback) {
                        const data = error?.response?.data || {};
                        const status = error?.response?.status;

                        if (typeof data.message === 'string' && data.message.trim()) {
                            return data.message;
                        }

                        const firstValidationError = data?.errors
                            ? Object.values(data.errors)[0]?.[0]
                            : null;

                        if (firstValidationError) {
                            return String(firstValidationError);
                        }

                        if (status === 403) {
                            return 'Akses ditolak. Pastikan akun Anda adalah super admin.';
                        }

                        if (status === 401) {
                            return 'Sesi login berakhir. Silakan login ulang.';
                        }

                        return fallback;
                    },

                    resetForm() {
                        this.editingId = null;
                        this.previewImageUrl = '';
                        this.form = {
                            name: '',
                            description: '',
                            image_url: '',
                            image_file: null,
                            remove_image: false,
                            price: '',
                            aliases: '',
                            category_id: '',
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

            function categoryManagement() {
                return {
                    categories: [],
                    categoryLoading: false,
                    categoryBusy: false,
                    editingCategoryId: null,
                    showCategoryModal: false,
                    categoryModalMode: 'create',
                    showDeleteCategoryModal: false,
                    categoryToDelete: null,
                    categoryMessage: '',
                    categoryMessageTone: 'text-slate-600',
                    categoryForm: {
                        name: '',
                        description: '',
                        is_active: true,
                    },

                    initCategories() {
                        this.fetchCategories();
                    },

                    async fetchCategories() {
                        this.categoryLoading = true;
                        try {
                            const response = await axios.get('/api/admin/categories');
                            this.categories = response.data.data || [];
                        } catch (error) {
                            this.categoryMessage = this.resolveCategoryErrorMessage(error, 'Gagal memuat data kategori.');
                            this.categoryMessageTone = 'text-rose-600';
                        } finally {
                            this.categoryLoading = false;
                        }
                    },

                    async submitCategory() {
                        if (!this.categoryForm.name.trim()) {
                            this.categoryMessage = 'Nama kategori wajib diisi.';
                            this.categoryMessageTone = 'text-rose-600';
                            return false;
                        }

                        this.categoryBusy = true;
                        let success = false;
                        try {
                            const payload = {
                                name: String(this.categoryForm.name || '').trim(),
                                description: String(this.categoryForm.description || '').trim(),
                                is_active: this.categoryForm.is_active ? true : false,
                            };

                            if (this.editingCategoryId) {
                                const response = await axios.patch(`/api/admin/categories/${this.editingCategoryId}`, payload);
                                this.categoryMessage = response.data.message || 'Kategori berhasil diperbarui.';
                            } else {
                                const response = await axios.post('/api/admin/categories', payload);
                                this.categoryMessage = response.data.message || 'Kategori berhasil ditambahkan.';
                            }

                            this.categoryMessageTone = 'text-emerald-600';
                            this.resetCategoryForm();
                            await this.fetchCategories();
                            success = true;
                        } catch (error) {
                            this.categoryMessage = this.resolveCategoryErrorMessage(error, 'Gagal menyimpan kategori.');
                            this.categoryMessageTone = 'text-rose-600';
                        } finally {
                            this.categoryBusy = false;
                        }

                        return success;
                    },

                    startEditCategory(category) {
                        this.editingCategoryId = category.id;
                        this.categoryForm.name = category.name || '';
                        this.categoryForm.description = category.description || '';
                        this.categoryForm.is_active = Boolean(category.is_active);
                        this.categoryMessage = `Mode edit: ${category.name}`;
                        this.categoryMessageTone = 'text-slate-600';
                    },

                    openCreateCategoryModal() {
                        this.categoryModalMode = 'create';
                        this.resetCategoryForm();
                        this.showCategoryModal = true;
                    },

                    openEditCategoryModal(category) {
                        this.categoryModalMode = 'edit';
                        this.startEditCategory(category);
                        this.showCategoryModal = true;
                    },

                    closeCategoryModal() {
                        this.showCategoryModal = false;
                        this.resetCategoryForm();
                    },

                    async submitCategoryModal() {
                        const success = await this.submitCategory();
                        if (success) {
                            this.showCategoryModal = false;
                        }
                    },

                    openDeleteCategoryModal(category) {
                        this.categoryToDelete = category;
                        this.showDeleteCategoryModal = true;
                    },

                    async confirmDeleteCategory() {
                        if (!this.categoryToDelete) {
                            return;
                        }

                        await this.deleteCategory(this.categoryToDelete, false);
                        if (this.categoryMessageTone === 'text-emerald-600') {
                            this.showDeleteCategoryModal = false;
                            this.categoryToDelete = null;
                        }
                    },

                    async deleteCategory(category, askConfirm = true) {
                        if (askConfirm && !confirm(`Hapus kategori ${category.name}?`)) {
                            return;
                        }

                        this.categoryBusy = true;
                        try {
                            const response = await axios.delete(`/api/admin/categories/${category.id}`);
                            this.categoryMessage = response.data.message || 'Kategori berhasil dihapus.';
                            this.categoryMessageTone = 'text-emerald-600';
                            await this.fetchCategories();
                        } catch (error) {
                            this.categoryMessage = this.resolveCategoryErrorMessage(error, 'Gagal menghapus kategori.');
                            this.categoryMessageTone = 'text-rose-600';
                        } finally {
                            this.categoryBusy = false;
                        }
                    },

                    resetCategoryForm() {
                        this.editingCategoryId = null;
                        this.categoryForm = {
                            name: '',
                            description: '',
                            is_active: true,
                        };
                    },

                    resolveCategoryErrorMessage(error, fallback) {
                        const data = error?.response?.data || {};
                        const status = error?.response?.status;

                        if (typeof data.message === 'string' && data.message.trim()) {
                            return data.message;
                        }

                        const firstValidationError = data?.errors
                            ? Object.values(data.errors)[0]?.[0]
                            : null;

                        if (firstValidationError) {
                            return String(firstValidationError);
                        }

                        if (status === 403) {
                            return 'Akses ditolak. Pastikan akun Anda adalah super admin.';
                        }

                        if (status === 401) {
                            return 'Sesi login berakhir. Silakan login ulang.';
                        }

                        return fallback;
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
