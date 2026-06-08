<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    /**
     * Seed baseline tools/equipment inventory.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'code' => 'ALAT-PROJ',
                'name' => 'Proyektor',
                'stock_qty' => 8,
                'unit' => 'unit',
                'description' => 'Proyektor untuk presentasi di kelas dan aula.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'ALAT-SOUND',
                'name' => 'Sound System Portable',
                'stock_qty' => 4,
                'unit' => 'set',
                'description' => 'Perangkat audio untuk kegiatan organisasi.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'ALAT-MIC',
                'name' => 'Mikrofon Wireless',
                'stock_qty' => 10,
                'unit' => 'unit',
                'description' => 'Mikrofon untuk seminar, rapat, dan pelatihan.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'ALAT-LCD',
                'name' => 'Layar Proyektor',
                'stock_qty' => 6,
                'unit' => 'unit',
                'description' => 'Layar pendukung presentasi.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('items')->upsert(
            $rows,
            ['code'],
            ['name', 'stock_qty', 'unit', 'description', 'is_active', 'updated_at']
        );
    }
}

