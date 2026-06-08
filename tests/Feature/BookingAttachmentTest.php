<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\BookingAttachment;
use App\Models\Organization;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndUserSeeder;
use Database\Seeders\RoomOpeningHourSeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_upload_download_and_delete_booking_attachment(): void
    {
        Storage::fake('local');
        $this->seed([
            RoleAndUserSeeder::class,
            OrganizationSeeder::class,
            RoomSeeder::class,
            RoomOpeningHourSeeder::class,
        ]);

        $requester = User::query()
            ->where('email', 'ketua.pelaksana@pnm.local')
            ->firstOrFail();
        $organization = Organization::query()
            ->where('code', 'ORMAWA-BEM')
            ->firstOrFail();
        $room = Room::query()
            ->where('code', 'R-101')
            ->firstOrFail();

        $upload = UploadedFile::fake()->create('proposal-kegiatan.pdf', 450, 'application/pdf');

        $this->actingAs($requester)
            ->post(route('bookings.store'), [
                'organization_id' => $organization->id,
                'room_id' => $room->id,
                'event_name' => 'Rapat Penyusunan Program Kerja',
                'event_description' => 'Rapat internal awal semester',
                'event_date' => Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'participant_count' => 35,
                'action' => 'draft',
                'attachments' => [$upload],
            ])
            ->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $attachment = BookingAttachment::query()
            ->where('booking_id', $booking->id)
            ->firstOrFail();

        $this->assertSame('proposal-kegiatan.pdf', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->file_path);

        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'event_key' => 'attachment_uploaded',
        ]);

        $this->actingAs($requester)
            ->get(route('bookings.attachments.download', [$booking, $attachment]))
            ->assertOk();

        $this->actingAs($requester)
            ->delete(route('bookings.attachments.destroy', [$booking, $attachment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('booking_attachments', [
            'id' => $attachment->id,
        ]);
        Storage::disk('local')->assertMissing($attachment->file_path);

        $this->assertTrue(
            BookingActivityLog::query()
                ->where('booking_id', $booking->id)
                ->where('event_key', 'attachment_deleted')
                ->exists()
        );
    }

    public function test_requester_can_delete_attachment_after_submit_before_final_status(): void
    {
        Storage::fake('local');
        $this->seed([
            RoleAndUserSeeder::class,
            OrganizationSeeder::class,
            RoomSeeder::class,
            RoomOpeningHourSeeder::class,
        ]);

        $requester = User::query()
            ->where('email', 'ketua.pelaksana@pnm.local')
            ->firstOrFail();
        $organization = Organization::query()
            ->where('code', 'ORMAWA-BEM')
            ->firstOrFail();
        $room = Room::query()
            ->where('code', 'R-101')
            ->firstOrFail();

        $upload = UploadedFile::fake()->create('susunan-panitia.pdf', 300, 'application/pdf');

        $this->actingAs($requester)
            ->post(route('bookings.store'), [
                'organization_id' => $organization->id,
                'room_id' => $room->id,
                'event_name' => 'Workshop Kepanitiaan',
                'event_description' => 'Pembekalan panitia acara',
                'event_date' => Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'participant_count' => 40,
                'action' => 'draft',
                'attachments' => [$upload],
            ])
            ->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $booking->update([
            'status' => 'waiting_ketua_org',
            'current_step_order' => 1,
            'current_role_key' => 'ketua_ormawa',
            'submitted_at' => now(),
        ]);
        $attachment = BookingAttachment::query()
            ->where('booking_id', $booking->id)
            ->firstOrFail();

        $this->assertSame('waiting_ketua_org', $booking->fresh()->status);
        Storage::disk('local')->assertExists($attachment->file_path);

        $this->actingAs($requester)
            ->delete(route('bookings.attachments.destroy', [$booking, $attachment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('booking_attachments', [
            'id' => $attachment->id,
        ]);
        Storage::disk('local')->assertMissing($attachment->file_path);
    }
}
