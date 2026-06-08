<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreRoomBlackoutRequest;
use App\Http\Requests\Master\UpdateRoomBlackoutRequest;
use App\Models\Room;
use App\Models\RoomBlackout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RoomBlackoutController extends Controller
{
    public function index(Request $request): View
    {
        $selectedRoomId = $request->integer('room_id') ?: null;
        $selectedDate = (string) $request->query('date', '');

        $query = RoomBlackout::query()
            ->with('room')
            ->orderByDesc('starts_at');

        if ($selectedRoomId) {
            $query->where('room_id', $selectedRoomId);
        }

        if ($selectedDate) {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $selectedDate)->toDateString();
                $query->whereDate('starts_at', '<=', $date)
                    ->whereDate('ends_at', '>=', $date);
            } catch (\Throwable) {
                $selectedDate = '';
            }
        }

        $blackouts = $query->paginate(12)->withQueryString();

        return view('master.blackouts.index', [
            'blackouts' => $blackouts,
            'rooms' => Room::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'selectedRoomId' => $selectedRoomId,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function create(): View
    {
        return view('master.blackouts.create', [
            'rooms' => Room::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreRoomBlackoutRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['starts_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at']);
        $data['ends_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['ends_at']);
        $data['created_by'] = $request->user()->id;

        RoomBlackout::query()->create($data);

        return redirect()
            ->route('master.blackouts.index')
            ->with('status', 'Jadwal blackout berhasil ditambahkan.');
    }

    public function edit(RoomBlackout $blackout): View
    {
        return view('master.blackouts.edit', [
            'blackout' => $blackout,
            'rooms' => Room::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateRoomBlackoutRequest $request, RoomBlackout $blackout): RedirectResponse
    {
        $data = $request->validated();
        $data['starts_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at']);
        $data['ends_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['ends_at']);

        $blackout->update($data);

        return redirect()
            ->route('master.blackouts.edit', $blackout)
            ->with('status', 'Jadwal blackout berhasil diperbarui.');
    }

    public function destroy(RoomBlackout $blackout): RedirectResponse
    {
        $blackout->delete();

        return redirect()
            ->route('master.blackouts.index')
            ->with('status', 'Jadwal blackout berhasil dihapus.');
    }
}

