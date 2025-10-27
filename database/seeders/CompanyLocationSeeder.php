<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class CompanyLocationSeeder extends Seeder
{
    /**
     * Seed company locations for existing companies.
     */
    public function run(): void
    {
        $companies = Company::query()->get(['id', 'name']);

        if ($companies->isEmpty()) {
            // Hindari membuat company secara paksa agar tidak bentrok dengan skema/validasi
            $this->command?->warn('Tidak ada data company. Jalankan CompanySeeder terlebih dahulu sebelum CompanyLocationSeeder.');
            return;
        }

        $now = now();

        foreach ($companies as $company) {
            $locations = [
                [
                    'company_id' => $company->id,
                    'name' => 'Gedung A',
                    'address' => 'JL. Pedaengan No.99',
                    'latitude' => -6.2000000,
                    'longitude' => 106.8166667,
                    'radius_km' => 0.50,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $company->id,
                    'name' => 'Gedung B',
                    'address' => 'JL. Pedaengan No.99',
                    'latitude' => -6.2614930,
                    'longitude' => 107.0000000,
                    'radius_km' => 0.75,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $company->id,
                    'name' => 'Gedung C',
                    'address' => 'JL. Pedaengan No.99',
                    'latitude' => -6.9147440,
                    'longitude' => 107.6098100,
                    'radius_km' => 0.50,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $company->id,
                    'name' => 'Gedung D',
                    'address' => 'JL. Pedaengan No.99',
                    'latitude' => -6.9147440,
                    'longitude' => 107.6098100,
                    'radius_km' => 0.50,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'company_id' => $company->id,
                    'name' => 'Mesjid Asshodriyah',
                    'address' => 'JL. Sumarmo',
                    'latitude' => -6.9147440,
                    'longitude' => 107.6098100,
                    'radius_km' => 0.50,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            // Insert idempotent: skip jika kombinasi (company_id, name) sudah ada
            foreach ($locations as $loc) {
                $exists = DB::table('company_locations')
                    ->where('company_id', $loc['company_id'])
                    ->where('name', $loc['name'])
                    ->exists();

                if (!$exists) {
                    DB::table('company_locations')->insert($loc);
                }
            }
        }
    }
}
