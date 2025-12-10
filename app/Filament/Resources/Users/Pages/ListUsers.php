<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'kepala_lembaga', 'manager'], true)) {
            $actions[] = CreateAction::make();
        }
        return $actions;
    }

    public function exportUsersExcel()
    {
        try {
            $query = $this->getFilteredTableQuery();
            $users = $query->with(['departemen', 'shiftKerjas', 'companyLocations'])->get();

            $filters = $this->getActiveUserFilters();

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $row = 1;
            $sheet->setCellValue('A' . $row, 'Exported At');
            $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i'));
            $row++;
            $sheet->setCellValue('A' . $row, 'Filters');
            $sheet->setCellValue('B' . $row, $filters);
            $row += 2;

            $sheet->setCellValue('A' . $row, 'Nama');
            $sheet->setCellValue('B' . $row, 'Email');
            $sheet->setCellValue('C' . $row, 'Unit Kerja');
            $sheet->setCellValue('D' . $row, 'Shift Kerja');
            $sheet->setCellValue('E' . $row, 'Lokasi');
            $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($users as $u) {
                $name = ($u->name ?? '');
                $email = ($u->email ?? '');
                $dept = $u->departemen->name ?? 'Belum diset';
                $shift = ($u->shiftKerjas?->pluck('name')->filter()->all() ?? []);
                $shiftStr = count($shift) ? implode(', ', $shift) : 'Belum diset';
                $locs = ($u->companyLocations?->pluck('name')->filter()->all() ?? []);
                $locStr = count($locs) ? implode(', ', $locs) : 'Belum diset';

                $sheet->setCellValue('A' . $row, $name);
                $sheet->setCellValue('B' . $row, $email);
                $sheet->setCellValue('C' . $row, $dept);
                $sheet->setCellValue('D' . $row, $shiftStr);
                $sheet->setCellValue('E' . $row, $locStr);
                $row++;
            }

            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $sheet->freezePane('A' . (4));

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'users_export_' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            Notification::make()->title('Excel berhasil diunduh')->success()->send();

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        } catch (\Throwable $e) {
            Notification::make()->title('Export Excel Gagal')->body($e->getMessage())->danger()->send();
        }
    }

    public function exportUsersPdf()
    {
        try {
            $query = $this->getFilteredTableQuery();
            $users = $query->with(['departemen', 'shiftKerjas', 'companyLocations'])->get();

            $filters = $this->getActiveUserFilters();

            $rowsHtml = '';
            foreach ($users as $u) {
                $avatarUrl = $u->image_url ? (str_starts_with($u->image_url, 'http') ? $u->image_url : asset('storage/' . ltrim($u->image_url, '/'))) : '';
                $name = e($u->name ?? '');
                $email = e($u->email ?? '');
                $dept = e($u->departemen->name ?? 'Belum diset');
                $shift = ($u->shiftKerjas?->pluck('name')->filter()->all() ?? []);
                $shiftStr = e(count($shift) ? implode(', ', $shift) : 'Belum diset');
                $locs = ($u->companyLocations?->pluck('name')->filter()->all() ?? []);
                $locStr = e(count($locs) ? implode(', ', $locs) : 'Belum diset');
                $created = optional($u->created_at)->format('d/m/Y H:i');

                $rowsHtml .= '<tr>'
                    . '<td style="padding:6px; border:1px solid #ddd;">' . ($avatarUrl ? '<img src="' . $avatarUrl . '" alt="" width="40" height="40" style="border-radius:50%; object-fit:cover;" />' : '-') . '</td>'
                    . '<td style="padding:6px; border:1px solid #ddd;"><div style="font-weight:600;">' . $name . '</div><div style="font-size:12px; color:#64748b;">' . $email . '</div></td>'
                    . '<td style="padding:6px; border:1px solid #ddd;">' . $dept . '</td>'
                    . '<td style="padding:6px; border:1px solid #ddd;">' . $shiftStr . '</td>'
                    . '<td style="padding:6px; border:1px solid #ddd;">' . $locStr . '</td>'
                    . '<td style="padding:6px; border:1px solid #ddd;">' . $created . '</td>'
                    . '</tr>';
            }

            $html = '<html><head><meta charset="utf-8"><style>table{border-collapse:collapse; width:100%; font-family:system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; font-size:12px;} thead th{background:#f8fafc; border:1px solid #ddd; padding:8px; text-align:left;} tbody td{border:1px solid #ddd;}</style></head><body>'
                . '<h2 style="margin:0 0 8px 0;">Export Users</h2>'
                . '<div style="margin-bottom:10px; font-size:12px; color:#334155;">Filters: ' . e($filters) . ' • Exported: ' . now()->format('d/m/Y H:i') . '</div>'
                . '<table><thead><tr>'
                . '<th>Avatar</th><th>Nama / Email</th><th>Unit Kerja</th><th>Shift Kerja</th><th>Lokasi</th><th>Dibuat</th>'
                . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
                . '</body></html>';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('A4', 'landscape')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $filename = 'users_export_' . now()->format('Y-m-d-H-i-s') . '.pdf';

            Notification::make()->title('PDF berhasil diunduh')->success()->send();

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, ['Content-Type' => 'application/pdf']);
        } catch (\Throwable $e) {
            Notification::make()->title('Export PDF Gagal')->body($e->getMessage())->danger()->send();
        }
    }

    private function getActiveUserFilters(): string
    {
        try {
            $filters = request()->input('tableFilters', request()->input('filters', []));
            $role = $filters['role']['value'] ?? null;
            $deptId = $filters['departemen_id']['value'] ?? null;
            $deptName = null;
            if ($deptId) {
                $dept = \App\Models\Departemen::find($deptId);
                $deptName = $dept?->name;
            }
            $parts = [];
            if ($role)
                $parts[] = 'Role: ' . $role;
            if ($deptName)
                $parts[] = 'Departemen: ' . $deptName;
            return count($parts) ? implode(' • ', $parts) : 'Tidak ada filter';
        } catch (\Throwable $e) {
            return 'Tidak ada filter';
        }
    }
}
