<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ApprovalFlow;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\BookingActivityLog;
use App\Models\BookingRevision;
use App\Models\Organization;
use App\Models\Room;
use App\Models\RoomBlackout;
use App\Models\RoomOpeningHour;
use App\Models\SprDocument;
use App\Models\User;
use App\Notifications\BookingApproverNotification;
use App\Notifications\BookingRequesterNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Booking statuses that should lock room time to prevent overlap.
     *
     * @return array<int, string>
     */
    public function blockingStatuses(): array
    {
        return [
            'submitted',
            'waiting_ketua_org',
            'waiting_kaprodi',
            'waiting_pembina_ukm',
            'waiting_wadir3',
            'waiting_kasubbag_verification',
            'waiting_kasubbag_signature',
            'approved_final',
            'revision_requested',
        ];
    }

    /**
     * Returns null when schedule is valid, otherwise returns error message.
     */
    public function validateSchedule(Room $room, string $eventDate, string $startTime, string $endTime, ?int $ignoreBookingId = null): ?string
    {
        $startAt = Carbon::parse($eventDate.' '.$startTime);
        $endAt = Carbon::parse($eventDate.' '.$endTime);

        if ($endAt->lte($startAt)) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        $normalizedStart = $this->normalizeTime($startTime);
        $normalizedEnd = $this->normalizeTime($endTime);
        $dayOfWeek = $startAt->dayOfWeek;

        $opening = RoomOpeningHour::query()
            ->where('room_id', $room->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$opening || !$opening->is_open) {
            return 'Ruangan tutup pada hari yang dipilih.';
        }

        if ($normalizedStart < $opening->open_time || $normalizedEnd > $opening->close_time) {
            return 'Waktu yang dipilih di luar jam operasional ruangan.';
        }

        $hasBlackout = RoomBlackout::query()
            ->where('room_id', $room->id)
            ->where('starts_at', '<', $endAt)
            ->where('ends_at', '>', $startAt)
            ->exists();

        if ($hasBlackout) {
            return 'Ruangan tidak tersedia karena sedang diblokir pada jam tersebut.';
        }

        $conflictQuery = Booking::query()
            ->where('room_id', $room->id)
            ->whereDate('event_date', $eventDate)
            ->whereIn('status', $this->blockingStatuses())
            ->where('start_time', '<', $normalizedEnd)
            ->where('end_time', '>', $normalizedStart);

        if ($ignoreBookingId) {
            $conflictQuery->where('id', '!=', $ignoreBookingId);
        }

        if ($conflictQuery->exists()) {
            return 'Jadwal bentrok dengan booking lain. Silakan pilih jam atau ruangan lain.';
        }

        return null;
    }

    public function createBooking(User $requester, array $payload): Booking
    {
        $organization = Organization::query()->findOrFail($payload['organization_id']);
        $action = $payload['action'];
        $firstStep = null;

        if ($action === 'submit') {
            $firstStep = ApprovalFlow::query()
                ->where('organization_type', $organization->type)
                ->where('is_active', true)
                ->orderBy('step_order')
                ->first();
        }

        $booking = DB::transaction(function () use ($requester, $payload, $firstStep) {
            $booking = Booking::query()->create([
                'organization_id' => $payload['organization_id'],
                'requester_id' => $requester->id,
                'room_id' => $payload['room_id'],
                'event_name' => $payload['event_name'],
                'event_description' => $payload['event_description'] ?? null,
                'event_date' => $payload['event_date'],
                'start_time' => $this->normalizeTime($payload['start_time']),
                'end_time' => $this->normalizeTime($payload['end_time']),
                'participant_count' => $payload['participant_count'] ?? null,
                'status' => $payload['action'] === 'submit' && $firstStep ? $this->statusFromRoleKey($firstStep->role_key) : 'draft',
                'current_step_order' => $payload['action'] === 'submit' && $firstStep ? $firstStep->step_order : null,
                'current_role_key' => $payload['action'] === 'submit' && $firstStep ? $firstStep->role_key : null,
                'submitted_at' => $payload['action'] === 'submit' ? now() : null,
            ]);

            $this->syncBookingItems($booking, $payload['items'] ?? []);

            $this->logBookingActivity(
                $booking,
                actor: $requester,
                eventKey: $payload['action'] === 'submit' ? 'booking_submitted' : 'booking_saved_draft',
                actionLabel: $payload['action'],
                oldStatus: null,
                newStatus: $booking->status,
                metadata: [
                    'organization_id' => $booking->organization_id,
                    'room_id' => $booking->room_id,
                    'current_step_order' => $booking->current_step_order,
                    'current_role_key' => $booking->current_role_key,
                ]
            );

            return $booking;
        });

        $booking->loadMissing(['organization', 'room', 'requester']);

        if ($action === 'submit' && $firstStep) {
            $this->notifyApproversForRole(
                $booking,
                $firstStep->role_key,
                reason: 'submitted',
                excludeUserId: $requester->id
            );
        }

        return $booking;
    }

    public function updateBookingData(Booking $booking, array $payload, ?User $actor = null): Booking
    {
        return DB::transaction(function () use ($booking, $payload, $actor) {
            $oldStatus = (string) $booking->status;

            $booking->update([
                'organization_id' => $payload['organization_id'],
                'room_id' => $payload['room_id'],
                'event_name' => $payload['event_name'],
                'event_description' => $payload['event_description'] ?? null,
                'event_date' => $payload['event_date'],
                'start_time' => $this->normalizeTime($payload['start_time']),
                'end_time' => $this->normalizeTime($payload['end_time']),
                'participant_count' => $payload['participant_count'] ?? null,
            ]);

            $this->syncBookingItems($booking, $payload['items'] ?? []);

            $this->logBookingActivity(
                $booking,
                actor: $actor,
                eventKey: 'booking_updated',
                actionLabel: 'update',
                oldStatus: $oldStatus,
                newStatus: $booking->status,
                metadata: [
                    'organization_id' => $booking->organization_id,
                    'room_id' => $booking->room_id,
                    'event_date' => $booking->event_date,
                ]
            );

            return $booking->fresh(['organization', 'room', 'requester', 'bookingItems.item']);
        });
    }

    public function processApprovalAction(Booking $booking, User $actor, string $action, ?string $note = null, array $itemDecisions = []): Booking
    {
        $allowedActions = ['approve', 'reject', 'request_revision'];
        if (!in_array($action, $allowedActions, true)) {
            throw ValidationException::withMessages([
                'action' => 'Aksi tidak valid.',
            ]);
        }

        if (in_array($action, ['reject', 'request_revision'], true) && blank($note)) {
            throw ValidationException::withMessages([
                'note' => 'Catatan wajib diisi untuk reject atau request revisi.',
            ]);
        }

        if (!$this->canActOnCurrentStep($booking, $actor)) {
            throw ValidationException::withMessages([
                'action' => 'Kamu tidak punya akses untuk aksi pada step ini.',
            ]);
        }

        $nextRoleKey = null;
        $result = DB::transaction(function () use ($booking, $actor, $action, $note, $itemDecisions, &$nextRoleKey) {
            $booking->loadMissing('organization');
            $organizationType = $booking->organization->type;
            $oldStatus = (string) $booking->status;
            $currentStep = (int) $booking->current_step_order;
            $currentRoleKey = (string) $booking->current_role_key;

            if ($currentRoleKey === 'kasubbag' && $action === 'approve') {
                $this->applyKasubbagItemDecisions($booking, $actor, $itemDecisions);
            }

            BookingApproval::query()->create([
                'booking_id' => $booking->id,
                'step_order' => $currentStep,
                'role_key' => $currentRoleKey,
                'actor_id' => $actor->id,
                'action' => $action,
                'note' => $note,
                'acted_at' => now(),
            ]);

            if ($action === 'approve') {
                $nextStep = ApprovalFlow::query()
                    ->where('organization_type', $organizationType)
                    ->where('is_active', true)
                    ->where('step_order', '>', $currentStep)
                    ->orderBy('step_order')
                    ->first();

                if ($nextStep) {
                    $nextRoleKey = (string) $nextStep->role_key;
                    $booking->update([
                        'status' => $this->statusFromRoleKey($nextStep->role_key),
                        'current_step_order' => $nextStep->step_order,
                        'current_role_key' => $nextStep->role_key,
                    ]);
                } else {
                    $booking->update([
                        'status' => 'approved_final',
                        'current_step_order' => null,
                        'current_role_key' => null,
                        'finalized_at' => now(),
                    ]);

                    $this->issueSprDocumentForFinalApproval($booking, $actor, $note);
                }
            }

            if ($action === 'reject') {
                $booking->update([
                    'status' => 'rejected',
                    'current_step_order' => null,
                    'current_role_key' => null,
                    'finalized_at' => now(),
                ]);
            }

            if ($action === 'request_revision') {
                $roundNo = $booking->revision_count + 1;

                BookingRevision::query()->create([
                    'booking_id' => $booking->id,
                    'round_no' => $roundNo,
                    'requested_from_step' => $currentStep,
                    'requested_by' => $actor->id,
                    'note' => (string) $note,
                ]);

                $booking->update([
                    'status' => 'revision_requested',
                    'current_step_order' => null,
                    'current_role_key' => null,
                    'revision_count' => $roundNo,
                    'last_revision_note' => $note,
                ]);
            }

            $eventKey = match ($action) {
                'approve' => $nextRoleKey ? 'approval_approved_step' : 'approval_approved_final',
                'reject' => 'approval_rejected',
                'request_revision' => 'approval_revision_requested',
                default => 'approval_unknown',
            };

            $this->logBookingActivity(
                $booking,
                actor: $actor,
                eventKey: $eventKey,
                actionLabel: $action,
                oldStatus: $oldStatus,
                newStatus: $booking->status,
                note: $note,
                metadata: [
                    'from_step_order' => $currentStep,
                    'from_role_key' => $currentRoleKey,
                    'to_step_order' => $booking->current_step_order,
                    'to_role_key' => $booking->current_role_key,
                ]
            );

            return $booking->fresh(['organization', 'room', 'requester', 'bookingItems.item', 'approvals.actor', 'revisions.requester', 'sprDocument.signer']);
        });

        if ($action === 'approve') {
            if ($nextRoleKey) {
                $this->notifyApproversForRole(
                    $result,
                    $nextRoleKey,
                    reason: 'advanced',
                    actorName: $actor->name,
                    excludeUserId: $actor->id
                );
                $this->notifyRequesterStatusUpdate(
                    $result,
                    event: 'approve_step',
                    actorName: $actor->name,
                    note: $note,
                    nextRoleKey: $nextRoleKey
                );
            } else {
                $this->notifyRequesterStatusUpdate(
                    $result,
                    event: 'approved_final',
                    actorName: $actor->name,
                    note: $note
                );
            }
        }

        if ($action === 'approve' && !$nextRoleKey) {
            $this->generateSprPdfDocument($result);
        }

        if ($action === 'reject') {
            $this->notifyRequesterStatusUpdate(
                $result,
                event: 'rejected',
                actorName: $actor->name,
                note: $note
            );
        }

        if ($action === 'request_revision') {
            $this->notifyRequesterStatusUpdate(
                $result,
                event: 'revision_requested',
                actorName: $actor->name,
                note: $note
            );
        }

        return $result;
    }

    public function generateSprPdfDocument(Booking $booking, bool $forceRegenerate = false): ?SprDocument
    {
        $booking->loadMissing([
            'organization',
            'room',
            'requester',
            'bookingItems.item',
            'sprDocument.signer',
        ]);

        $sprDocument = $booking->sprDocument;
        if (!$sprDocument) {
            return null;
        }

        $currentPdfPath = (string) ($sprDocument->pdf_path ?? '');
        if (
            !$forceRegenerate
            && filled($currentPdfPath)
            && $currentPdfPath !== 'pending'
            && Storage::disk('local')->exists($currentPdfPath)
        ) {
            return $sprDocument;
        }

        $pdfPath = $this->buildSprPdfPath($booking, $sprDocument);
        $pdfBinary = Pdf::loadView('pdf.spr', [
            'booking' => $booking,
            'sprDocument' => $sprDocument,
        ])
            ->setPaper('a4')
            ->output();

        Storage::disk('local')->put($pdfPath, $pdfBinary);

        $sprDocument->update([
            'pdf_path' => $pdfPath,
        ]);

        $this->logBookingActivity(
            $booking,
            actor: $sprDocument->signer,
            eventKey: 'spr_pdf_generated',
            actionLabel: $forceRegenerate ? 'generate_pdf_force' : 'generate_pdf',
            oldStatus: $booking->status,
            newStatus: $booking->status,
            metadata: [
                'spr_number' => $sprDocument->spr_number,
                'pdf_path' => $pdfPath,
            ]
        );

        return $sprDocument->fresh('signer');
    }

    public function resubmitAfterRevision(Booking $booking, User $actor, ?string $note = null): Booking
    {
        $isRequester = $booking->requester_id === $actor->id;
        if (!$isRequester && !$actor->hasRole('admin')) {
            throw ValidationException::withMessages([
                'action' => 'Hanya pengaju atau admin yang bisa resubmit.',
            ]);
        }

        if ($booking->status !== 'revision_requested') {
            throw ValidationException::withMessages([
                'action' => 'Booking ini tidak dalam status revisi.',
            ]);
        }

        $targetRoleKey = null;
        $result = DB::transaction(function () use ($booking, $actor, $note, &$targetRoleKey) {
            $booking->loadMissing('organization');
            $oldStatus = (string) $booking->status;
            $pendingRevision = BookingRevision::query()
                ->where('booking_id', $booking->id)
                ->whereNull('resolved_at')
                ->orderByDesc('round_no')
                ->first();

            if (!$pendingRevision) {
                throw ValidationException::withMessages([
                    'action' => 'Data revisi tidak ditemukan.',
                ]);
            }

            $targetStep = ApprovalFlow::query()
                ->where('organization_type', $booking->organization->type)
                ->where('step_order', $pendingRevision->requested_from_step)
                ->where('is_active', true)
                ->first();

            if (!$targetStep) {
                throw ValidationException::withMessages([
                    'action' => 'Step tujuan revisi tidak valid.',
                ]);
            }
            $targetRoleKey = (string) $targetStep->role_key;

            $pendingRevision->update([
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ]);

            BookingApproval::query()->create([
                'booking_id' => $booking->id,
                'step_order' => $targetStep->step_order,
                'role_key' => $targetStep->role_key,
                'actor_id' => $actor->id,
                'action' => 'resubmit',
                'note' => $note,
                'acted_at' => now(),
            ]);

            $booking->update([
                'status' => $this->statusFromRoleKey($targetStep->role_key),
                'current_step_order' => $targetStep->step_order,
                'current_role_key' => $targetStep->role_key,
                'last_revision_note' => null,
            ]);

            $this->logBookingActivity(
                $booking,
                actor: $actor,
                eventKey: 'booking_resubmitted',
                actionLabel: 'resubmit',
                oldStatus: $oldStatus,
                newStatus: $booking->status,
                note: $note,
                metadata: [
                    'to_step_order' => $booking->current_step_order,
                    'to_role_key' => $booking->current_role_key,
                    'revision_round' => $pendingRevision->round_no,
                ]
            );

            return $booking->fresh(['organization', 'room', 'requester', 'bookingItems.item', 'approvals.actor', 'revisions.requester']);
        });

        if ($targetRoleKey) {
            $this->notifyApproversForRole(
                $result,
                $targetRoleKey,
                reason: 'resubmitted',
                actorName: $actor->name,
                note: $note,
                excludeUserId: $actor->id
            );
        }

        return $result;
    }

    private function issueSprDocumentForFinalApproval(Booking $booking, User $signer, ?string $signatureNote = null): void
    {
        $exists = SprDocument::query()
            ->where('booking_id', $booking->id)
            ->exists();

        if ($exists) {
            return;
        }

        $issuedDate = now()->toDateString();
        $sprNumber = $this->generateNextSprNumber($issuedDate);

        SprDocument::query()->create([
            'booking_id' => $booking->id,
            'spr_number' => $sprNumber,
            'issued_date' => $issuedDate,
            'signed_by' => $signer->id,
            'signature_note' => $signatureNote,
            // Placeholder path until PDF generation feature is implemented.
            'pdf_path' => 'pending',
        ]);

        $this->logBookingActivity(
            $booking,
            actor: $signer,
            eventKey: 'spr_number_issued',
            actionLabel: 'issue_spr',
            oldStatus: $booking->status,
            newStatus: $booking->status,
            note: $signatureNote,
            metadata: [
                'spr_number' => $sprNumber,
                'issued_date' => $issuedDate,
            ]
        );
    }

    private function generateNextSprNumber(string $issuedDate): string
    {
        $year = Carbon::parse($issuedDate)->year;
        $sequence = SprDocument::query()
            ->whereYear('issued_date', $year)
            ->lockForUpdate()
            ->count() + 1;

        while (true) {
            $candidate = sprintf('SPR/%04d/PNM/%d', $sequence, $year);
            $exists = SprDocument::query()
                ->where('spr_number', $candidate)
                ->lockForUpdate()
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $sequence++;
        }
    }

    private function buildSprPdfPath(Booking $booking, SprDocument $sprDocument): string
    {
        $year = Carbon::parse($sprDocument->issued_date)->format('Y');
        $month = Carbon::parse($sprDocument->issued_date)->format('m');

        return sprintf(
            'spr/%s/%s/spr-booking-%d-%d.pdf',
            $year,
            $month,
            $booking->id,
            $sprDocument->id
        );
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    private function logBookingActivity(
        Booking $booking,
        ?User $actor,
        string $eventKey,
        ?string $actionLabel = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $note = null,
        ?array $metadata = null,
    ): void {
        BookingActivityLog::query()->create([
            'booking_id' => $booking->id,
            'actor_id' => $actor?->id,
            'event_key' => $eventKey,
            'action_label' => $actionLabel,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => filled($note) ? $note : null,
            'metadata' => $metadata ? array_filter($metadata, static fn ($value) => !is_null($value)) : null,
            'acted_at' => now(),
        ]);
    }

    private function notifyApproversForRole(
        Booking $booking,
        string $roleKey,
        string $reason,
        ?string $actorName = null,
        ?string $note = null,
        ?int $excludeUserId = null,
    ): void {
        $recipients = User::query()
            ->role($roleKey)
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new BookingApproverNotification(
                    $booking,
                    $roleKey,
                    $reason,
                    $actorName,
                    $note
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function notifyRequesterStatusUpdate(
        Booking $booking,
        string $event,
        ?string $actorName = null,
        ?string $note = null,
        ?string $nextRoleKey = null,
    ): void {
        $requester = $booking->requester;
        if (!$requester) {
            return;
        }

        try {
            $requester->notify(new BookingRequesterNotification(
                $booking,
                $event,
                $actorName,
                $note,
                $nextRoleKey
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function statusFromRoleKey(string $roleKey): string
    {
        return match ($roleKey) {
            'ketua_ormawa', 'ketua_ukm' => 'waiting_ketua_org',
            'kaprodi' => 'waiting_kaprodi',
            'pembina_ukm' => 'waiting_pembina_ukm',
            'wadir3' => 'waiting_wadir3',
            'kasubbag' => 'waiting_kasubbag_verification',
            default => 'submitted',
        };
    }

    private function canActOnCurrentStep(Booking $booking, User $actor): bool
    {
        if ($actor->hasRole('admin')) {
            return true;
        }

        if (!$booking->current_role_key) {
            return false;
        }

        return $actor->hasRole($booking->current_role_key);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncBookingItems(Booking $booking, array $items): void
    {
        $desired = [];

        foreach ($items as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $qty = (int) ($row['requested_qty'] ?? 0);
            if ($itemId > 0 && $qty > 0) {
                $desired[$itemId] = $qty;
            }
        }

        $existing = $booking->bookingItems()->get()->keyBy('item_id');

        foreach ($desired as $itemId => $qty) {
            $existingRow = $existing->get($itemId);

            if ($existingRow) {
                $existingRow->update([
                    'requested_qty' => $qty,
                    'status_item' => 'pending',
                    'approved_qty' => null,
                    'verification_note' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            } else {
                $booking->bookingItems()->create([
                    'item_id' => $itemId,
                    'requested_qty' => $qty,
                    'status_item' => 'pending',
                ]);
            }
        }

        if (empty($desired)) {
            $booking->bookingItems()->delete();
            return;
        }

        $booking->bookingItems()
            ->whereNotIn('item_id', array_keys($desired))
            ->delete();
    }

    /**
     * @param array<int, array<string, mixed>> $itemDecisions
     */
    private function applyKasubbagItemDecisions(Booking $booking, User $actor, array $itemDecisions): void
    {
        $decisionMap = collect($itemDecisions)->keyBy(fn ($row) => (int) ($row['booking_item_id'] ?? 0));

        foreach ($booking->bookingItems()->get() as $bookingItem) {
            $raw = $decisionMap->get($bookingItem->id);
            if (!$raw) {
                throw ValidationException::withMessages([
                    'items' => 'Semua item harus diputuskan oleh kasubbag sebelum approve final.',
                ]);
            }

            $statusItem = $raw['status_item'] ?? 'approved';
            $approvedQty = isset($raw['approved_qty']) && $raw['approved_qty'] !== '' ? (int) $raw['approved_qty'] : null;
            $verificationNote = isset($raw['verification_note']) ? trim((string) $raw['verification_note']) : null;

            if (!in_array($statusItem, ['approved', 'partial', 'crossed'], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Status item tidak valid.',
                ]);
            }

            if ($statusItem === 'approved') {
                $approvedQty = $bookingItem->requested_qty;
            }

            if ($statusItem === 'partial') {
                if ($approvedQty === null || $approvedQty < 1 || $approvedQty >= $bookingItem->requested_qty) {
                    throw ValidationException::withMessages([
                        'items' => 'Qty partial harus diisi, minimal 1, dan lebih kecil dari qty diajukan.',
                    ]);
                }
            }

            if ($statusItem === 'crossed') {
                $approvedQty = 0;
                if (blank($verificationNote)) {
                    throw ValidationException::withMessages([
                        'items' => 'Catatan wajib diisi untuk item yang disilang.',
                    ]);
                }
            }

            $bookingItem->update([
                'status_item' => $statusItem,
                'approved_qty' => $approvedQty,
                'verification_note' => $verificationNote,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);
        }
    }
}
