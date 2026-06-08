<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kasubbag Queue
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <p class="text-sm text-gray-500">Antrean khusus verifikasi item dan finalisasi kasubbag.</p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('bookings.kasubbag-queue.export', ['status' => $selectedStatus, 'priority' => $selectedPriority, 'format' => 'summary']) }}"
                                class="inline-flex items-center rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-600"
                            >
                                CSV Ringkas
                            </a>
                            <a
                                href="{{ route('bookings.kasubbag-queue.export', ['status' => $selectedStatus, 'priority' => $selectedPriority, 'format' => 'detail']) }}"
                                class="inline-flex items-center rounded-md bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-600"
                            >
                                CSV Detail Item
                            </a>
                            <a
                                href="{{ route('bookings.kasubbag-queue.export.xlsx', ['status' => $selectedStatus, 'priority' => $selectedPriority, 'format' => 'summary']) }}"
                                class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-600"
                            >
                                XLSX Ringkas
                            </a>
                            <a
                                href="{{ route('bookings.kasubbag-queue.export.xlsx', ['status' => $selectedStatus, 'priority' => $selectedPriority, 'format' => 'detail']) }}"
                                class="inline-flex items-center rounded-md bg-teal-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-600"
                            >
                                XLSX Detail Item
                            </a>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Urutan otomatis: <span class="font-semibold text-red-600">overdue</span> -> <span class="font-semibold text-amber-600">urgent</span> (hari ini/besok) -> normal.</p>
                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['priority' => $selectedPriority]) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ is_null($selectedStatus) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            Semua
                        </a>
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['status' => 'waiting_kasubbag_verification', 'priority' => $selectedPriority]) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedStatus === 'waiting_kasubbag_verification' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            Verifikasi ({{ $pendingCounts['waiting_kasubbag_verification'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['status' => 'waiting_kasubbag_signature', 'priority' => $selectedPriority]) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedStatus === 'waiting_kasubbag_signature' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            Signature ({{ $pendingCounts['waiting_kasubbag_signature'] ?? 0 }})
                        </a>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['status' => $selectedStatus, 'priority' => 'all']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'all' ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            All ({{ $priorityCounts['all'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['status' => $selectedStatus, 'priority' => 'overdue']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'overdue' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                        >
                            Overdue ({{ $priorityCounts['overdue'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['status' => $selectedStatus, 'priority' => 'urgent']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'urgent' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800 hover:bg-amber-200' }}"
                        >
                            Urgent ({{ $priorityCounts['urgent'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.kasubbag-queue', ['status' => $selectedStatus, 'priority' => 'normal']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'normal' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            Normal ({{ $priorityCounts['normal'] ?? 0 }})
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Kegiatan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Pengaju</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Organisasi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Jadwal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Item Diajukan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Prioritas</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($bookings as $booking)
                                @php
                                    $isOverdue = $booking->event_date->isBefore(today());
                                    $isUrgent = !$isOverdue && ($booking->event_date->isToday() || $booking->event_date->isTomorrow());
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $booking->event_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->requester->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->organization->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $booking->event_date->format('d-m-Y') }}<br>
                                        {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->bookingItems->count() }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $booking->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($isOverdue)
                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                                overdue
                                            </span>
                                        @elseif ($isUrgent)
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                                urgent
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                                normal
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('bookings.kasubbag-review', $booking) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            Verifikasi
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Tidak ada antrean kasubbag saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t">
                    {{ $bookings->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
