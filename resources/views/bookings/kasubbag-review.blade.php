<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kasubbag Review Pengajuan
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
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Pengaju</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $booking->requester->name }}</dd>
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
                    </dl>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Verifikasi Item dan Approve</h3>
                    <form method="POST" action="{{ route('bookings.action', $booking) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="action" value="approve">

                        @if ($booking->bookingItems->count() > 0)
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
                        @else
                            <p class="text-sm text-gray-500">Pengajuan ini tidak memiliki item alat, kamu bisa langsung approve.</p>
                        @endif

                        <div>
                            <label for="approve_note" class="block text-sm font-medium text-gray-700">Catatan Approve (opsional)</label>
                            <textarea id="approve_note" name="note" rows="2" class="mt-1 block w-full rounded-md border-gray-300">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                            Approve dan Lanjutkan
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Request Revisi</h3>
                        <form method="POST" action="{{ route('bookings.action', $booking) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="action" value="request_revision">
                            <div>
                                <label for="revision_note" class="block text-sm font-medium text-gray-700">Catatan Revisi</label>
                                <textarea id="revision_note" name="note" rows="3" class="mt-1 block w-full rounded-md border-gray-300" required></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500">
                                Kirim Revisi
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Reject Pengajuan</h3>
                        <form method="POST" action="{{ route('bookings.action', $booking) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <div>
                                <label for="reject_note" class="block text-sm font-medium text-gray-700">Alasan Reject</label>
                                <textarea id="reject_note" name="note" rows="3" class="mt-1 block w-full rounded-md border-gray-300" required></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>

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

            <div class="flex items-center gap-3">
                <a href="{{ route('bookings.kasubbag-queue') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Kembali ke Kasubbag Queue</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('bookings.show', $booking) }}" class="text-sm text-gray-600 hover:text-gray-800">Lihat Detail Umum</a>
            </div>
        </div>
    </div>
</x-app-layout>
