<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomOpeningHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomOpeningHourController extends Controller
{
    /**
     * @return array<int, string>
     */
    private function dayLabels(): array
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
    }

    public function edit(Room $room): View
    {
        $existing = RoomOpeningHour::query()
            ->where('room_id', $room->id)
            ->get()
            ->keyBy('day_of_week');

        $days = collect($this->dayLabels())
            ->map(function (string $label, int $day) use ($existing) {
                $row = $existing->get($day);

                return [
                    'day' => $day,
                    'label' => $label,
                    'is_open' => (bool) ($row?->is_open ?? false),
                    'open_time' => substr((string) ($row?->open_time ?? '08:00:00'), 0, 5),
                    'close_time' => substr((string) ($row?->close_time ?? '16:00:00'), 0, 5),
                ];
            })
            ->values();

        return view('master.rooms.opening-hours', [
            'room' => $room,
            'days' => $days,
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'array'],
            'days.*.is_open' => ['nullable', 'boolean'],
            'days.*.open_time' => ['nullable', 'date_format:H:i'],
            'days.*.close_time' => ['nullable', 'date_format:H:i'],
        ]);

        $rows = [];
        foreach ($this->dayLabels() as $day => $label) {
            $dayPayload = (array) $request->input('days.'.$day, []);
            $isOpen = array_key_exists('is_open', $dayPayload) ? (bool) $dayPayload['is_open'] : false;
            $openTime = $dayPayload['open_time'] ?? null;
            $closeTime = $dayPayload['close_time'] ?? null;

            if ($isOpen) {
                if (!$openTime || !$closeTime) {
                    throw ValidationException::withMessages([
                        'days.'.$day.'.open_time' => 'Jam buka/tutup wajib diisi untuk '.$label.'.',
                    ]);
                }

                if ($closeTime <= $openTime) {
                    throw ValidationException::withMessages([
                        'days.'.$day.'.close_time' => 'Jam tutup harus lebih besar dari jam buka untuk '.$label.'.',
                    ]);
                }
            }

            $rows[] = [
                'room_id' => $room->id,
                'day_of_week' => $day,
                'open_time' => $isOpen ? $openTime.':00' : '00:00:00',
                'close_time' => $isOpen ? $closeTime.':00' : '00:00:00',
                'is_open' => $isOpen,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        DB::transaction(function () use ($rows) {
            RoomOpeningHour::query()->upsert(
                $rows,
                ['room_id', 'day_of_week'],
                ['open_time', 'close_time', 'is_open', 'updated_at']
            );
        });

        return redirect()
            ->route('master.rooms.opening-hours.edit', $room)
            ->with('status', 'Jam operasional ruangan berhasil diperbarui.');
    }
}

