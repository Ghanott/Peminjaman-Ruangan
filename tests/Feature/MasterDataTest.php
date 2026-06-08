<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoleAndUserSeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_privileged_user_cannot_access_master_routes(): void
    {
        $this->seed(RoleAndUserSeeder::class);

        $user = User::query()->where('email', 'ketua.pelaksana@pnm.local')->firstOrFail();

        $this->actingAs($user)
            ->get(route('master.rooms.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('master.items.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_room_and_item(): void
    {
        $this->seed(RoleAndUserSeeder::class);

        $admin = User::query()->where('email', 'admin@pnm.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('master.rooms.store'), [
                'code' => 'LAB-301',
                'name' => 'Lab Komputer 301',
                'location' => 'Gedung Teknologi Lantai 3',
                'capacity' => 40,
                'description' => 'Lab untuk praktikum.',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'code' => 'LAB-301',
            'name' => 'Lab Komputer 301',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('master.items.store'), [
                'code' => 'MIC-01',
                'name' => 'Microphone Wireless',
                'stock_qty' => 8,
                'unit' => 'buah',
                'description' => 'Microphone kegiatan seminar',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('items', [
            'code' => 'MIC-01',
            'name' => 'Microphone Wireless',
            'stock_qty' => 8,
            'is_active' => 1,
        ]);
    }

    public function test_kasubbag_can_update_opening_hours_and_create_blackout(): void
    {
        $this->seed([
            RoleAndUserSeeder::class,
            RoomSeeder::class,
        ]);

        $kasubbag = User::query()->where('email', 'kasubbag@pnm.local')->firstOrFail();
        $room = Room::query()->where('code', 'R-101')->firstOrFail();

        $payload = ['days' => []];
        for ($day = 0; $day <= 6; $day++) {
            $payload['days'][$day] = [
                'is_open' => $day >= 1 && $day <= 5 ? '1' : '0',
                'open_time' => $day >= 1 && $day <= 5 ? '08:00' : '',
                'close_time' => $day >= 1 && $day <= 5 ? '18:00' : '',
            ];
        }

        $this->actingAs($kasubbag)
            ->put(route('master.rooms.opening-hours.update', $room), $payload)
            ->assertRedirect(route('master.rooms.opening-hours.edit', $room));

        $this->assertDatabaseHas('room_opening_hours', [
            'room_id' => $room->id,
            'day_of_week' => 1,
            'is_open' => 1,
            'open_time' => '08:00:00',
            'close_time' => '18:00:00',
        ]);

        $this->actingAs($kasubbag)
            ->post(route('master.blackouts.store'), [
                'room_id' => $room->id,
                'starts_at' => now()->addDays(2)->setTime(13, 0)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDays(2)->setTime(15, 0)->format('Y-m-d\TH:i'),
                'reason' => 'Maintenance projector',
            ])
            ->assertRedirect(route('master.blackouts.index'));

        $this->assertDatabaseHas('room_blackouts', [
            'room_id' => $room->id,
            'reason' => 'Maintenance projector',
            'created_by' => $kasubbag->id,
        ]);
    }
}

