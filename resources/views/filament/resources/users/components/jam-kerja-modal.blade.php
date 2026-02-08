<div
    x-data="{
        isLoading: false,
        isLoadingSchedule: false,
        isSaving: false,
        items: [],
        selected: [],
        currentDay: null,
        currentMonth: null,
        currentYear: null,
        currentUserId: null,
        error: null,
        fetchScheduleError: null,
        saveSuccess: null,
        saveError: null,
        rawSelections: $wire.entangle('mountedTableActionData.jam_kerja_custom_selections'),
        selections: {},

        init() {
            console.log('Jam Kerja Modal Initialized (Footer Version)');

            this.$watch('rawSelections', (value) => {
                try {
                    this.selections = typeof value === 'string' ? JSON.parse(value) : (value || {});
                } catch (e) {
                    this.selections = {};
                }
            });

            try {
                this.selections = typeof this.rawSelections === 'string' ? JSON.parse(this.rawSelections) : (this.rawSelections || {});
            } catch (e) {
                this.selections = {};
            }
        },

        openDialog(day, month, year, userId) {
            console.log('openDialog executed:', day, month, year, userId);
            this.currentDay = day;
            this.currentMonth = month;
            this.currentYear = year;
            this.currentUserId = userId;
            this.saveSuccess = null;
            this.saveError = null;
            this.fetchScheduleError = null;

            this.loadItems();
            this.loadSchedule();
            
            // Show modal using ref
            if (this.$refs.jamKerjaDialog) {
                this.$refs.jamKerjaDialog.showModal();
            } else {
                console.error('Dialog ref not found!');
            }
        },

        async loadSchedule() {
             this.isLoadingSchedule = true;
             this.fetchScheduleError = null;
             this.selected = []; 

             try {
                const params = new URLSearchParams({
                    user_id: this.currentUserId,
                    day: this.currentDay,
                    month: this.currentMonth,
                    year: this.currentYear
                });
                const response = await fetch(`/admin/ajax/get-work-schedule?${params.toString()}`);
                if (!response.ok) throw new Error('Gagal mengambil jadwal');
                const result = await response.json();
                if (result.success) {
                    this.selected = result.data;
                } else {
                    throw new Error(result.message);
                }
             } catch (err) {
                 this.fetchScheduleError = err.message;
                 console.error('Error fetching schedule:', err);
                 // Fallback to local entangled state if DB fetch fails
                 this.selected = this.selections[this.currentDay] || [];
             } finally {
                 this.isLoadingSchedule = false;
             }
        },

        close() {
            if (this.$refs.jamKerjaDialog && this.$refs.jamKerjaDialog.open) {
                this.$refs.jamKerjaDialog.close();
            }
        },

        async loadItems() {
            if (this.items.length > 0) return;

            this.isLoading = true;
            this.error = null;

            try {
                const response = await fetch('/admin/ajax/jam-kerjas', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) throw new Error('Gagal mengambil data');
                this.items = await response.json();
                console.log('Items loaded:', this.items);
            } catch (err) {
                this.error = err.message;
                console.error('Load error:', err);
            } finally {
                this.isLoading = false;
            }
        },

        async save() {
            this.isSaving = true;
            this.saveError = null;
            this.saveSuccess = null;

            if (!this.currentUserId || this.currentUserId === '0') {
                this.saveError = 'User belum tersimpan. Silakan simpan data user utama terlebih dahulu.';
                this.isSaving = false;
                return;
            }

            // Update local entangled state for consistency
            this.selections[this.currentDay] = [...this.selected];
            this.rawSelections = JSON.stringify(this.selections);

            try {
                const response = await fetch('/admin/ajax/save-work-schedule', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    },
                    body: JSON.stringify({
                        user_id: this.currentUserId,
                        day: this.currentDay,
                        month: this.currentMonth,
                        year: this.currentYear,
                        jam_kerja_ids: this.selected
                    })
                });

                const data = await response.json();

                if (!response.ok) throw new Error(data.message || 'Gagal menyimpan data');

                this.saveSuccess = data.message || 'Data jadwal kerja berhasil disimpan';

                // Auto close after delay (without reloading page)
                setTimeout(() => {
                    this.close();
                }, 1500);

            } catch (err) {
                this.saveError = err.message || 'Terjadi kesalahan saat menyimpan data';
            } finally {
                this.isSaving = false;
            }
        }
    }"
    @open-jam-kerja-dialog.window="openDialog($event.detail.day, $event.detail.month, $event.detail.year, $event.detail.userId)"
    wire:ignore
