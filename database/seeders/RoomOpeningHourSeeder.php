<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomOpeningHourSeeder extends Seeder
{
    /**
     * Seed weekly opening hours for all active rooms.
     */
    public function run(): void
    {
        $now = now();
        $roomIds = DB::table('rooms')->pluck('id');
        $rows = [];

        foreach ($roomIds as $roomId) {
            for ($day = 0; $day <= 6; $day++) {
                $isWeekday = $day >= 1 && $day <= 5;
                $isSaturday = $day === 6;

                $rows[] = [
                    'room_id' => $roomId,
                    'day_of_week' => $day,
                    'open_time' => $isWeekday ? '07:00:00' : ($isSaturday ? '08:00:00' : '00:00:00'),
                    'close_time' => $isWeekday ? '21:00:00' : ($isSaturday ? '16:00:00' : '00:00:00'),
                    'is_open' => $isWeekday || $isSaturday,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('room_opening_hours')->upsert(
            $rows,
            ['room_id', 'day_of_week'],
            ['open_time', 'close_time', 'is_open', 'updated_at']
        );
    }
}

