<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomBlackout;
use App\Models\RoomOpeningHour;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RoomAvailabilityController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function __invoke(Request $request): View
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->query('date', now()->toDateString()));
        $selectedRoomId = $request->integer('room_id') ?: null;

        $rooms = Room::query()
            ->where('is_active', true)
            ->when($selectedRoomId, fn ($query) => $query->where('id', $selectedRoomId))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'location', 'capacity']);

        $roomIds = $rooms->pluck('id')->all();
        $dayOfWeek = $selectedDate->dayOfWeek;
        $dayStart = $selectedDate->copy()->startOfDay();
        $dayEnd = $selectedDate->copy()->endOfDay();

        $openingHoursByRoom = RoomOpeningHour::query()
            ->whereIn('room_id', $roomIds)
            ->where('day_of_week', $dayOfWeek)
            ->get()
            ->keyBy('room_id');

        $bookingsByRoom = Booking::query()
            ->with(['organization:id,name'])
            ->whereIn('room_id', $roomIds)
            ->whereDate('event_date', $selectedDate->toDateString())
            ->whereIn('status', $this->bookingService->blockingStatuses())
            ->orderBy('start_time')
            ->get([
                'id',
                'room_id',
                'organization_id',
                'event_name',
                'status',
                'start_time',
                'end_time',
            ])
            ->groupBy('room_id');

        $blackoutsByRoom = RoomBlackout::query()
            ->whereIn('room_id', $roomIds)
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->orderBy('starts_at')
            ->get([
                'id',
                'room_id',
                'starts_at',
                'ends_at',
                'reason',
            ])
            ->groupBy('room_id');

        $cards = $rooms->map(function (Room $room) use ($openingHoursByRoom, $bookingsByRoom, $blackoutsByRoom, $selectedDate) {
            return $this->buildAvailabilityCard(
                $room,
                $openingHoursByRoom->get($room->id),
                $bookingsByRoom->get($room->id, collect()),
                $blackoutsByRoom->get($room->id, collect()),
                $selectedDate
            );
        });

        return view('rooms.availability', [
            'selectedDate' => $selectedDate,
            'selectedRoomId' => $selectedRoomId,
            'rooms' => Room::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'cards' => $cards,
            'summary' => [
                'total' => $cards->count(),
                'available' => $cards->where('availability_status', 'available')->count(),
                'full' => $cards->where('availability_status', 'full')->count(),
                'closed' => $cards->where('availability_status', 'closed')->count(),
            ],
        ]);
    }

    private function resolveSelectedDate(string $rawDate): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $rawDate)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function buildAvailabilityCard(
        Room $room,
        ?RoomOpeningHour $openingHour,
        Collection $bookings,
        Collection $blackouts,
        Carbon $selectedDate,
    ): array {
        $timeline = collect();

        foreach ($bookings as $booking) {
            $start = $this->timeToMinute((string) $booking->start_time);
            $end = $this->timeToMinute((string) $booking->end_time);
            if ($end <= $start) {
                continue;
            }

            $timeline->push([
                'type' => 'booking',
                'title' => (string) $booking->event_name,
                'subtitle' => $booking->organization?->name ?: '-',
                'status' => (string) $booking->status,
                'start_label' => $this->minuteToTime($start),
                'end_label' => $this->minuteToTime($end),
                'start_minute' => $start,
                'end_minute' => $end,
            ]);
        }

        foreach ($blackouts as $blackout) {
            $startAt = $blackout->starts_at->copy();
            $endAt = $blackout->ends_at->copy();
            $dayStart = $selectedDate->copy()->startOfDay();
            $dayEnd = $selectedDate->copy()->endOfDay();

            $clampedStart = $startAt->lt($dayStart) ? $dayStart : $startAt;
            $clampedEnd = $endAt->gt($dayEnd) ? $dayEnd : $endAt;

            $start = ((int) $clampedStart->format('H')) * 60 + (int) $clampedStart->format('i');
            $end = ((int) $clampedEnd->format('H')) * 60 + (int) $clampedEnd->format('i');
            if ($end <= $start) {
                continue;
            }

            $timeline->push([
                'type' => 'blackout',
                'title' => 'Ruangan Ditutup',
                'subtitle' => (string) ($blackout->reason ?: 'Blackout ruangan'),
                'status' => 'blackout',
                'start_label' => $this->minuteToTime($start),
                'end_label' => $this->minuteToTime($end),
                'start_minute' => $start,
                'end_minute' => $end,
            ]);
        }

        $timeline = $timeline->sortBy('start_minute')->values();

        if (!$openingHour || !$openingHour->is_open) {
            return [
                'room' => $room,
                'availability_status' => 'closed',
                'open_label' => 'Tutup',
                'occupancy_percent' => 0,
                'available_slots' => collect(),
                'timeline' => $timeline,
            ];
        }

        $openStart = $this->timeToMinute((string) $openingHour->open_time);
        $openEnd = $this->timeToMinute((string) $openingHour->close_time);
        $openDuration = max(0, $openEnd - $openStart);

        $busyIntervals = $timeline
            ->map(function (array $event) use ($openStart, $openEnd) {
                $start = max($openStart, $event['start_minute']);
                $end = min($openEnd, $event['end_minute']);

                if ($end <= $start) {
                    return null;
                }

                return ['start' => $start, 'end' => $end];
            })
            ->filter()
            ->sortBy('start')
            ->values();

        $mergedBusy = collect();
        foreach ($busyIntervals as $interval) {
            if ($mergedBusy->isEmpty()) {
                $mergedBusy->push($interval);
                continue;
            }

            $lastIndex = $mergedBusy->count() - 1;
            $last = $mergedBusy->get($lastIndex);
            if ($interval['start'] <= $last['end']) {
                $last['end'] = max($last['end'], $interval['end']);
                $mergedBusy->put($lastIndex, $last);
            } else {
                $mergedBusy->push($interval);
            }
        }

        $availableSlots = collect();
        $cursor = $openStart;
        foreach ($mergedBusy as $interval) {
            if ($interval['start'] > $cursor) {
                $availableSlots->push([
                    'start' => $this->minuteToTime($cursor),
                    'end' => $this->minuteToTime($interval['start']),
                    'duration_minutes' => $interval['start'] - $cursor,
                ]);
            }

            $cursor = max($cursor, $interval['end']);
        }

        if ($cursor < $openEnd) {
            $availableSlots->push([
                'start' => $this->minuteToTime($cursor),
                'end' => $this->minuteToTime($openEnd),
                'duration_minutes' => $openEnd - $cursor,
            ]);
        }

        $busyMinutes = $mergedBusy->sum(fn (array $interval) => $interval['end'] - $interval['start']);
        $occupancyPercent = $openDuration > 0
            ? (int) round(($busyMinutes / $openDuration) * 100)
            : 0;

        $availabilityStatus = $availableSlots->isEmpty() ? 'full' : 'available';

        return [
            'room' => $room,
            'availability_status' => $availabilityStatus,
            'open_label' => $this->minuteToTime($openStart).' - '.$this->minuteToTime($openEnd),
            'occupancy_percent' => max(0, min(100, $occupancyPercent)),
            'available_slots' => $availableSlots,
            'timeline' => $timeline,
        ];
    }

    private function timeToMinute(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
        return ($hour * 60) + $minute;
    }

    private function minuteToTime(int $minute): string
    {
        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }
}

