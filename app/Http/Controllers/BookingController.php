<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingActionRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\BookingActivityLog;
use App\Models\BookingAttachment;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    /**
     * @return array<int, string>
     */
    private function approverRoleKeys(): array
    {
        return [
            'ketua_ormawa',
            'ketua_ukm',
            'kaprodi',
            'pembina_ukm',
            'wadir3',
            'kasubbag',
        ];
    }

    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $user = $request->user();
        $roleNames = $user->getRoleNames();

        $query = Booking::query()
            ->with(['organization', 'room', 'requester'])
            ->latest();

        if (!$user->hasRole('admin')) {
            if ($user->hasRole('ketua_pelaksana')) {
                $query->where('requester_id', $user->id);
            } else {
                $query->where(function ($q) use ($user, $roleNames) {
                    $q->whereIn('current_role_key', $roleNames)
                        ->orWhere('requester_id', $user->id);
                });
            }
        }

        $bookings = $query->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function approvalQueue(Request $request): View
    {
        $this->authorize('viewApprovalQueue', Booking::class);

        $user = $request->user();
        $roleNames = $user->getRoleNames()->values()->all();
        $approverRoles = array_values(array_intersect($roleNames, $this->approverRoleKeys()));
        $isAdmin = $user->hasRole('admin');

        $selectedRole = $request->query('role');
        if ($selectedRole && !$isAdmin && !in_array($selectedRole, $approverRoles, true)) {
            $selectedRole = null;
        }

        if (!$selectedRole && !$isAdmin && !empty($approverRoles)) {
            $selectedRole = $approverRoles[0];
        }
        $selectedPriority = $this->normalizePriority($request->query('priority'));

        $query = Booking::query()
            ->with(['organization', 'room', 'requester'])
            ->whereNotNull('current_role_key');

        if ($selectedRole) {
            $query->where('current_role_key', $selectedRole);
        } elseif (!$isAdmin) {
            $query->whereIn('current_role_key', $approverRoles);
        }

        $priorityCounts = $this->buildPriorityCounts(clone $query);
        $this->applyPriorityFilter($query, $selectedPriority);
        $this->applyPriorityOrdering($query);
        $bookings = $query->paginate(10)->withQueryString();

        $countQuery = Booking::query()
            ->selectRaw('current_role_key, COUNT(*) as total')
            ->whereNotNull('current_role_key');

        if (!$isAdmin) {
            $countQuery->whereIn('current_role_key', $approverRoles);
        }

        $pendingCounts = $countQuery
            ->groupBy('current_role_key')
            ->pluck('total', 'current_role_key');

        return view('bookings.approval-queue', [
            'bookings' => $bookings,
            'selectedRole' => $selectedRole,
            'selectedPriority' => $selectedPriority,
            'availableRoles' => $isAdmin ? $this->approverRoleKeys() : $approverRoles,
            'pendingCounts' => $pendingCounts,
            'priorityCounts' => $priorityCounts,
        ]);
    }

    public function exportApprovalQueueCsv(Request $request): StreamedResponse
    {
        $this->authorize('exportApprovalQueue', Booking::class);

        $user = $request->user();
        $roleNames = $user->getRoleNames()->values()->all();
        $approverRoles = array_values(array_intersect($roleNames, $this->approverRoleKeys()));
        $isAdmin = $user->hasRole('admin');

        $selectedRole = $request->query('role');
        if ($selectedRole && !$isAdmin && !in_array($selectedRole, $approverRoles, true)) {
            $selectedRole = null;
        }

        if (!$selectedRole && !$isAdmin && !empty($approverRoles)) {
            $selectedRole = $approverRoles[0];
        }

        $exportFormat = $this->normalizeExportFormat($request->query('format'));
        $selectedPriority = $this->normalizePriority($request->query('priority'));

        $query = Booking::query()
            ->with(['organization', 'room', 'requester', 'bookingItems.item'])
            ->whereNotNull('current_role_key');

        if ($selectedRole) {
            $query->where('current_role_key', $selectedRole);
        } elseif (!$isAdmin) {
            $query->whereIn('current_role_key', $approverRoles);
        }

        $this->applyPriorityFilter($query, $selectedPriority);
        $this->applyPriorityOrdering($query);

        $bookings = $query->get();
        $filename = 'approval-queue-'.$exportFormat.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($bookings, $exportFormat) {
            $handle = fopen('php://output', 'w');

            if ($exportFormat === 'detail') {
                fputcsv($handle, [
                    'event_name',
                    'organization',
                    'room',
                    'event_date',
                    'start_time',
                    'end_time',
                    'requester',
                    'current_role',
                    'status',
                    'priority',
                    'item_name',
                    'requested_qty',
                    'approved_qty',
                    'item_status',
                    'item_note',
                ]);

                foreach ($bookings as $booking) {
                    $items = $booking->bookingItems;

                    if ($items->isEmpty()) {
                        fputcsv($handle, [
                            $booking->event_name,
                            $booking->organization?->name,
                            $booking->room?->name,
                            $booking->event_date?->format('Y-m-d'),
                            $booking->start_time,
                            $booking->end_time,
                            $booking->requester?->name,
                            $booking->current_role_key,
                            $booking->status,
                            $this->priorityLabel($booking->event_date),
                            '',
                            '',
                            '',
                            '',
                            '',
                        ]);
                        continue;
                    }

                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $booking->event_name,
                            $booking->organization?->name,
                            $booking->room?->name,
                            $booking->event_date?->format('Y-m-d'),
                            $booking->start_time,
                            $booking->end_time,
                            $booking->requester?->name,
                            $booking->current_role_key,
                            $booking->status,
                            $this->priorityLabel($booking->event_date),
                            $item->item?->name,
                            $item->requested_qty,
                            $item->approved_qty,
                            $item->status_item,
                            $item->verification_note,
                        ]);
                    }
                }
            } else {
                fputcsv($handle, ['event_name', 'organization', 'room', 'event_date', 'start_time', 'end_time', 'requester', 'current_role', 'status', 'priority']);

                foreach ($bookings as $booking) {
                    fputcsv($handle, [
                        $booking->event_name,
                        $booking->organization?->name,
                        $booking->room?->name,
                        $booking->event_date?->format('Y-m-d'),
                        $booking->start_time,
                        $booking->end_time,
                        $booking->requester?->name,
                        $booking->current_role_key,
                        $booking->status,
                        $this->priorityLabel($booking->event_date),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportApprovalQueueXlsx(Request $request): StreamedResponse
    {
        $this->authorize('exportApprovalQueue', Booking::class);

        $user = $request->user();
        $roleNames = $user->getRoleNames()->values()->all();
        $approverRoles = array_values(array_intersect($roleNames, $this->approverRoleKeys()));
        $isAdmin = $user->hasRole('admin');

        $selectedRole = $request->query('role');
        if ($selectedRole && !$isAdmin && !in_array($selectedRole, $approverRoles, true)) {
            $selectedRole = null;
        }

        if (!$selectedRole && !$isAdmin && !empty($approverRoles)) {
            $selectedRole = $approverRoles[0];
        }

        $exportFormat = $this->normalizeExportFormat($request->query('format'));
        $selectedPriority = $this->normalizePriority($request->query('priority'));

        $query = Booking::query()
            ->with(['organization', 'room', 'requester', 'bookingItems.item'])
            ->whereNotNull('current_role_key');

        if ($selectedRole) {
            $query->where('current_role_key', $selectedRole);
        } elseif (!$isAdmin) {
            $query->whereIn('current_role_key', $approverRoles);
        }

        $this->applyPriorityFilter($query, $selectedPriority);
        $this->applyPriorityOrdering($query);

        $bookings = $query->get();
        $rows = $this->buildApprovalQueueExportRows($bookings, $exportFormat);
        $filename = 'approval-queue-'.$exportFormat.'-'.now()->format('Ymd-His').'.xlsx';

        return $this->downloadXlsx($rows, $filename, 'Approval Queue');
    }

    public function kasubbagQueue(Request $request): View
    {
        $this->authorize('viewKasubbagQueue', Booking::class);

        $allowedStatuses = ['waiting_kasubbag_verification', 'waiting_kasubbag_signature'];
        $selectedStatus = $request->query('status');
        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = null;
        }
        $selectedPriority = $this->normalizePriority($request->query('priority'));

        $query = Booking::query()
            ->with(['organization', 'room', 'requester', 'bookingItems'])
            ->where('current_role_key', 'kasubbag');

        if ($selectedStatus) {
            $query->where('status', $selectedStatus);
        }

        $priorityCounts = $this->buildPriorityCounts(clone $query);
        $this->applyPriorityFilter($query, $selectedPriority);
        $this->applyPriorityOrdering($query);
        $bookings = $query->paginate(10)->withQueryString();

        $pendingCounts = Booking::query()
            ->where('current_role_key', 'kasubbag')
            ->whereIn('status', $allowedStatuses)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('bookings.kasubbag-queue', [
            'bookings' => $bookings,
            'selectedStatus' => $selectedStatus,
            'selectedPriority' => $selectedPriority,
            'pendingCounts' => $pendingCounts,
            'priorityCounts' => $priorityCounts,
        ]);
    }

    public function exportKasubbagQueueCsv(Request $request): StreamedResponse
    {
        $this->authorize('exportKasubbagQueue', Booking::class);

        $allowedStatuses = ['waiting_kasubbag_verification', 'waiting_kasubbag_signature'];
        $selectedStatus = $request->query('status');
        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = null;
        }
        $exportFormat = $this->normalizeExportFormat($request->query('format'));
        $selectedPriority = $this->normalizePriority($request->query('priority'));

        $query = Booking::query()
            ->with(['organization', 'room', 'requester', 'bookingItems.item'])
            ->where('current_role_key', 'kasubbag');

        if ($selectedStatus) {
            $query->where('status', $selectedStatus);
        }

        $this->applyPriorityFilter($query, $selectedPriority);
        $this->applyPriorityOrdering($query);

        $bookings = $query->get();
        $filename = 'kasubbag-queue-'.$exportFormat.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($bookings, $exportFormat) {
            $handle = fopen('php://output', 'w');

            if ($exportFormat === 'detail') {
                fputcsv($handle, [
                    'event_name',
                    'organization',
                    'room',
                    'event_date',
                    'start_time',
                    'end_time',
                    'requester',
                    'status',
                    'priority',
                    'item_name',
                    'requested_qty',
                    'approved_qty',
                    'item_status',
                    'item_note',
                ]);

                foreach ($bookings as $booking) {
                    $items = $booking->bookingItems;

                    if ($items->isEmpty()) {
                        fputcsv($handle, [
                            $booking->event_name,
                            $booking->organization?->name,
                            $booking->room?->name,
                            $booking->event_date?->format('Y-m-d'),
                            $booking->start_time,
                            $booking->end_time,
                            $booking->requester?->name,
                            $booking->status,
                            $this->priorityLabel($booking->event_date),
                            '',
                            '',
                            '',
                            '',
                            '',
                        ]);
                        continue;
                    }

                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $booking->event_name,
                            $booking->organization?->name,
                            $booking->room?->name,
                            $booking->event_date?->format('Y-m-d'),
                            $booking->start_time,
                            $booking->end_time,
                            $booking->requester?->name,
                            $booking->status,
                            $this->priorityLabel($booking->event_date),
                            $item->item?->name,
                            $item->requested_qty,
                            $item->approved_qty,
                            $item->status_item,
                            $item->verification_note,
                        ]);
                    }
                }
            } else {
                fputcsv($handle, ['event_name', 'organization', 'room', 'event_date', 'start_time', 'end_time', 'requester', 'requested_items_count', 'status', 'priority']);

                foreach ($bookings as $booking) {
                    fputcsv($handle, [
                        $booking->event_name,
                        $booking->organization?->name,
                        $booking->room?->name,
                        $booking->event_date?->format('Y-m-d'),
                        $booking->start_time,
                        $booking->end_time,
                        $booking->requester?->name,
                        $booking->bookingItems->count(),
                        $booking->status,
                        $this->priorityLabel($booking->event_date),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportKasubbagQueueXlsx(Request $request): StreamedResponse
    {
        $this->authorize('exportKasubbagQueue', Booking::class);

        $allowedStatuses = ['waiting_kasubbag_verification', 'waiting_kasubbag_signature'];
        $selectedStatus = $request->query('status');
        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = null;
        }
        $exportFormat = $this->normalizeExportFormat($request->query('format'));
        $selectedPriority = $this->normalizePriority($request->query('priority'));

        $query = Booking::query()
            ->with(['organization', 'room', 'requester', 'bookingItems.item'])
            ->where('current_role_key', 'kasubbag');

        if ($selectedStatus) {
            $query->where('status', $selectedStatus);
        }

        $this->applyPriorityFilter($query, $selectedPriority);
        $this->applyPriorityOrdering($query);

        $bookings = $query->get();
        $rows = $this->buildKasubbagQueueExportRows($bookings, $exportFormat);
        $filename = 'kasubbag-queue-'.$exportFormat.'-'.now()->format('Ymd-His').'.xlsx';

        return $this->downloadXlsx($rows, $filename, 'Kasubbag Queue');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $organizations = Organization::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $prefill = $this->resolveCreatePrefill($request);

        return view('bookings.create', compact('organizations', 'rooms', 'items', 'prefill'));
    }

    public function edit(Booking $booking): View
    {
        $this->authorize('update', $booking);

        $booking->load(['bookingItems.item', 'attachments.uploader']);

        $organizations = Organization::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedItemQty = $booking->bookingItems
            ->pluck('requested_qty', 'item_id')
            ->toArray();

        return view('bookings.edit', compact('booking', 'organizations', 'rooms', 'items', 'selectedItemQty'));
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $room = Room::query()->findOrFail($data['room_id']);

        $scheduleError = $this->bookingService->validateSchedule(
            $room,
            $data['event_date'],
            $data['start_time'],
            $data['end_time']
        );

        if ($scheduleError) {
            return back()
                ->withInput()
                ->withErrors(['schedule' => $scheduleError]);
        }

        $booking = $this->bookingService->createBooking($request->user(), $data);
        $this->handleAttachmentUploads($booking, $request->file('attachments', []), $request->user());

        $message = $data['action'] === 'submit'
            ? 'Pengajuan berhasil dikirim ke alur persetujuan.'
            : 'Draft pengajuan berhasil disimpan.';

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $message);
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $room = Room::query()->findOrFail($data['room_id']);

        $scheduleError = $this->bookingService->validateSchedule(
            $room,
            $data['event_date'],
            $data['start_time'],
            $data['end_time'],
            $booking->id
        );

        if ($scheduleError) {
            return back()
                ->withInput()
                ->withErrors(['schedule' => $scheduleError]);
        }

        $this->bookingService->updateBookingData($booking, $data, $request->user());
        $this->removeAttachments($booking, (array) $request->input('remove_attachment_ids', []), $request->user());
        $this->handleAttachmentUploads($booking, $request->file('attachments', []), $request->user());

        $message = $booking->status === 'revision_requested'
            ? 'Perubahan revisi disimpan. Lanjutkan dengan tombol resubmit.'
            : 'Perubahan draft berhasil disimpan.';

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $message);
    }

    public function show(Booking $booking, Request $request): View
    {
        $user = $request->user();
        $this->authorize('view', $booking);

        $eventLabels = $this->auditEventLabels();
        $selectedLogEvent = $request->query('log_event');
        if ($selectedLogEvent && !array_key_exists($selectedLogEvent, $eventLabels)) {
            $selectedLogEvent = null;
        }

        $selectedLogActor = $request->integer('log_actor') ?: null;
        $selectedLogFrom = $request->query('log_from');
        if ($selectedLogFrom && !$this->isValidDate($selectedLogFrom)) {
            $selectedLogFrom = null;
        }
        $selectedLogTo = $request->query('log_to');
        if ($selectedLogTo && !$this->isValidDate($selectedLogTo)) {
            $selectedLogTo = null;
        }

        $booking->load([
            'organization',
            'room',
            'requester',
            'sprDocument.signer',
            'attachments.uploader',
            'bookingItems.item',
            'approvals' => fn ($query) => $query->orderByDesc('acted_at'),
            'approvals.actor',
            'revisions' => fn ($query) => $query->orderByDesc('round_no'),
            'revisions.requester',
        ]);

        $activityLogsQuery = $booking->activityLogs()
            ->with('actor')
            ->orderByDesc('acted_at');

        if ($selectedLogEvent) {
            $activityLogsQuery->where('event_key', $selectedLogEvent);
        }

        if ($selectedLogActor) {
            $activityLogsQuery->where('actor_id', $selectedLogActor);
        }

        if ($selectedLogFrom) {
            $activityLogsQuery->whereDate('acted_at', '>=', $selectedLogFrom);
        }

        if ($selectedLogTo) {
            $activityLogsQuery->whereDate('acted_at', '<=', $selectedLogTo);
        }

        $activityLogs = $activityLogsQuery
            ->paginate(10, ['*'], 'activity_page')
            ->withQueryString();

        $actorIds = $booking->activityLogs()
            ->whereNotNull('actor_id')
            ->distinct()
            ->pluck('actor_id');

        $logActors = User::query()
            ->whereIn('id', $actorIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $canProcessAction = $user->can('processAction', $booking);

        return view('bookings.show', [
            'booking' => $booking,
            'isCurrentApprover' => $canProcessAction,
            'canResubmit' => $user->can('resubmit', $booking),
            'canEdit' => $user->can('update', $booking),
            'canReviewAsKasubbag' => $user->can('reviewKasubbag', $booking),
            'canManageAttachments' => $user->can('manageAttachments', $booking),
            'activityLogs' => $activityLogs,
            'auditEventLabels' => $eventLabels,
            'logActors' => $logActors,
            'selectedLogEvent' => $selectedLogEvent,
            'selectedLogActor' => $selectedLogActor,
            'selectedLogFrom' => $selectedLogFrom,
            'selectedLogTo' => $selectedLogTo,
        ]);
    }

    public function downloadSpr(Booking $booking, Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $booking);

        $sprDocument = $this->bookingService->generateSprPdfDocument($booking);
        if (!$sprDocument) {
            return back()->withErrors([
                'spr' => 'SPR belum tersedia. Pengajuan harus mencapai approve final terlebih dahulu.',
            ]);
        }

        $pdfPath = (string) $sprDocument->pdf_path;
        if (blank($pdfPath) || $pdfPath === 'pending' || !Storage::disk('local')->exists($pdfPath)) {
            return back()->withErrors([
                'spr' => 'File PDF SPR belum tersedia. Silakan coba lagi.',
            ]);
        }

        $downloadName = str_replace(['/', '\\'], '-', $sprDocument->spr_number).'.pdf';

        return Storage::disk('local')->download(
            $pdfPath,
            $downloadName,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function downloadAttachment(Booking $booking, BookingAttachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $booking);

        if ($attachment->booking_id !== $booking->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            return back()->withErrors([
                'attachment' => 'File lampiran tidak ditemukan di server.',
            ]);
        }

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    public function destroyAttachment(Booking $booking, BookingAttachment $attachment, Request $request): RedirectResponse
    {
        $this->authorize('manageAttachments', $booking);

        if ($attachment->booking_id !== $booking->id) {
            abort(404);
        }

        $this->deleteSingleAttachment($booking, $attachment, $request->user());

        return back()->with('status', 'Lampiran berhasil dihapus.');
    }

    public function kasubbagReview(Booking $booking): View
    {
        $this->authorize('reviewKasubbag', $booking);

        $booking->load([
            'organization',
            'room',
            'requester',
            'bookingItems.item',
            'approvals' => fn ($query) => $query->orderByDesc('acted_at'),
            'approvals.actor',
            'revisions' => fn ($query) => $query->orderByDesc('round_no'),
            'revisions.requester',
        ]);

        return view('bookings.kasubbag-review', [
            'booking' => $booking,
        ]);
    }

    public function action(BookingActionRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('processAction', $booking);

        try {
            $this->bookingService->processApprovalAction(
                $booking,
                $request->user(),
                $request->string('action')->toString(),
                $request->input('note'),
                $request->input('items', [])
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['action' => 'Proses aksi gagal. Coba ulangi lagi.']);
        }

        return back()->with('status', 'Aksi berhasil diproses.');
    }

    public function resubmit(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('resubmit', $booking);

        $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->bookingService->resubmitAfterRevision(
                $booking,
                $request->user(),
                $request->input('note')
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['action' => 'Resubmit gagal. Coba ulangi lagi.']);
        }

        return back()->with('status', 'Pengajuan revisi berhasil dikirim ulang.');
    }

    /**
     * @param \Illuminate\Support\Collection<int, Booking> $bookings
     * @return array<int, array<int, string|int|null>>
     */
    private function buildApprovalQueueExportRows($bookings, string $exportFormat): array
    {
        if ($exportFormat === 'detail') {
            $rows = [[
                'event_name',
                'organization',
                'room',
                'event_date',
                'start_time',
                'end_time',
                'requester',
                'current_role',
                'status',
                'priority',
                'item_name',
                'requested_qty',
                'approved_qty',
                'item_status',
                'item_note',
            ]];

            foreach ($bookings as $booking) {
                $items = $booking->bookingItems;
                if ($items->isEmpty()) {
                    $rows[] = [
                        $booking->event_name,
                        $booking->organization?->name,
                        $booking->room?->name,
                        $booking->event_date?->format('Y-m-d'),
                        $booking->start_time,
                        $booking->end_time,
                        $booking->requester?->name,
                        $booking->current_role_key,
                        $booking->status,
                        $this->priorityLabel($booking->event_date),
                        '',
                        '',
                        '',
                        '',
                        '',
                    ];
                    continue;
                }

                foreach ($items as $item) {
                    $rows[] = [
                        $booking->event_name,
                        $booking->organization?->name,
                        $booking->room?->name,
                        $booking->event_date?->format('Y-m-d'),
                        $booking->start_time,
                        $booking->end_time,
                        $booking->requester?->name,
                        $booking->current_role_key,
                        $booking->status,
                        $this->priorityLabel($booking->event_date),
                        $item->item?->name,
                        $item->requested_qty,
                        $item->approved_qty,
                        $item->status_item,
                        $item->verification_note,
                    ];
                }
            }

            return $rows;
        }

        $rows = [[
            'event_name',
            'organization',
            'room',
            'event_date',
            'start_time',
            'end_time',
            'requester',
            'current_role',
            'status',
            'priority',
        ]];

        foreach ($bookings as $booking) {
            $rows[] = [
                $booking->event_name,
                $booking->organization?->name,
                $booking->room?->name,
                $booking->event_date?->format('Y-m-d'),
                $booking->start_time,
                $booking->end_time,
                $booking->requester?->name,
                $booking->current_role_key,
                $booking->status,
                $this->priorityLabel($booking->event_date),
            ];
        }

        return $rows;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Booking> $bookings
     * @return array<int, array<int, string|int|null>>
     */
    private function buildKasubbagQueueExportRows($bookings, string $exportFormat): array
    {
        if ($exportFormat === 'detail') {
            $rows = [[
                'event_name',
                'organization',
                'room',
                'event_date',
                'start_time',
                'end_time',
                'requester',
                'status',
                'priority',
                'item_name',
                'requested_qty',
                'approved_qty',
                'item_status',
                'item_note',
            ]];

            foreach ($bookings as $booking) {
                $items = $booking->bookingItems;
                if ($items->isEmpty()) {
                    $rows[] = [
                        $booking->event_name,
                        $booking->organization?->name,
                        $booking->room?->name,
                        $booking->event_date?->format('Y-m-d'),
                        $booking->start_time,
                        $booking->end_time,
                        $booking->requester?->name,
                        $booking->status,
                        $this->priorityLabel($booking->event_date),
                        '',
                        '',
                        '',
                        '',
                        '',
                    ];
                    continue;
                }

                foreach ($items as $item) {
                    $rows[] = [
                        $booking->event_name,
                        $booking->organization?->name,
                        $booking->room?->name,
                        $booking->event_date?->format('Y-m-d'),
                        $booking->start_time,
                        $booking->end_time,
                        $booking->requester?->name,
                        $booking->status,
                        $this->priorityLabel($booking->event_date),
                        $item->item?->name,
                        $item->requested_qty,
                        $item->approved_qty,
                        $item->status_item,
                        $item->verification_note,
                    ];
                }
            }

            return $rows;
        }

        $rows = [[
            'event_name',
            'organization',
            'room',
            'event_date',
            'start_time',
            'end_time',
            'requester',
            'requested_items_count',
            'status',
            'priority',
        ]];

        foreach ($bookings as $booking) {
            $rows[] = [
                $booking->event_name,
                $booking->organization?->name,
                $booking->room?->name,
                $booking->event_date?->format('Y-m-d'),
                $booking->start_time,
                $booking->end_time,
                $booking->requester?->name,
                $booking->bookingItems->count(),
                $booking->status,
                $this->priorityLabel($booking->event_date),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, string|int|null>> $rows
     */
    private function downloadXlsx(array $rows, string $filename, string $sheetTitle): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $sheetTitle) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $sheetTitle) ?: 'Export', 0, 31));
            $sheet->fromArray($rows, null, 'A1', true);

            $highestColumn = $sheet->getHighestDataColumn();
            foreach (range('A', $highestColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
            $sheet->freezePane('A2');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function applyPriorityOrdering(Builder $query): void
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        // Priority order: overdue first, then urgent (today/tomorrow), then normal.
        $query->orderByRaw(
            'CASE
                WHEN event_date < ? THEN 0
                WHEN event_date BETWEEN ? AND ? THEN 1
                ELSE 2
             END ASC',
            [$today, $today, $tomorrow]
        )
            ->orderBy('event_date')
            ->orderBy('start_time');
    }

    private function normalizePriority(?string $priority): string
    {
        return in_array($priority, ['all', 'overdue', 'urgent', 'normal'], true)
            ? $priority
            : 'all';
    }

    private function normalizeExportFormat(?string $format): string
    {
        return in_array($format, ['summary', 'detail'], true)
            ? $format
            : 'summary';
    }

    /**
     * @return array<string, string>
     */
    private function auditEventLabels(): array
    {
        return [
            'booking_saved_draft' => 'Simpan Draft',
            'booking_submitted' => 'Submit Pengajuan',
            'booking_updated' => 'Update Data Pengajuan',
            'approval_approved_step' => 'Approve ke Step Berikutnya',
            'approval_approved_final' => 'Approve Final',
            'approval_rejected' => 'Reject Pengajuan',
            'approval_revision_requested' => 'Request Revisi',
            'booking_resubmitted' => 'Resubmit Revisi',
            'spr_number_issued' => 'Nomor SPR Diterbitkan',
            'spr_pdf_generated' => 'PDF SPR Digenerate',
            'attachment_uploaded' => 'Lampiran Diunggah',
            'attachment_deleted' => 'Lampiran Dihapus',
        ];
    }

    private function isValidDate(?string $date): bool
    {
        if (!$date) {
            return false;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{
     *     event_date: string|null,
     *     room_id: int|null,
     *     start_time: string|null,
     *     end_time: string|null,
     *     from_availability: bool
     * }
     */
    private function resolveCreatePrefill(Request $request): array
    {
        $prefill = [
            'event_date' => null,
            'room_id' => null,
            'start_time' => null,
            'end_time' => null,
            'from_availability' => false,
        ];

        $eventDate = (string) $request->query('event_date');
        if ($this->isValidDate($eventDate) && $eventDate >= now()->toDateString()) {
            $prefill['event_date'] = $eventDate;
        }

        $roomId = $request->integer('room_id') ?: null;
        if (
            $roomId
            && Room::query()
                ->where('id', $roomId)
                ->where('is_active', true)
                ->exists()
        ) {
            $prefill['room_id'] = $roomId;
        }

        $startTime = $request->query('start_time');
        if ($this->isValidHourMinute($startTime)) {
            $prefill['start_time'] = $startTime;
        }

        $endTime = $request->query('end_time');
        if ($this->isValidHourMinute($endTime)) {
            $prefill['end_time'] = $endTime;
        }

        if (
            $prefill['start_time']
            && $prefill['end_time']
            && $prefill['end_time'] <= $prefill['start_time']
        ) {
            $prefill['end_time'] = null;
        }

        $prefill['from_availability'] = $request->query('from') === 'availability'
            && filled($prefill['event_date'])
            && filled($prefill['room_id'])
            && filled($prefill['start_time'])
            && filled($prefill['end_time']);

        return $prefill;
    }

    private function isValidHourMinute(?string $time): bool
    {
        if (!$time || !is_string($time)) {
            return false;
        }

        try {
            return Carbon::createFromFormat('H:i', $time)->format('H:i') === $time;
        } catch (\Throwable) {
            return false;
        }
    }

    private function applyPriorityFilter(Builder $query, string $priority): void
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        if ($priority === 'overdue') {
            $query->whereDate('event_date', '<', $today);
            return;
        }

        if ($priority === 'urgent') {
            $query->whereBetween('event_date', [$today, $tomorrow]);
            return;
        }

        if ($priority === 'normal') {
            $query->whereDate('event_date', '>', $tomorrow);
        }
    }

    /**
     * @return array<string, int>
     */
    private function buildPriorityCounts(Builder $baseQuery): array
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        return [
            'all' => (clone $baseQuery)->count(),
            'overdue' => (clone $baseQuery)->whereDate('event_date', '<', $today)->count(),
            'urgent' => (clone $baseQuery)->whereBetween('event_date', [$today, $tomorrow])->count(),
            'normal' => (clone $baseQuery)->whereDate('event_date', '>', $tomorrow)->count(),
        ];
    }

    private function priorityLabel(?Carbon $eventDate): string
    {
        if (!$eventDate) {
            return 'normal';
        }

        if ($eventDate->isBefore(today())) {
            return 'overdue';
        }

        if ($eventDate->isToday() || $eventDate->isTomorrow()) {
            return 'urgent';
        }

        return 'normal';
    }

    /**
     * @param array<int, UploadedFile|null> $files
     */
    private function handleAttachmentUploads(Booking $booking, array $files, User $actor): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());
            $storedName = Str::uuid()->toString().($extension ? '.'.$extension : '');
            $directory = 'bookings/'.$booking->id.'/attachments';
            $path = $file->storeAs($directory, $storedName, 'local');

            $attachment = BookingAttachment::query()->create([
                'booking_id' => $booking->id,
                'uploaded_by' => $actor->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $path,
                'mime_type' => (string) $file->getClientMimeType(),
                'size_bytes' => (int) $file->getSize(),
            ]);

            $this->logAttachmentActivity(
                booking: $booking,
                actor: $actor,
                eventKey: 'attachment_uploaded',
                metadata: [
                    'attachment_id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'size_bytes' => $attachment->size_bytes,
                ]
            );
        }
    }

    /**
     * @param array<int, int|string> $attachmentIds
     */
    private function removeAttachments(Booking $booking, array $attachmentIds, User $actor): void
    {
        if (empty($attachmentIds)) {
            return;
        }

        $ids = collect($attachmentIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $attachments = BookingAttachment::query()
            ->where('booking_id', $booking->id)
            ->whereIn('id', $ids->all())
            ->get();

        foreach ($attachments as $attachment) {
            $this->deleteSingleAttachment($booking, $attachment, $actor);
        }
    }

    private function deleteSingleAttachment(Booking $booking, BookingAttachment $attachment, User $actor): void
    {
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $this->logAttachmentActivity(
            booking: $booking,
            actor: $actor,
            eventKey: 'attachment_deleted',
            metadata: [
                'attachment_id' => $attachment->id,
                'original_name' => $attachment->original_name,
            ]
        );

        $attachment->delete();
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function logAttachmentActivity(Booking $booking, User $actor, string $eventKey, array $metadata): void
    {
        BookingActivityLog::query()->create([
            'booking_id' => $booking->id,
            'actor_id' => $actor->id,
            'event_key' => $eventKey,
            'action_label' => $eventKey,
            'old_status' => $booking->status,
            'new_status' => $booking->status,
            'metadata' => $metadata,
            'acted_at' => now(),
        ]);
    }
}
