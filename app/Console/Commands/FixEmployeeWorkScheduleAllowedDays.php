<?php

namespace App\Console\Commands;

use App\Models\EmployeeWorkSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixEmployeeWorkScheduleAllowedDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-employee-work-schedule-allowed-days';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbaiki kolom allowed_days pada employee_work_schedule agar hanya hari Minggu bernilai false';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai perbaikan data allowed_days pada employee_work_schedule...');

        $fixedCount = 0;
        $skippedCount = 0;

        EmployeeWorkSchedule::query()
            ->orderBy('id')
            ->chunkById(200, function ($schedules) use (&$fixedCount, &$skippedCount) {
                foreach ($schedules as $schedule) {
                    $year = (int) $schedule->year;
                    $month = (int) $schedule->month;

                    if (! $year || ! $month) {
                        $skippedCount++;

                        continue;
                    }

                    $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

                    $current = (array) ($schedule->allowed_days ?? []);

                    if ($current === []) {
                        $isAllFalse = true;
                    } else {
                        $isAllFalse = collect(range(1, $daysInMonth))
                            ->every(function ($d) use ($current) {
                                $key = (string) $d;

                                return array_key_exists($key, $current)
                                    && in_array($current[$key], [false, 0, '0'], true);
                            });
                    }

                    if (! $isAllFalse) {
                        $skippedCount++;

                        continue;
                    }

                    $newMap = [];
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $date = Carbon::createFromDate($year, $month, $d);

                        $newMap[(string) $d] = $date->dayOfWeek === Carbon::SUNDAY
                            ? false
                            : true;
                    }

                    $schedule->allowed_days = $newMap;
                    $schedule->save();

                    $fixedCount++;
                }
            });

        $this->info("Perbaikan selesai. Jadwal diperbaiki: {$fixedCount}, dilewati: {$skippedCount}.");

        return self::SUCCESS;
    }
}
