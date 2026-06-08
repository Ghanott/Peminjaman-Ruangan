<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalFlowSeeder extends Seeder
{
    /**
     * Seed default approval flow for ORMAWA and UKM paths.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['organization_type' => 'ormawa', 'step_order' => 1, 'role_key' => 'ketua_ormawa', 'is_active' => true],
            ['organization_type' => 'ormawa', 'step_order' => 2, 'role_key' => 'kaprodi', 'is_active' => true],
            ['organization_type' => 'ormawa', 'step_order' => 3, 'role_key' => 'wadir3', 'is_active' => true],
            ['organization_type' => 'ormawa', 'step_order' => 4, 'role_key' => 'kasubbag', 'is_active' => true],

            ['organization_type' => 'ukm', 'step_order' => 1, 'role_key' => 'ketua_ukm', 'is_active' => true],
            ['organization_type' => 'ukm', 'step_order' => 2, 'role_key' => 'pembina_ukm', 'is_active' => true],
            ['organization_type' => 'ukm', 'step_order' => 3, 'role_key' => 'wadir3', 'is_active' => true],
            ['organization_type' => 'ukm', 'step_order' => 4, 'role_key' => 'kasubbag', 'is_active' => true],
        ];

        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('approval_flows')->upsert(
            $rows,
            ['organization_type', 'step_order'],
            ['role_key', 'is_active', 'updated_at']
        );
    }
}

