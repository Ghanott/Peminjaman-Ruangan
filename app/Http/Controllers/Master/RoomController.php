<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreRoomRequest;
use App\Http\Requests\Master\UpdateRoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $rooms = Room::query()
            ->withCount('bookings')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('location', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('master.rooms.index', [
            'rooms' => $rooms,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('master.rooms.create');
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $room = Room::query()->create($data);

        return redirect()
            ->route('master.rooms.edit', $room)
            ->with('status', 'Ruangan berhasil ditambahkan.');
    }

    public function edit(Room $room): View
    {
        return view('master.rooms.edit', [
            'room' => $room,
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $room->update($data);

        return redirect()
            ->route('master.rooms.edit', $room)
            ->with('status', 'Ruangan berhasil diperbarui.');
    }
}

