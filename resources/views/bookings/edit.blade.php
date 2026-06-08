<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Pengajuan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
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

                    @if ($booking->status === 'revision_requested' && $booking->last_revision_note)
                        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            <p class="font-semibold">Catatan revisi</p>
                            <p class="mt-1">{{ $booking->last_revision_note }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('bookings.update', $booking) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label for="organization_id" class="block text-sm font-medium text-gray-700">Organisasi</label>
                                <select id="organization_id" name="organization_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Pilih Organisasi</option>
                                    @foreach ($organizations as $organization)
                                        <option value="{{ $organization->id }}" @selected(old('organization_id', $booking->organization_id) == $organization->id)>
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
                                        <option value="{{ $room->id }}" @selected(old('room_id', $booking->room_id) == $room->id)>
                                            {{ $room->name }} ({{ $room->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label for="event_name" class="block text-sm font-medium text-gray-700">Nama Kegiatan</label>
                                <input id="event_name" type="text" name="event_name" value="{{ old('event_name', $booking->event_name) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label for="participant_count" class="block text-sm font-medium text-gray-700">Jumlah Peserta</label>
                                <input id="participant_count" type="number" min="1" name="participant_count" value="{{ old('participant_count', $booking->participant_count) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div>
                            <label for="event_description" class="block text-sm font-medium text-gray-700">Deskripsi Kegiatan</label>
                            <textarea id="event_description" name="event_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('event_description', $booking->event_description) }}</textarea>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            <div>
                                <label for="event_date" class="block text-sm font-medium text-gray-700">Tanggal</label>
                                <input id="event_date" type="date" name="event_date" value="{{ old('event_date', $booking->event_date->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                                <input id="start_time" type="time" name="start_time" value="{{ old('start_time', substr($booking->start_time, 0, 5)) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                                <input id="end_time" type="time" name="end_time" value="{{ old('end_time', substr($booking->end_time, 0, 5)) }}" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Lampiran Dokumen</h3>
                            <p class="text-xs text-gray-500 mb-3">Tambah file baru (PDF/DOC/DOCX, maksimal 10 MB per file, maksimal 5 file per upload).</p>
                            <input
                                type="file"
                                name="attachments[]"
                                multiple
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="block w-full rounded-md border-gray-300 text-sm"
                            >

                            @if ($booking->attachments->isNotEmpty())
                                <div class="mt-4 rounded-md border border-gray-200 p-4">
                                    <p class="text-sm font-semibold text-gray-800 mb-2">Lampiran Saat Ini</p>
                                    <div class="space-y-2">
                                        @foreach ($booking->attachments as $attachment)
                                            <label class="flex items-start gap-3 text-sm text-gray-700">
                                                <input type="checkbox" name="remove_attachment_ids[]" value="{{ $attachment->id }}" class="mt-0.5 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                                <span>
                                                    <a href="{{ route('bookings.attachments.download', [$booking, $attachment]) }}" class="font-medium text-indigo-700 hover:text-indigo-900">
                                                        {{ $attachment->original_name }}
                                                    </a>
                                                    <span class="block text-xs text-gray-500">
                                                        upload: {{ $attachment->created_at?->format('d-m-Y H:i') }} oleh {{ $attachment->uploader?->name ?? '-' }}
                                                    </span>
                                                    <span class="block text-xs text-gray-500">Centang untuk hapus saat simpan.</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Daftar Alat</h3>
                            @php
                                $oldRows = old('items');
                                $initialRows = is_array($oldRows)
                                    ? $oldRows
                                    : collect($selectedItemQty)
                                        ->map(fn ($qty, $itemId) => ['item_id' => $itemId, 'requested_qty' => $qty])
                                        ->values()
                                        ->all();
                            @endphp
                            @include('bookings.partials.item-request-builder', [
                                'items' => $items,
                                'initialRows' => $initialRows,
                                'builderId' => 'edit-item-builder',
                            ])
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('bookings.show', $booking) }}" class="text-sm text-gray-600 hover:text-gray-900">Kembali ke detail</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
