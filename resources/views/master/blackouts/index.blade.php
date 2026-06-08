<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Jadwal Blackout Ruangan
        </h2>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-100 via-stone-50 to-slate-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('master.partials.tabs')

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <form method="GET" action="{{ route('master.blackouts.index') }}" class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="room_id" class="block text-xs uppercase tracking-wide text-slate-500">Ruangan</label>
                                <select id="room_id" name="room_id" class="mt-1 rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                    <option value="">Semua Ruangan</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" @selected((int) $selectedRoomId === (int) $room->id)>
                                            {{ $room->name }} ({{ $room->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="date" class="block text-xs uppercase tracking-wide text-slate-500">Tanggal</label>
                                <input id="date" name="date" type="date" value="{{ $selectedDate }}" class="mt-1 rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                Filter
                            </button>
                        </form>

                        <a href="{{ route('master.blackouts.create') }}" class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-600">
                            Tambah Blackout
                        </a>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-4">Ruangan</th>
                                    <th class="py-2 pr-4">Mulai</th>
                                    <th class="py-2 pr-4">Selesai</th>
                                    <th class="py-2 pr-4">Alasan</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse ($blackouts as $blackout)
                                    <tr class="text-slate-700">
                                        <td class="py-3 pr-4 font-semibold">
                                            {{ $blackout->room?->name }} ({{ $blackout->room?->code }})
                                        </td>
                                        <td class="py-3 pr-4">{{ $blackout->starts_at->format('d-m-Y H:i') }}</td>
                                        <td class="py-3 pr-4">{{ $blackout->ends_at->format('d-m-Y H:i') }}</td>
                                        <td class="py-3 pr-4">{{ $blackout->reason ?: '-' }}</td>
                                        <td class="py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('master.blackouts.edit', $blackout) }}" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('master.blackouts.destroy', $blackout) }}" onsubmit="return confirm('Hapus blackout ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-sm text-slate-500">Belum ada jadwal blackout.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($blackouts->hasPages())
                        <div class="mt-4 border-t border-slate-100 pt-3">
                            {{ $blackouts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

