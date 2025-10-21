<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permitTypes = [
            [
                'name' => 'Izin Sakit',
                'quota_days' => 12,
                'is_paid' => true,
                'urut' => 1,
            ],
            [
                'name' => 'Izin Pribadi',
                'quota_days' => 6,
                'is_paid' => false,
                'urut' => 2,
            ],
            [
                'name' => 'Izin Keluarga',
                'quota_days' => 3,
                'is_paid' => true,
                'urut' => 3,
            ],
            [
                'name' => 'Izin Dinas',
                'quota_days' => 30,
                'is_paid' => true,
                'urut' => 4,
            ],
            [
                'name' => 'Izin Melahirkan',
                'quota_days' => 90,
                'is_paid' => true,
                'urut' => 5,
            ],
        ];

        foreach ($permitTypes as $permitType) {
            \App\Models\PermitType::create($permitType);
        }
    }
}
