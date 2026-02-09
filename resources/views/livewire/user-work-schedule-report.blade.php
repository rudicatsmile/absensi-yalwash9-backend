<div wire:init="loadData" style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1f2937; padding: 24px; background-color: #ffffff;">
    
    <!-- Filters Section -->
    <div style="margin-bottom: 24px; display: flex; flex-direction: column; gap: 16px;">
        <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: space-between;">
            
            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; flex: 1;">
                <!-- Date Filters -->
                <div style="min-width: 150px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tanggal Mulai</label>
                    <input type="date" wire:model.live="startDate" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); outline: none; transition: border-color 0.15s ease-in-out;">
                </div>
                <div style="min-width: 150px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Tanggal Selesai</label>
                    <input type="date" wire:model.live="endDate" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); outline: none; transition: border-color 0.15s ease-in-out;">
                </div>
                
                <!-- Dept Filter -->
                <div style="min-width: 200px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Departemen</label>
                    <select wire:model.live="departmentId" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); outline: none; background-color: white;">
                        <option value="">Semua Departemen</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Search -->
            <div style="width: 100%; max-width: 300px;">
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Cari Pegawai</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); outline: none;">
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div style="margin-bottom: 16px; display: flex; gap: 8px;">
        <button wire:click="exportExcel" wire:loading.attr="disabled" style="display: inline-flex; align-items: center; padding: 8px 16px; background-color: #059669; border: 1px solid transparent; border-radius: 6px; font-weight: 600; font-size: 12px; color: white; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: background-color 0.15s ease-in-out;">
            <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
            <span wire:loading wire:target="exportExcel">Processing...</span>
        </button>
        <button wire:click="exportPdf" wire:loading.attr="disabled" style="display: inline-flex; align-items: center; padding: 8px 16px; background-color: #dc2626; border: 1px solid transparent; border-radius: 6px; font-weight: 600; font-size: 12px; color: white; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: background-color 0.15s ease-in-out;">
            <span wire:loading.remove wire:target="exportPdf">Export PDF</span>
            <span wire:loading wire:target="exportPdf">Processing...</span>
        </button>
    </div>

    <!-- Table Container -->
    <div style="overflow-x: auto; position: relative; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        
        <!-- Loading Overlay -->
        <div wire:loading.flex style="position: absolute; inset: 0; align-items: center; justify-content: center; background-color: rgba(255, 255, 255, 0.75); z-index: 50;">
            <div style="border: 4px solid #f3f3f3; border-top: 4px solid #4f46e5; border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite;"></div>
            <style>
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; color: #6b7280;">
            <thead style="background-color: #f9fafb; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em;">
                <tr>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Tanggal</th>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Pegawai</th>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Departemen</th>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Shift</th>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Jam Masuk</th>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Jam Pulang</th>
                    <th scope="col" style="padding: 12px 24px; border-bottom: 1px solid #e5e7eb;">Total Jam</th>
                </tr>
            </thead>
            <tbody>
                @if(!$readyToLoad)
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; border-bottom: 1px solid #e5e7eb;">Loading data...</td>
                    </tr>
                @elseif($schedules->isEmpty())
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; border-bottom: 1px solid #e5e7eb;">Tidak ada data ditemukan.</td>
                    </tr>
                @else
                    @foreach($schedules as $schedule)
                        <tr style="background-color: white; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='white'">
                            <td style="padding: 16px 24px; white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }}
                            </td>
                            <td style="padding: 16px 24px; font-weight: 500; color: #111827; white-space: nowrap;">
                                {{ $schedule->user->name ?? '-' }}
                            </td>
                            <td style="padding: 16px 24px;">
                                {{ $schedule->user->departemen->name ?? '-' }}
                            </td>
                            <td style="padding: 16px 24px;">
                                {{ $schedule->shift->name ?? '-' }}
                            </td>
                            <td style="padding: 16px 24px;">
                                {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
                            </td>
                            <td style="padding: 16px 24px;">
                                {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '-' }}
                            </td>
                            <td style="padding: 16px 24px;">
                                @php
                                    $total = '-';
                                    if ($schedule->start_time && $schedule->end_time) {
                                        $start = \Carbon\Carbon::parse($schedule->start_time);
                                        $end = \Carbon\Carbon::parse($schedule->end_time);
                                        $diff = $start->diffInMinutes($end);
                                        $hours = floor($diff / 60);
                                        $minutes = $diff % 60;
                                        $total = sprintf('%02d:%02d', $hours, $minutes);
                                    }
                                @endphp
                                <span style="background-color: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 9999px; font-weight: 500; font-size: 12px;">
                                    {{ $total }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px;">
        {{ $readyToLoad ? $schedules->links() : '' }}
    </div>
</div>
