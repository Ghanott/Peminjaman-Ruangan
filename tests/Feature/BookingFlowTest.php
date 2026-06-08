<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\BookingRevision;
use App\Models\Organization;
use App\Models\Room;
use App\Models\SprDocument;
use App\Models\User;
use App\Notifications\BookingApproverNotification;
use App\Notifications\BookingRequesterNotification;
use Carbon\Carbon;
use Database\Seeders\ApprovalFlowSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndUserSeeder;
use Database\Seeders\RoomOpeningHourSeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ormawa_flow_can_be_approved_until_final(): void
    {
        $this->seedBookingMasterData();

        $requester = $this->userByEmail('ketua.pelaksana@pnm.local');
        $ketuaOrmawa = $this->userByEmail('ketua.ormawa@pnm.local');
        $kaprodi = $this->userByEmail('kaprodi@pnm.local');
        $wadir3 = $this->userByEmail('wadir3@pnm.local');
        $kasubbag = $this->userByEmail('kasubbag@pnm.local');

        $this->assertTrue($ketuaOrmawa->hasRole('ketua_ormawa'));
        $this->assertTrue($kaprodi->hasRole('kaprodi'));
        $this->assertTrue($wadir3->hasRole('wadir3'));
        $this->assertTrue($kasubbag->hasRole('kasubbag'));

        $booking = $this->submitBooking($requester, 'ORMAWA-BEM');

        $this->assertSame('waiting_ketua_org', $booking->status);
        $this->assertSame('ketua_ormawa', $booking->current_role_key);
        $this->assertSame(1, (int) $booking->current_step_order);

        $this->actingAs($ketuaOrmawa)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('waiting_kaprodi', $booking->status);
        $this->assertSame('kaprodi', $booking->current_role_key);
        $this->assertSame(2, (int) $booking->current_step_order);

        $this->actingAs($kaprodi)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('waiting_wadir3', $booking->status);
        $this->assertSame('wadir3', $booking->current_role_key);
        $this->assertSame(3, (int) $booking->current_step_order);

        $this->actingAs($wadir3)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('waiting_kasubbag_verification', $booking->status);
        $this->assertSame('kasubbag', $booking->current_role_key);
        $this->assertSame(4, (int) $booking->current_step_order);

        $this->actingAs($kasubbag)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('approved_final', $booking->status);
        $this->assertNull($booking->current_role_key);
        $this->assertNull($booking->current_step_order);
        $this->assertNotNull($booking->finalized_at);

        $spr = SprDocument::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($spr);
        $this->assertMatchesRegularExpression('/^SPR\/\d{4}\/PNM\/\d{4}$/', (string) $spr->spr_number);
        $this->assertSame($kasubbag->id, (int) $spr->signed_by);
        $this->assertNotSame('pending', $spr->pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($spr->pdf_path));

        $events = BookingActivityLog::query()
            ->where('booking_id', $booking->id)
            ->pluck('event_key')
            ->all();
        $this->assertContains('booking_submitted', $events);
        $this->assertContains('approval_approved_step', $events);
        $this->assertContains('approval_approved_final', $events);
        $this->assertContains('spr_number_issued', $events);
        $this->assertContains('spr_pdf_generated', $events);
    }

    public function test_ukm_flow_routes_to_pembina_not_kaprodi(): void
    {
        $this->seedBookingMasterData();

        $requester = $this->userByEmail('ketua.pelaksana@pnm.local');
        $ketuaUkm = $this->userByEmail('ketua.ukm@pnm.local');

        $this->assertTrue($ketuaUkm->hasRole('ketua_ukm'));

        $booking = $this->submitBooking($requester, 'UKM-MUSIK');

        $this->assertSame('waiting_ketua_org', $booking->status);
        $this->assertSame('ketua_ukm', $booking->current_role_key);
        $this->assertSame(1, (int) $booking->current_step_order);

        $this->actingAs($ketuaUkm)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('waiting_pembina_ukm', $booking->status);
        $this->assertSame('pembina_ukm', $booking->current_role_key);
        $this->assertSame(2, (int) $booking->current_step_order);
    }

    public function test_requester_can_download_spr_pdf_after_final_approval(): void
    {
        $this->seedBookingMasterData();

        $requester = $this->userByEmail('ketua.pelaksana@pnm.local');
        $ketuaOrmawa = $this->userByEmail('ketua.ormawa@pnm.local');
        $kaprodi = $this->userByEmail('kaprodi@pnm.local');
        $wadir3 = $this->userByEmail('wadir3@pnm.local');
        $kasubbag = $this->userByEmail('kasubbag@pnm.local');

        $booking = $this->submitBooking($requester, 'ORMAWA-BEM');

        $this->actingAs($ketuaOrmawa)->post(route('bookings.action', $booking), ['action' => 'approve'])->assertRedirect();
        $this->actingAs($kaprodi)->post(route('bookings.action', $booking), ['action' => 'approve'])->assertRedirect();
        $this->actingAs($wadir3)->post(route('bookings.action', $booking), ['action' => 'approve'])->assertRedirect();
        $this->actingAs($kasubbag)->post(route('bookings.action', $booking), ['action' => 'approve'])->assertRedirect();

        $response = $this->actingAs($requester)
            ->get(route('bookings.spr.download', $booking));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));

        $spr = SprDocument::query()->where('booking_id', $booking->id)->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($spr->pdf_path));
    }

    public function test_revision_and_resubmit_return_to_requested_step(): void
    {
        $this->seedBookingMasterData();

        $requester = $this->userByEmail('ketua.pelaksana@pnm.local');
        $ketuaOrmawa = $this->userByEmail('ketua.ormawa@pnm.local');
        $kaprodi = $this->userByEmail('kaprodi@pnm.local');

        $this->assertTrue($ketuaOrmawa->hasRole('ketua_ormawa'));
        $this->assertTrue($kaprodi->hasRole('kaprodi'));

        $booking = $this->submitBooking($requester, 'ORMAWA-BEM');

        $this->actingAs($ketuaOrmawa)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('kaprodi', $booking->current_role_key);

        $this->actingAs($kaprodi)
            ->post(route('bookings.action', $booking), [
                'action' => 'request_revision',
                'note' => 'Lengkapi surat kegiatan dan perbaiki deskripsi agenda.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('revision_requested', $booking->status);
        $this->assertNull($booking->current_role_key);
        $this->assertNull($booking->current_step_order);
        $this->assertSame(1, (int) $booking->revision_count);

        $this->actingAs($requester)
            ->post(route('bookings.resubmit', $booking), [
                'note' => 'Dokumen sudah diperbaiki.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('waiting_kaprodi', $booking->status);
        $this->assertSame('kaprodi', $booking->current_role_key);
        $this->assertSame(2, (int) $booking->current_step_order);
        $this->assertNull($booking->last_revision_note);

        $revision = BookingRevision::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($revision);
        $this->assertNotNull($revision->resolved_at);

        $events = BookingActivityLog::query()
            ->where('booking_id', $booking->id)
            ->pluck('event_key')
            ->all();
        $this->assertContains('approval_revision_requested', $events);
        $this->assertContains('booking_resubmitted', $events);
    }

    public function test_notifications_are_sent_for_submit_approve_and_revision(): void
    {
        $this->seedBookingMasterData();
        Notification::fake();

        $requester = $this->userByEmail('ketua.pelaksana@pnm.local');
        $ketuaOrmawa = $this->userByEmail('ketua.ormawa@pnm.local');
        $kaprodi = $this->userByEmail('kaprodi@pnm.local');

        $this->assertTrue($ketuaOrmawa->hasRole('ketua_ormawa'));
        $this->assertTrue($kaprodi->hasRole('kaprodi'));

        $booking = $this->submitBooking($requester, 'ORMAWA-BEM');

        Notification::assertSentTo($ketuaOrmawa, BookingApproverNotification::class);

        $this->actingAs($ketuaOrmawa)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $booking->refresh();
        Notification::assertSentTo($kaprodi, BookingApproverNotification::class);
        Notification::assertSentTo($requester, BookingRequesterNotification::class);

        $this->actingAs($kaprodi)
            ->post(route('bookings.action', $booking), [
                'action' => 'request_revision',
                'note' => 'Perlu revisi lampiran.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($requester, BookingRequesterNotification::class);
    }

    public function test_booking_show_can_filter_audit_logs_by_event(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Skipped on Windows due compiled Blade file lock (rename access denied).');
        }

        $this->seedBookingMasterData();
        $compiledPath = storage_path('framework/testing/views/'.Str::uuid()->toString());
        File::ensureDirectoryExists($compiledPath);
        config()->set('view.compiled', $compiledPath);

        $requester = $this->userByEmail('ketua.pelaksana@pnm.local');
        $ketuaOrmawa = $this->userByEmail('ketua.ormawa@pnm.local');
        $booking = $this->submitBooking($requester, 'ORMAWA-BEM');

        $this->actingAs($ketuaOrmawa)
            ->post(route('bookings.action', $booking), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $response = $this->actingAs($requester)
            ->get(route('bookings.show', [
                'booking' => $booking,
                'log_event' => 'booking_submitted',
            ]));

        $response->assertOk();
        $response->assertSee('Submit Pengajuan');
        $response->assertDontSee('status: waiting_ketua_org -> waiting_kaprodi');
    }

    private function seedBookingMasterData(): void
    {
        $this->seed([
            RoleAndUserSeeder::class,
            OrganizationSeeder::class,
            RoomSeeder::class,
            RoomOpeningHourSeeder::class,
            ApprovalFlowSeeder::class,
        ]);
    }

    private function userByEmail(string $email): User
    {
        return User::query()
            ->where('email', $email)
            ->firstOrFail();
    }

    private function submitBooking(User $requester, string $organizationCode): Booking
    {
        $organization = Organization::query()
            ->where('code', $organizationCode)
            ->firstOrFail();

        $room = Room::query()
            ->where('code', 'R-101')
            ->firstOrFail();

        $response = $this->actingAs($requester)
            ->post(route('bookings.store'), [
                'organization_id' => $organization->id,
                'room_id' => $room->id,
                'event_name' => 'Audit Kaderisasi',
                'event_description' => 'Simulasi pengujian alur booking',
                'event_date' => Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'participant_count' => 80,
                'action' => 'submit',
            ]);

        $response->assertSessionHasNoErrors();

        return Booking::query()->latest('id')->firstOrFail();
    }
}
