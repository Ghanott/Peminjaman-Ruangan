<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Form Pengajuan Peminjaman
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($prefill['from_availability'])
                        <div class="mb-6 rounded-md border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">
                            <p class="font-semibold">Slot dipilih dari halaman ketersediaan.</p>
                            <p class="mt-1">
                                Tanggal {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $prefill['event_date'])->format('d-m-Y') }},
                                jam {{ $prefill['start_time'] }} - {{ $prefill['end_time'] }} sudah terisi otomatis.
                            </p>
                            <a
                                href="{{ route('rooms.availability', ['date' => $prefill['event_date'], 'room_id' => $prefill['room_id']]) }}"
                                class="mt-2 inline-flex items-center text-xs font-semibold text-teal-700 hover:text-teal-900"
                            >
                                Kembali ke halaman ketersediaan
                            </a>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-semibold mb-2">Ada data yang perlu diperbaiki:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('bookings.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label for="organization_id" class="block text-sm font-medium text-gray-700">Organisasi</label>
                                <select id="organization_id" name="organization_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Pilih Organisasi</option>
                                    @foreach ($organizations as $organization)
                                        <option value="{{ $organization->id }}" @selected(old('organization_id') == $organization->id)>
                                            {{ $organization->name }} ({{ strtoupper($organization->type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="room_id" class="block text-sm font-medium text-gray-700">Ruangan</label>
                                <select id="room_id" name="room_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Pilih Ruangan</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" @selected(old('room_id', $prefill['room_id']) == $room->id)>
                                            {{ $room->name }} ({{ $room->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label for="event_name" class="block text-sm font-medium text-gray-700">Nama Kegiatan</label>
                                <input id="event_name" type="text" name="event_name" value="{{ old('event_name') }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label for="participant_count" class="block text-sm font-medium text-gray-700">Jumlah Peserta</label>
                                <input id="participant_count" type="number" min="1" name="participant_count" value="{{ old('participant_count') }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div>
                            <label for="event_description" class="block text-sm font-medium text-gray-700">Deskripsi Kegiatan</label>
                            <textarea id="event_description" name="event_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('event_description') }}</textarea>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            <div>
                                <label for="event_date" class="block text-sm font-medium text-gray-700">Tanggal</label>
                                <input id="event_date" type="date" name="event_date" value="{{ old('event_date', $prefill['event_date']) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                                <input id="start_time" type="time" name="start_time" value="{{ old('start_time', $prefill['start_time']) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                                <input id="end_time" type="time" name="end_time" value="{{ old('end_time', $prefill['end_time']) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Lampiran Dokumen (opsional)</h3>
                            <p class="text-xs text-gray-500 mb-3">Format: PDF/DOC/DOCX, maksimal 10 MB per file, maksimal 5 file.</p>
                            <input
                                type="file"
                                name="attachments[]"
                                multiple
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="block w-full rounded-md border-gray-300 text-sm"
                            >
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Daftar Alat (opsional)</h3>
                            @php
                                $initialRows = old('items', []);
                            @endphp
                            @include('bookings.partials.item-request-builder', [
                                'items' => $items,
                                'initialRows' => $initialRows,
                                'builderId' => 'create-item-builder',
                            ])
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" name="action" value="draft" class="inline-flex items-center rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-600">
                                Simpan Draft
                            </button>
                            <button type="submit" name="action" value="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                                Submit Pengajuan
                            </button>
                            <a href="{{ route('bookings.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Kembali ke daftar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
