<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Pengajuan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-semibold mb-2">Ada data yang perlu diperbaiki:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <dl class="grid gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Kegiatan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->event_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->status }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Nomor SPR</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->sprDocument?->spr_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Penandatangan SPR</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->sprDocument?->signer?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">File SPR</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($booking->sprDocument && $booking->sprDocument->pdf_path !== 'pending')
                                    Tersedia
                                @elseif ($booking->sprDocument)
                                    Menunggu generate PDF
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Organisasi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->organization->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Ruangan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->room->name }} ({{ $booking->room->code }})</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Jadwal</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $booking->event_date->format('d-m-Y') }},
                                {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Ketua Pelaksana</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->requester->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Jumlah Peserta</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->participant_count ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Role Step Aktif</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->current_role_key ?? '-' }}</dd>
                        </div>
                    </dl>

                    @if ($booking->event_description)
                        <div class="mt-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Deskripsi</p>
                            <p class="mt-1 text-sm text-gray-700">{{ $booking->event_description }}</p>
                        </div>
                    @endif

                    @if ($booking->last_revision_note)
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            <p class="font-semibold">Catatan Revisi Terakhir</p>
                            <p class="mt-1">{{ $booking->last_revision_note }}</p>
                        </div>
                    @endif

                    @if ($canEdit)
                        <div class="mt-4">
                            <a href="{{ route('bookings.edit', $booking) }}" class="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600">
                                Edit Data Pengajuan
                            </a>
                        </div>
                    @endif

                    @if ($canReviewAsKasubbag)
                        <div class="mt-3">
                            <a href="{{ route('bookings.kasubbag-review', $booking) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                                Buka Review Kasubbag
                            </a>
                        </div>
                    @endif

                    @if ($booking->sprDocument)
                        <div class="mt-3">
                            <a href="{{ route('bookings.spr.download', $booking) }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                                Download SPR (PDF)
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Alat yang Diajukan</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Nama Alat</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Qty Diajukan</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Qty Disetujui</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($booking->bookingItems as $bookingItem)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $bookingItem->item->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $bookingItem->requested_qty }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $bookingItem->approved_qty ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $bookingItem->status_item }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $bookingItem->verification_note ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-4 text-sm text-center text-gray-500">
                                            Tidak ada alat yang diajukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Lampiran Dokumen</h3>
                    <div class="space-y-3">
                        @forelse ($booking->attachments as $attachment)
                            <div class="rounded-md border border-gray-200 p-3 flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('bookings.attachments.download', [$booking, $attachment]) }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                                        {{ $attachment->original_name }}
                                    </a>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ number_format($attachment->size_bytes / 1024, 1) }} KB |
                                        upload oleh {{ $attachment->uploader?->name ?? '-' }} |
                                        {{ $attachment->created_at?->format('d-m-Y H:i') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('bookings.attachments.download', [$booking, $attachment]) }}" class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                        Download
                                    </a>
                                    @if ($canManageAttachments)
                                        <form method="POST" action="{{ route('bookings.attachments.destroy', [$booking, $attachment]) }}" onsubmit="return confirm('Hapus lampiran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada lampiran dokumen.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($isCurrentApprover && $booking->current_role_key)
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Aksi Approval</h3>
                        <form method="POST" action="{{ route('bookings.action', $booking) }}" class="space-y-4">
                            @csrf

                            <div>
                                <label for="action" class="block text-sm font-medium text-gray-700">Aksi</label>
                                <select id="action" name="action" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="approve">Approve</option>
                                    <option value="request_revision">Request Revisi</option>
                                    <option value="reject">Reject</option>
                                </select>
                            </div>

                            <div>
                                <label for="note" class="block text-sm font-medium text-gray-700">Catatan (wajib untuk reject/revisi)</label>
                                <textarea id="note" name="note" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('note') }}</textarea>
                            </div>

                            @if ($booking->current_role_key === 'kasubbag' && $booking->bookingItems->count() > 0)
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">Verifikasi Item oleh Kasubbag</h4>
                                    <p class="text-xs text-gray-500 mb-3">Bagian ini dipakai saat aksi approve untuk menentukan item disetujui, parsial, atau disilang.</p>
                                    <div class="overflow-x-auto border rounded-md">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Item</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Qty Ajukan</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Keputusan</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Qty Disetujui</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Catatan</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach ($booking->bookingItems as $index => $bookingItem)
                                                    <tr>
                                                        <td class="px-3 py-2 text-sm text-gray-900">
                                                            {{ $bookingItem->item->name }}
                                                            <input type="hidden" name="items[{{ $index }}][booking_item_id]" value="{{ $bookingItem->id }}">
                                                        </td>
                                                        <td class="px-3 py-2 text-sm text-gray-700">{{ $bookingItem->requested_qty }}</td>
                                                        <td class="px-3 py-2">
                                                            <select name="items[{{ $index }}][status_item]" class="rounded-md border-gray-300 text-sm">
                                                                <option value="approved" @selected(old("items.$index.status_item", $bookingItem->status_item === 'pending' ? 'approved' : $bookingItem->status_item) === 'approved')>approved</option>
                                                                <option value="partial" @selected(old("items.$index.status_item", $bookingItem->status_item) === 'partial')>partial</option>
                                                                <option value="crossed" @selected(old("items.$index.status_item", $bookingItem->status_item) === 'crossed')>crossed</option>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                name="items[{{ $index }}][approved_qty]"
                                                                value="{{ old("items.$index.approved_qty", $bookingItem->approved_qty ?? $bookingItem->requested_qty) }}"
                                                                class="w-24 rounded-md border-gray-300 text-sm"
                                                            >
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <input
                                                                type="text"
                                                                name="items[{{ $index }}][verification_note]"
                                                                value="{{ old("items.$index.verification_note", $bookingItem->verification_note) }}"
                                                                class="w-full rounded-md border-gray-300 text-sm"
                                                                placeholder="alasan silang / catatan"
                                                            >
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                                Proses Aksi
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($canResubmit)
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Resubmit Setelah Revisi</h3>
                        <form method="POST" action="{{ route('bookings.resubmit', $booking) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="resubmit_note" class="block text-sm font-medium text-gray-700">Catatan Resubmit (opsional)</label>
                                <textarea id="resubmit_note" name="note" rows="2" class="mt-1 block w-full rounded-md border-gray-300">{{ old('note') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                                Kirim Ulang Pengajuan
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Timeline Approval</h3>
                        <div class="space-y-3">
                            @forelse ($booking->approvals as $approval)
                                <div class="rounded-md border p-3">
                                    <p class="text-sm font-semibold text-gray-800">{{ $approval->action }} - {{ $approval->role_key }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        oleh {{ $approval->actor?->name ?? '-' }} pada {{ optional($approval->acted_at)->format('d-m-Y H:i') }}
                                    </p>
                                    @if ($approval->note)
                                        <p class="text-sm text-gray-700 mt-2">{{ $approval->note }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Belum ada riwayat approval.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Riwayat Revisi</h3>
                        <div class="space-y-3">
                            @forelse ($booking->revisions as $revision)
                                <div class="rounded-md border p-3">
                                    <p class="text-sm font-semibold text-gray-800">Round {{ $revision->round_no }} (step {{ $revision->requested_from_step }})</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        diminta oleh {{ $revision->requester?->name ?? '-' }}
                                    </p>
                                    <p class="text-sm text-gray-700 mt-2">{{ $revision->note }}</p>
                                    <p class="text-xs text-gray-500 mt-2">
                                        status: {{ $revision->resolved_at ? 'resolved' : 'pending' }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Belum ada riwayat revisi.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Audit Aktivitas</h3>
                    <form method="GET" action="{{ route('bookings.show', $booking) }}" class="grid gap-3 md:grid-cols-4 mb-4">
                        <div>
                            <label for="log_event" class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Event</label>
                            <select id="log_event" name="log_event" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua Event</option>
                                @foreach ($auditEventLabels as $key => $label)
                                    <option value="{{ $key }}" @selected($selectedLogEvent === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="log_actor" class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Aktor</label>
                            <select id="log_actor" name="log_actor" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua Aktor</option>
                                @foreach ($logActors as $actor)
                                    <option value="{{ $actor->id }}" @selected((int) $selectedLogActor === (int) $actor->id)>{{ $actor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="log_from" class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Dari Tanggal</label>
                            <input id="log_from" type="date" name="log_from" value="{{ $selectedLogFrom }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label for="log_to" class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Sampai Tanggal</label>
                            <input id="log_to" type="date" name="log_to" value="{{ $selectedLogTo }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-4 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex items-center rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-600">
                                Terapkan Filter
                            </button>
                            <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">
                                Reset
                            </a>
                        </div>
                    </form>
                    <div class="space-y-3">
                        @php
                            $eventLabels = $auditEventLabels;
                        @endphp
                        @forelse ($activityLogs as $log)
                            @php
                                $label = $eventLabels[$log->event_key] ?? str_replace('_', ' ', $log->event_key);
                            @endphp
                            <div class="rounded-md border p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-800">{{ $label }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ optional($log->acted_at)->format('d-m-Y H:i') }}
                                    </p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    oleh {{ $log->actor?->name ?? 'Sistem' }}
                                    @if ($log->action_label)
                                        | aksi: {{ $log->action_label }}
                                    @endif
                                </p>
                                @if ($log->old_status || $log->new_status)
                                    <p class="text-sm text-gray-700 mt-2">
                                        status: {{ $log->old_status ?? '-' }} -> {{ $log->new_status ?? '-' }}
                                    </p>
                                @endif
                                @if ($log->note)
                                    <p class="text-sm text-gray-700 mt-2">{{ $log->note }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada audit aktivitas.</p>
                        @endforelse
                    </div>
                    @if ($activityLogs->hasPages())
                        <div class="mt-4 border-t pt-3">
                            {{ $activityLogs->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <a href="{{ route('bookings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Kembali ke daftar pengajuan</a>
            </div>
        </div>
    </div>
</x-app-layout>
