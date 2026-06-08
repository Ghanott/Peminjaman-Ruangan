<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Jadwal Ketersediaan Ruangan
        </h2>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-100 via-stone-50 to-slate-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                <form method="GET" action="{{ route('rooms.availability') }}" class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                    <div>
                        <label for="date" class="block text-xs uppercase tracking-wide text-slate-500">Tanggal</label>
                        <input
                            id="date"
                            name="date"
                            type="date"
                            value="{{ $selectedDate->toDateString() }}"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                    </div>
                    <div>
                        <label for="room_id" class="block text-xs uppercase tracking-wide text-slate-500">Filter Ruangan</label>
                        <select
                            id="room_id"
                            name="room_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            <option value="">Semua Ruangan Aktif</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected((int) $selectedRoomId === (int) $room->id)>
                                    {{ $room->name }} ({{ $room->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-600">
                            Cek Jadwal
                        </button>
                        <a href="{{ route('rooms.availability', ['date' => now()->toDateString()]) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Hari Ini
                        </a>
                    </div>
                </form>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Ruangan</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['total'] }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">Tersedia</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-800">{{ $summary['available'] }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-amber-700">Penuh</p>
                    <p class="mt-2 text-3xl font-bold text-amber-800">{{ $summary['full'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Tutup</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800">{{ $summary['closed'] }}</p>
                </article>
            </section>

            <section class="space-y-4">
                @forelse ($cards as $card)
                    @php
                        $statusStyles = [
                            'available' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'full' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'closed' => 'bg-slate-100 text-slate-700 border-slate-200',
                        ];
                        $statusLabels = [
                            'available' => 'Tersedia',
                            'full' => 'Penuh',
                            'closed' => 'Tutup',
                        ];
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="grid lg:grid-cols-[1fr_1.2fr]">
                            <div class="p-6 border-b lg:border-b-0 lg:border-r border-slate-200">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">{{ $card['room']->name }}</h3>
                                        <p class="text-sm text-slate-500">{{ $card['room']->code }} @if($card['room']->location)- {{ $card['room']->location }}@endif</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusStyles[$card['availability_status']] }}">
                                        {{ $statusLabels[$card['availability_status']] }}
                                    </span>
                                </div>

                                <dl class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                        <dt class="text-[11px] uppercase tracking-wide text-slate-500">Jam Operasional</dt>
                                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $card['open_label'] }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                        <dt class="text-[11px] uppercase tracking-wide text-slate-500">Kapasitas</dt>
                                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $card['room']->capacity ?? '-' }} orang</dd>
                                    </div>
                                </dl>

                                <div class="mt-5">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-700">Tingkat Keterisian</span>
                                        <span class="text-slate-500">{{ $card['occupancy_percent'] }}%</span>
                                    </div>
                                    <div class="mt-2 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-teal-600" style="width: {{ $card['occupancy_percent'] }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 bg-slate-50/60">
                                <h4 class="text-sm font-semibold text-slate-900">Slot Tersedia</h4>
                                @if ($card['available_slots']->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($card['available_slots'] as $slot)
                                            <a
                                                href="{{ route('bookings.create', [
                                                    'event_date' => $selectedDate->toDateString(),
                                                    'room_id' => $card['room']->id,
                                                    'start_time' => $slot['start'],
                                                    'end_time' => $slot['end'],
                                                    'from' => 'availability',
                                                ]) }}"
                                                class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-100"
                                            >
                                                {{ $slot['start'] }} - {{ $slot['end'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Klik slot untuk auto-isi form pengajuan.</p>
                                @else
                                    <p class="mt-3 text-sm text-slate-500">Tidak ada slot tersedia pada jam operasional hari ini.</p>
                                @endif

                                <h4 class="mt-6 text-sm font-semibold text-slate-900">Timeline Pemakaian / Blokir</h4>
                                @if ($card['timeline']->isNotEmpty())
                                    <div class="mt-3 space-y-2">
                                        @foreach ($card['timeline'] as $event)
                                            <div class="rounded-xl border p-3 {{ $event['type'] === 'booking' ? 'border-teal-200 bg-teal-50/50' : 'border-rose-200 bg-rose-50/70' }}">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-800">{{ $event['title'] }}</p>
                                                        <p class="text-xs text-slate-500">{{ $event['subtitle'] }}</p>
                                                    </div>
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $event['type'] === 'booking' ? 'bg-teal-100 text-teal-700' : 'bg-rose-100 text-rose-700' }}">
                                                        {{ $event['type'] === 'booking' ? 'Pengajuan' : 'Blackout' }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-xs font-medium text-slate-600">{{ $event['start_label'] }} - {{ $event['end_label'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 text-sm text-slate-500">Belum ada pengajuan atau blackout pada tanggal ini.</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">
                        Ruangan tidak ditemukan untuk filter yang dipilih.
                    </article>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
