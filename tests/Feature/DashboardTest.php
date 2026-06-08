<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_can_be_rendered_with_new_layout(): void
    {
        $this->seed(RoleAndUserSeeder::class);

        $user = User::query()
            ->where('email', 'ketua.pelaksana@pnm.local')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Peminjaman Ruangan');
        $response->assertSee('Aksi Cepat');
        $response->assertSee('Agenda Saya Berikutnya');
    }
}

