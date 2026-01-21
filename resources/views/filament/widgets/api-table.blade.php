<div
    class="fi-table-con w-full max-w-full overflow-x-auto rounded-lg shadow ring-1 ring-gray-950/5 dark:ring-white/10"
    x-data='{
        rows: [],
        total: 0,
        page: 1,
        perPage: 10,
        sort: "name",
        dir: "asc",
        q: "",
        filters: @json($filters),
        loading: false,
        error: null,
        async load() {
            try {
                this.loading = true;
                this.error = null;
                const base = {
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date,
                    q: this.q,
                    sort: this.sort,
                    dir: this.dir,
                    page: this.page,
                    per_page: this.perPage
                };

                // Add all filter keys to base object if they exist
                for (const key in this.filters) {
                    if (this.filters.hasOwnProperty(key) && this.filters[key] !== null && this.filters[key] !== "") {
                         base[key] = this.filters[key];
                    }
                }

                const params = new URLSearchParams(base);
                const url = window.location.origin + "{{ $endpoint }}" + "?" + params.toString();

                const res = await fetch(url);
                const json = await res.json();

                if (!res.ok) {
                    this.error = (json && json.message) ? json.message : "Gagal memuat";
                    this.rows = [];
                    this.total = 0;
                } else {
                    this.rows = json.data || [];
                    this.total = (json.pagination && json.pagination.total) ? json.pagination.total : this.rows.length;
                }
            } catch(e) {
                this.error = "Gagal memuat";
                this.rows = [];
                this.total = 0;
            } finally {
                this.loading = false;
            }
        },
        setSort(key) {
            if (this.sort === key) {
                this.dir = this.dir === "asc" ? "desc" : "asc";
            } else {
                this.sort = key;
                this.dir = "asc";
            }
            this.page = 1;
            this.load();
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.total / this.perPage));
        },
        goto(p) {
            if (p < 1 || p > this.totalPages()) return;
            this.page = p;
            this.load();
        }
    }'
    x-init="load()"
>
    <div class="flex items-center justify-between p-3 gap-3 flex-wrap bg-white dark:bg-gray-900 border-b border-gray-300 dark:border-white/10">
        <div class="flex items-center gap-2 flex-wrap">
            <input
                type="text"
                class="fi-input block w-full rounded-lg border-none bg-gray-50 px-3 py-1.5 text-sm text-gray-950 ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:ring-primary-500 sm:w-64"
                placeholder="Cari nama..."
                x-model.debounce.300ms="q"
                @input="page=1; load()"
            >

            @if(isset($filterOptions) && is_array($filterOptions))
                @foreach($filterOptions as $filter)
                    @if(($filter['type'] ?? '') === 'select')
                        <select
                            class="fi-input block w-auto rounded-lg border-none bg-gray-50 px-3 py-1.5 text-sm text-gray-950 ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:ring-primary-500"
                            x-model="filters.{{ $filter['model'] }}"
                            @change="page=1; load()"
                        >
                            <option value="">{{ $filter['placeholder'] ?? 'Pilih' }}</option>
                            @foreach($filter['options'] as $val => $text)
                                <option value="{{ $val }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    @endif
                @endforeach
            @endif
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="text-sm text-gray-500 dark:text-gray-400">Baris:</span>
            <select
                class="fi-input block w-auto rounded-lg border-none bg-gray-50 px-3 py-1.5 text-sm text-gray-950 ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:ring-primary-500"
                x-model.number="perPage"
                @change="page=1; load()"
            >
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <style>
        .custom-api-table, .custom-api-table th, .custom-api-table td {
            border: 1px solid #d1d5db !important;
            border-collapse: collapse !important;
            padding: 12px !important; /* Minimal 8px padding, set to 12px for better spacing */
            vertical-align: top !important; /* Ensure consistent vertical alignment */
        }
        .dark .custom-api-table, .dark .custom-api-table th, .dark .custom-api-table td {
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
    </style>
    <table class="fi-table custom-api-table w-full min-w-full table-auto border-collapse text-start border border-gray-300 dark:border-white/10">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th class="fi-table-header-cell p-3 text-left text-sm font-semibold text-gray-950 border border-gray-300 dark:text-white dark:border-white/10">No</th>
                @foreach($columns as $col)
                    <th
                        class="fi-table-header-cell p-3 text-left text-sm font-semibold text-gray-950 border border-gray-300 dark:text-white dark:border-white/10 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10"
                        @click="setSort('{{ $col['key'] }}')"
                    >
                        <span class="flex items-center gap-1">
                            {{ $col['label'] }}
                            <span x-show="sort === '{{ $col['key'] }}'" x-text="dir === 'asc' ? '↑' : '↓'"></span>
                        </span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <template x-for="(row, index) in rows" :key="row.id || index">
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="fi-table-cell p-3 text-sm text-gray-500 border border-gray-300 dark:text-gray-400 dark:border-white/10" x-text="(page - 1) * perPage + index + 1"></td>
                    @foreach($columns as $col)
                        <td class="fi-table-cell p-3 text-sm text-gray-950 border border-gray-300 dark:text-white dark:border-white/10">
                            @if(($col['format'] ?? '') === 'date')
                                <span x-text="row.{{ $col['key'] }} ? new Date(row.{{ $col['key'] }}).toLocaleDateString('id-ID') : '-'"></span>
                            @elseif(($col['format'] ?? '') === 'status')
                                <span
                                    class="px-2 py-1 rounded text-xs font-bold"
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': row.{{ $col['key'] }} === 'approved' || row.{{ $col['key'] }} === 'hadir',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': row.{{ $col['key'] }} === 'pending' || row.{{ $col['key'] }} === 'sakit' || row.{{ $col['key'] }} === 'izin',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': row.{{ $col['key'] }} === 'rejected' || row.{{ $col['key'] }} === 'alpha',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !['approved', 'pending', 'rejected', 'hadir', 'sakit', 'izin', 'alpha'].includes(row.{{ $col['key'] }})
                                    }"
                                    x-text="row.{{ $col['key'] }}"
                                ></span>
                            @else
                                <span x-text="row.{{ $col['key'] }}"></span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </template>
            <tr x-show="rows.length === 0 && !loading">
                <td colspan="{{ count($columns) + 1 }}" class="p-4 text-center text-sm text-gray-500 border border-gray-300 dark:text-gray-400 dark:border-white/10">
                    Tidak ada data
                </td>
            </tr>
        </tbody>
    </table>

    <div class="flex items-center justify-between p-3 border-t border-gray-300 dark:border-white/5">
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="fi-btn fi-btn-size-sm fi-btn-color-gray relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-outlined border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:border-white/20 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10"
                @click="goto(page - 1)"
                :disabled="page <= 1"
                :class="{ 'opacity-50 cursor-not-allowed': page <= 1 }"
            >
                Sebelumnya
            </button>
            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="page + ' / ' + totalPages()"></span>
            <button
                type="button"
                class="fi-btn fi-btn-size-sm fi-btn-color-gray relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-outlined border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:border-white/20 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10"
                @click="goto(page + 1)"
                :disabled="page >= totalPages()"
                :class="{ 'opacity-50 cursor-not-allowed': page >= totalPages() }"
            >
                Berikutnya
            </button>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-gray-400" x-show="loading">Memuat...</span>
            <span class="text-sm text-red-600 dark:text-red-400" x-show="error" x-text="error"></span>
        </div>
    </div>
</div>
