<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Approval Queue
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <p class="text-sm text-gray-500">Filter antrean berdasarkan role approver.</p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('bookings.approval-queue.export', ['role' => $selectedRole, 'priority' => $selectedPriority, 'format' => 'summary']) }}"
                                class="inline-flex items-center rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-600"
                            >
                                CSV Ringkas
                            </a>
                            <a
                                href="{{ route('bookings.approval-queue.export', ['role' => $selectedRole, 'priority' => $selectedPriority, 'format' => 'detail']) }}"
                                class="inline-flex items-center rounded-md bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-600"
                            >
                                CSV Detail Item
                            </a>
                            <a
                                href="{{ route('bookings.approval-queue.export.xlsx', ['role' => $selectedRole, 'priority' => $selectedPriority, 'format' => 'summary']) }}"
                                class="inline-flex items-center rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-600"
                            >
                                XLSX Ringkas
                            </a>
                            <a
                                href="{{ route('bookings.approval-queue.export.xlsx', ['role' => $selectedRole, 'priority' => $selectedPriority, 'format' => 'detail']) }}"
                                class="inline-flex items-center rounded-md bg-teal-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-600"
                            >
                                XLSX Detail Item
                            </a>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Urutan otomatis: <span class="font-semibold text-red-600">overdue</span> -> <span class="font-semibold text-amber-600">urgent</span> (hari ini/besok) -> normal.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($availableRoles as $role)
                            <a
                                href="{{ route('bookings.approval-queue', ['role' => $role, 'priority' => $selectedPriority]) }}"
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedRole === $role ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                            >
                                {{ $role }} ({{ $pendingCounts[$role] ?? 0 }})
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            href="{{ route('bookings.approval-queue', ['role' => $selectedRole, 'priority' => 'all']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'all' ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            All ({{ $priorityCounts['all'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.approval-queue', ['role' => $selectedRole, 'priority' => 'overdue']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'overdue' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                        >
                            Overdue ({{ $priorityCounts['overdue'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.approval-queue', ['role' => $selectedRole, 'priority' => 'urgent']) }}"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedPriority === 'urgent' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800 hover:bg-amber-200' }}"
                        >
                            Urgent ({{ $priorityCounts['urgent'] ?? 0 }})
                        </a>
                        <a
                            href="{{ route('bookings.approval-queue', ['role' => $selectedRole, 'priority' => 'normal']) }}"
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Organisasi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Ruangan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Jadwal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Step Aktif</th>
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
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->organization->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->room->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $booking->event_date->format('d-m-Y') }}<br>
                                        {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->current_role_key }}</td>
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
                                        @if ($booking->current_role_key === 'kasubbag')
                                            <a href="{{ route('bookings.kasubbag-review', $booking) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                                Verifikasi
                                            </a>
                                        @else
                                            <a href="{{ route('bookings.show', $booking) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                                Proses
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Tidak ada pengajuan yang menunggu persetujuan di antrean ini.
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
