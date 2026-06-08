<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndUserSeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCreatePrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_can_prefill_from_room_availability_query(): void
    {
        $this->seed([
            RoleAndUserSeeder::class,
            OrganizationSeeder::class,
            RoomSeeder::class,
        ]);

        $user = User::query()
            ->where('email', 'ketua.pelaksana@pnm.local')
            ->firstOrFail();
        $room = Room::query()
            ->where('code', 'R-101')
            ->firstOrFail();

        $date = now()->addDays(3)->toDateString();

        $response = $this->actingAs($user)->get(route('bookings.create', [
            'event_date' => $date,
            'room_id' => $room->id,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'from' => 'availability',
        ]));

        $response->assertOk();
        $response->assertSee('Slot dipilih dari halaman ketersediaan.');
        $response->assertSee('value="'.$date.'"', false);
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="11:00"', false);
        $response->assertSee('value="'.$room->id.'" selected', false);
    }
}

