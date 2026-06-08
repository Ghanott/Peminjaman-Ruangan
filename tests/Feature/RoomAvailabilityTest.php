<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Organization;
use App\Models\Room;
use App\Models\RoomBlackout;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndUserSeeder;
use Database\Seeders\RoomOpeningHourSeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_availability_page_shows_bookings_and_blackouts(): void
    {
        $this->seed([
            RoleAndUserSeeder::class,
            OrganizationSeeder::class,
            RoomSeeder::class,
            RoomOpeningHourSeeder::class,
        ]);

        $user = User::query()->where('email', 'ketua.pelaksana@pnm.local')->firstOrFail();
        $organization = Organization::query()->where('code', 'ORMAWA-BEM')->firstOrFail();
        $room = Room::query()->where('code', 'R-101')->firstOrFail();

        $date = now()->next('Tuesday')->toDateString();

        Booking::query()->create([
            'organization_id' => $organization->id,
            'requester_id' => $user->id,
            'room_id' => $room->id,
            'event_name' => 'Rapat Koordinasi Panitia',
            'event_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => 'waiting_ketua_org',
            'current_step_order' => 1,
            'current_role_key' => 'ketua_ormawa',
        ]);

        RoomBlackout::query()->create([
            'room_id' => $room->id,
            'starts_at' => $date.' 14:00:00',
            'ends_at' => $date.' 15:00:00',
            'reason' => 'Perawatan AC',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('rooms.availability', [
            'date' => $date,
            'room_id' => $room->id,
        ]));

        $response->assertOk();
        $response->assertSee('Jadwal Ketersediaan Ruangan');
        $response->assertSee('Ruang 101');
        $response->assertSee('Rapat Koordinasi Panitia');
        $response->assertSee('Perawatan AC');
        $response->assertSee('Slot Tersedia');
        $response->assertSee('Klik slot untuk auto-isi form pengajuan booking.');
        $response->assertSee('/bookings/create?event_date='.$date, false);
        $response->assertSee('from=availability');
    }
}
