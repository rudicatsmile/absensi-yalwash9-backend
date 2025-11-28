<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex justify-end mb-4">
        <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <x-filament::button
                wire:click="sortBy('name')"
                :color="$sortBy === 'name' ? 'primary' : 'gray'"
            >
                Nama Departemen
                @if ($sortBy === 'name')
                    <x-heroicon-s-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                @endif
            </x-filament::button>
            <x-filament::button
                wire:click="sortBy('attendance')"
                :color="$sortBy === 'attendance' ? 'primary' : 'gray'"
            >
                Jumlah Laporan
                @if ($sortBy === 'attendance')
                    <x-heroicon-s-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                @endif
            </x-filament::button>
            <x-filament::button
                wire:click="sortBy('percentage')"
                :color="$sortBy === 'percentage' ? 'primary' : 'gray'"
            >
                Persentase
                @if ($sortBy === 'percentage')
                    <x-heroicon-s-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-4 h-4 ml-1" />
                @endif
            </x-filament::button>
        </div>
    </div>

    <div wire:loading.class="opacity-50 transition-opacity duration-300">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($departmentStats as $stat)
                <div class="block p-3 bg-white rounded-lg shadow-md dark:bg-gray-800 transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1">
                    <div class="flex flex-col justify-start h-full">
                        <div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate" title="{{ $stat['name'] }}">
                                {{ $stat['name'] }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $stat['attendance'] }}
                            </p>
                            <div class="flex items-center text-xs font-medium {{ $stat['percentage'] > 75 ? 'text-green-600' : 'text-red-600' }} mt-1.5">
                                {{ $stat['percentage'] }}%
                                <x-heroicon-s-arrow-trending-up class="w-3 h-3 ml-1" />
                            </div>
                        </div>
                        <div class="mt-auto pt-2">
                            <x-filament::button
                                tag="a"
                                href="#"
                                size="xs"
                                outlined="true"
                                class="w-full text-center"
                            >
                                Lihat Detail
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-filament::card class="text-center">
                        <p class="text-gray-500 dark:text-gray-400">
                            Tidak ada data departemen atau kehadiran untuk ditampilkan.
                        </p>
                    </x-filament::card>
                </div>
            @endforelse
        </div>
    </div>

    <div wire:loading.flex class="col-span-full items-center justify-center mt-6">
        <x-filament::loading-indicator class="h-8 w-8" />
    </div>
</x-filament-panels::page>