>
    <template x-teleport="body">
        <dialog
            x-ref="jamKerjaDialog"
            @click.self="close()"
            class="backdrop:bg-black/50"
            style="
                width: 100%;
                max-width: 42rem;
                max-height: 90vh;
                margin: 0;
                border: none;
                border-radius: 12px;
                padding: 0;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                background-color: white;
                overflow: hidden;
                position: fixed;
                z-index: 100000;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            "
        >
            <div style="display: flex; flex-direction: column; height: 100%; max-height: 90vh;">
                <!-- Header -->
                <div style="padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background-color: #f9fafb;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">
                        Pilih Jam Kerja
                        <span x-show="currentDay" style="font-weight: normal; color: #6b7280; font-size: 0.875rem; margin-left: 8px;">
                            (Tanggal <span x-text="currentDay"></span>)
                        </span>
                    </h3>
                    <button type="button" @click="close()" style="color: #9ca3af; background: none; border: none; cursor: pointer; padding: 4px;">
                        <span class="sr-only">Close</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 24px; height: 24px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div style="flex: 1; overflow-y: auto; padding: 0;">
                    <!-- Loading -->
                    <div x-show="isLoading" style="padding: 32px; text-align: center; color: #6b7280;">
                        <svg style="animation: spin 1s linear infinite; height: 32px; width: 32px; margin: 0 auto 16px; color: #d97706;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Memuat data jam kerja...</p>
                        <style>
                            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                        </style>
                    </div>

                    <!-- Checking Schedule Loading -->
                    <div x-show="!isLoading && isLoadingSchedule" style="padding: 16px; text-align: center; background-color: #fffbeb; color: #d97706; font-size: 0.875rem;">
                        <svg class="animate-spin" style="display:inline-block; width:16px; height:16px; margin-right:8px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memeriksa jadwal tersimpan...
                    </div>

                    <!-- Error -->
                    <div x-show="error" style="text-align: center; color: #dc2626; padding: 16px;" x-text="error"></div>
                    <div x-show="fetchScheduleError" style="text-align: center; color: #dc2626; padding: 12px; background-color: #fef2f2; font-size: 0.875rem; border-bottom: 1px solid #fee2e2;">
                        Gagal mengambil jadwal tersimpan: <span x-text="fetchScheduleError"></span>
                    </div>

                    <!-- Table -->
                    <div x-show="!isLoading && !error" style="overflow-x: auto;">
                        <table style="width: 100%; text-align: left; font-size: 0.875rem; color: #6b7280; border-collapse: collapse;">
                            <thead style="background-color: #f9fafb; color: #374151; text-transform: uppercase; font-size: 0.75rem;">
                                <tr>
                                    <th scope="col" style="padding: 12px 16px; width: 40px;"></th>
                                    <th scope="col" style="padding: 12px 16px;">Nama</th>
                                    <th scope="col" style="padding: 12px 16px;">Mulai</th>
                                    <th scope="col" style="padding: 12px 16px;">Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr style="border-bottom: 1px solid #e5e7eb; hover:bg-gray-50;">
                                        <td style="padding: 12px 16px;">
                                            <input type="checkbox" :value="item.id" x-model="selected" style="border-radius: 4px; border-color: #d1d5db; color: #d97706; width: 16px; height: 16px; cursor: pointer;">
                                        </td>
                                        <td style="padding: 12px 16px; font-weight: 500; color: #111827;" x-text="item.name"></td>
                                        <td style="padding: 12px 16px;" x-text="item.start_time"></td>
                                        <td style="padding: 12px 16px;" x-text="item.end_time"></td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="4" style="padding: 24px; text-align: center;">Tidak ada data jam kerja.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb; background-color: #f9fafb; display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
                    <!-- Feedback Messages -->
                    <div style="flex: 1; margin-right: 16px;">
                        <span x-show="saveSuccess" style="color: #059669; font-size: 0.875rem;" x-text="saveSuccess"></span>
                        <span x-show="saveError" style="color: #dc2626; font-size: 0.875rem;" x-text="saveError"></span>
                    </div>

                    <button type="button" @click="close()" :disabled="isSaving" style="padding: 8px 16px; font-size: 0.875rem; font-weight: 500; color: #374151; background-color: white; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        Batal
                    </button>
                    <button type="button" @click="save()" :disabled="isSaving" style="padding: 8px 16px; font-size: 0.875rem; font-weight: 500; color: white; background-color: #d97706; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); display: flex; align-items: center; gap: 8px;">
                        <svg x-show="isSaving" class="animate-spin" style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSaving ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </div>
        </dialog>
    </template>
</div>
