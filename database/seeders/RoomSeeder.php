<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    /**
     * Seed default rooms for booking simulation.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'code' => 'R-101',
                'name' => 'Ruang 101',
                'location' => 'Gedung M.Nuh Lantai 1',
                'capacity' => 60,
                'description' => 'Ruang kelas umum untuk kegiatan akademik dan organisasi.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'R-203',
                'name' => 'Ruang 203',
                'location' => 'Gedung M.Nuh Lantai 2',
                'capacity' => 40,
                'description' => 'Ruang diskusi dan rapat organisasi.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'RKB-1',
                'name' => 'RKB Kampus 1',
                'location' => 'Gedung RKB',
                'capacity' => 250,
                'description' => 'Ruang Kelas Bersama',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('rooms')->upsert(
            $rows,
            ['code'],
            ['name', 'location', 'capacity', 'description', 'is_active', 'updated_at']
        );
    }
}

