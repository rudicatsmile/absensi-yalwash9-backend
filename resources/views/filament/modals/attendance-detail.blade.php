<div style="display: flex; flex-direction: column; gap: 1rem;">
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
        <div>
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Nama
                Karyawan</label>
            <p style="font-size: 0.875rem; color: #111827;">{{ $record->user->name }}</p>
        </div>

        <div>
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Tanggal</label>
            <p style="font-size: 0.875rem; color: #111827;">
                {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</p>
        </div>

        <div>
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Jabatan</label>
            <p style="font-size: 0.875rem; color: #111827;">{{ $record->user->position ?? '-' }}</p>
        </div>

        <div>
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Departemen</label>
            <p style="font-size: 0.875rem; color: #111827;">{{ $record->user->department ?? '-' }}</p>
        </div>

        <div>
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Jam
                Masuk</label>
            <p style="font-size: 0.875rem; color: #111827;">{{ $timeIn }}</p>
        </div>

        <div>
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Jam
                Keluar</label>
            <p style="font-size: 0.875rem; color: #111827;">{{ $timeOut }}</p>
        </div>

        <div style="grid-column: span 2;">
            <label
                style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Total
                Jam Kerja</label>
            <p style="font-size: 0.875rem; color: #111827;">{{ $workingHours }}</p>
        </div>

        @if ($record->latlon_in)
            <div>
                <label
                    style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Lokasi
                    Check-in</label>
                <p style="font-size: 0.875rem; color: #111827;">{{ $record->latlon_in }}</p>
            </div>
        @endif

        @if ($record->latlon_out)
            <div>
                <label
                    style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Lokasi
                    Check-out</label>
                <p style="font-size: 0.875rem; color: #111827;">{{ $record->latlon_out }}</p>
            </div>
        @endif
    </div>
</div>
