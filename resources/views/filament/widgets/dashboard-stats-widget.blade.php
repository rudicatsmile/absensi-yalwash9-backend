<x-filament-widgets::widget class="fi-widgets-dashboard-stats bg-transparent shadow-none ring-0 p-0">
    <div class="space-y-6">
        {{-- 5 Main Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">

            {{-- 1. Total Pegawai --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border-b-4 border-blue-500 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-full group-hover:scale-110 transition-transform duration-300">
                         <x-heroicon-m-users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate uppercase tracking-wider">Total Pegawai</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalPegawai }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. Total Hadir --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border-b-4 border-green-500 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-full group-hover:scale-110 transition-transform duration-300">
                         <x-heroicon-m-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate uppercase tracking-wider">Hadir</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalHadir }}</div>
                    </div>
                </div>
            </div>

            {{-- 3. Total Tidak Hadir --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border-b-4 border-red-500 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-full group-hover:scale-110 transition-transform duration-300">
                         <x-heroicon-m-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate uppercase tracking-wider">Tidak Hadir</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalTidakHadir }}</div>
                    </div>
                </div>
            </div>

            {{-- 4. Total Izin --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border-b-4 border-amber-500 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-full group-hover:scale-110 transition-transform duration-300">
                         <x-heroicon-m-clipboard-document-check class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate uppercase tracking-wider">Izin</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalIzin }}</div>
                    </div>
                </div>
            </div>

            {{-- 5. Total Cuti --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border-b-4 border-purple-500 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-full group-hover:scale-110 transition-transform duration-300">
                         <x-heroicon-m-calendar-days class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate uppercase tracking-wider">Cuti</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalCuti }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-b border-gray-200 dark:border-white/10 pb-4"></div>
    </div>
</x-filament-widgets::widget>
