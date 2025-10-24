<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

    public function exportToPdf()
    {
        try {
            // Get filtered data from the table
            $query = $this->getFilteredTableQuery();
            $meetings = $query->with(['employee', 'approver', 'meetingType'])->get();

            // Create PDF using blade view
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.pages.laporan-meeting-pdf', [
                'meetings' => $meetings,
                'filters' => [
                    'tanggal_mulai' => now()->startOfMonth()->format('Y-m-d'),
                    'tanggal_selesai' => now()->format('Y-m-d'),
                    'status' => null,
                    'employee_id' => null,
                ],
                'exported_at' => now()->format('d/m/Y H:i'),
            ])
                ->setPaper('A4', 'landscape')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $filename = 'laporan-meeting-'.now()->format('d-m-Y').'.pdf';

            Notification::make()
                ->title('PDF berhasil diunduh')
                ->success()
                ->send();

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export PDF Gagal')
                ->body('Terjadi kesalahan: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}