<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Seed roles and default users for local development.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'admin',
            'ketua_pelaksana',
            'ketua_ormawa',
            'ketua_ukm',
            'kaprodi',
            'pembina_ukm',
            'wadir3',
            'kasubbag',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $users = [
            ['name' => 'Admin PNM', 'email' => 'admin@pnm.local', 'role' => 'admin'],
            ['name' => 'Ketua Pelaksana', 'email' => 'ketua.pelaksana@pnm.local', 'role' => 'ketua_pelaksana'],
            ['name' => 'Ketua ORMAWA', 'email' => 'ketua.ormawa@pnm.local', 'role' => 'ketua_ormawa'],
            ['name' => 'Ketua UKM', 'email' => 'ketua.ukm@pnm.local', 'role' => 'ketua_ukm'],
            ['name' => 'Kaprodi', 'email' => 'kaprodi@pnm.local', 'role' => 'kaprodi'],
            ['name' => 'Pembina UKM', 'email' => 'pembina.ukm@pnm.local', 'role' => 'pembina_ukm'],
            ['name' => 'Wadir 3', 'email' => 'wadir3@pnm.local', 'role' => 'wadir3'],
            ['name' => 'Kasubbag', 'email' => 'kasubbag@pnm.local', 'role' => 'kasubbag'],
        ];

        foreach ($users as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'ghani123456',
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
