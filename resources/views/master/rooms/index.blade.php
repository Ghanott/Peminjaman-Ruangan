<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Master Ruangan
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
                        <form method="GET" action="{{ route('master.rooms.index') }}" class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="q" class="block text-xs uppercase tracking-wide text-slate-500">Cari Ruangan</label>
                                <input id="q" name="q" value="{{ $search }}" placeholder="nama/kode/lokasi" class="mt-1 rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                Cari
                            </button>
                        </form>

                        <a href="{{ route('master.rooms.create') }}" class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-600">
                            Tambah Ruangan
                        </a>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-4">Kode</th>
                                    <th class="py-2 pr-4">Nama</th>
                                    <th class="py-2 pr-4">Lokasi</th>
                                    <th class="py-2 pr-4">Kapasitas</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Jumlah Pengajuan</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse ($rooms as $room)
                                    <tr class="text-slate-700">
                                        <td class="py-3 pr-4 font-semibold">{{ $room->code }}</td>
                                        <td class="py-3 pr-4">{{ $room->name }}</td>
                                        <td class="py-3 pr-4">{{ $room->location ?: '-' }}</td>
                                        <td class="py-3 pr-4">{{ $room->capacity ?: '-' }}</td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $room->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $room->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4">{{ $room->bookings_count }}</td>
                                        <td class="py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('master.rooms.edit', $room) }}" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    Edit
                                                </a>
                                                <a href="{{ route('master.rooms.opening-hours.edit', $room) }}" class="inline-flex items-center rounded-md border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 hover:bg-teal-100">
                                                    Jam Operasional
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-sm text-slate-500">Belum ada data ruangan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($rooms->hasPages())
                        <div class="mt-4 border-t border-slate-100 pt-3">
                            {{ $rooms->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
