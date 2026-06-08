<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed baseline organizations for flow testing.
     */
    public function run(): void
    {
        $now = now();

        $ketuaOrmawaId = User::query()->where('email', 'ketua.ormawa@pnm.local')->value('id');
        $ketuaUkmId = User::query()->where('email', 'ketua.ukm@pnm.local')->value('id');
        $kaprodiId = User::query()->where('email', 'kaprodi@pnm.local')->value('id');
        $pembinaUkmId = User::query()->where('email', 'pembina.ukm@pnm.local')->value('id');

        $rows = [
            [
                'code' => 'ORMAWA-BEM',
                'name' => 'BEM KM PNM',
                'type' => 'ormawa',
                'ketua_user_id' => $ketuaOrmawaId,
                'kaprodi_user_id' => $kaprodiId,
                'pembina_user_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'UKM-MUSIK',
                'name' => 'UKM Musik PNM',
                'type' => 'ukm',
                'ketua_user_id' => $ketuaUkmId,
                'kaprodi_user_id' => null,
                'pembina_user_id' => $pembinaUkmId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('organizations')->upsert(
            $rows,
            ['code'],
            ['name', 'type', 'ketua_user_id', 'kaprodi_user_id', 'pembina_user_id', 'is_active', 'updated_at']
        );
    }
}

