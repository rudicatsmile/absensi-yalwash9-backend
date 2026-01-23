<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Permit;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AttendancePresenceService
{
    public function buildMatrix(
        string $startDate,
        string $endDate,
        ?int $departemenId,
        $shiftId, // Can be int or array
        string $statusFilter,
        string $mode
    ): array {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = collect($period)->map(fn($d) => $d->toDateString())->values();

        $usersQuery = User::query()
            ->select(['id', 'name', 'departemen_id', 'shift_kerja_id'])
            ->when($departemenId, fn($q) => $q->where('departemen_id', $departemenId));

        $users = $usersQuery->get();

        $attendances = Attendance::query()
            ->select(['user_id', 'date'])
            ->when(!empty($shiftId), function ($q) use ($shiftId) {
                if (is_array($shiftId)) {
                    $q->whereIn('shift_id', $shiftId);
                } else {
                    $q->where('shift_id', $shiftId);
                }
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($a) => $a->user_id . '|' . $a->date);

        $permits = Permit::query()
            ->select(['employee_id', 'permit_type_id', 'start_date', 'end_date', 'status'])
            ->where('status', 'approved')
            ->where('permit_type_id', '!=', 4) // Exclude permit_type_id 4
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate)
            ->get();

        $permitIndex = $this->indexPermitsByUserDate($permits, $dates);

        $rows = [];
        $totals = [
            'present' => 0,
            'absent' => 0,
            'absent_by_permit' => 0,
            'absent_unexcused' => 0,
        ];

        foreach ($users as $i => $u) {
            $row = [
                'No' => $i + 1,
                'Nama' => $u->name,
            ];

            $presentDays = 0;
            $absentDays = 0;
            $absentByPermit = 0;
            $absentUnexcused = 0;

            foreach ($dates as $d) {
                $key = $u->id . '|' . $d;
                $presentCount = isset($attendances[$key]) ? count($attendances[$key]) : 0;
                $permitTypeId = $permitIndex[$u->id][$d]['permit_type_id'] ?? null;

                $cell = [
                    'count' => $presentCount,
                    'present' => $presentCount > 0,
                    'permit_type_id' => $permitTypeId,
                    'absent_reason' => null,
                ];

                if ($presentCount === 0) {
                    if ($permitTypeId) {
                        $absentByPermit++;
                    } else {
                        $cell['absent_reason'] = 'Alpa';
                        $absentUnexcused++;
                    }
                    $absentDays++;
                } else {
                    $presentDays++;
                }

                $row[$d] = $cell;
            }

            if ($statusFilter === 'Hadir' && $presentDays === 0) {
                continue;
            }
            if ($statusFilter === 'Tidak Hadir' && $absentDays === 0) {
                continue;
            }

            $totals['present'] += $presentDays;
            $totals['absent'] += $absentDays;
            $totals['absent_by_permit'] += $absentByPermit;
            $totals['absent_unexcused'] += $absentUnexcused;

            $rows[] = $row;
        }

        return [
            'dates' => $dates->map(fn($d) => $d)->values()->all(),
            'rows' => $rows,
            'totals' => $totals,
            'mode' => $mode,
        ];
    }

    private function indexPermitsByUserDate(Collection $permits, Collection $dates): array
    {
        $index = [];
        foreach ($permits as $p) {
            foreach ($dates as $d) {
                if ($d >= $p->start_date->toDateString() && $d <= $p->end_date->toDateString()) {
                    $index[$p->employee_id][$d] = [
                        'permit_type_id' => $p->permit_type_id,
                    ];
                }
            }
        }
        return $index;
    }
}
