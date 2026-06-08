<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Daftar Pengajuan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                @if (auth()->user()->hasRole('ketua_pelaksana') || auth()->user()->hasRole('admin'))
                    <a href="{{ route('bookings.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                        + Pengajuan Baru
                    </a>
                @endif
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $booking->event_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->organization->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $booking->room->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $booking->event_date->format('d-m-Y') }}<br>
                                        {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $booking->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('bookings.show', $booking) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            Detail
                                        </a>
                                        @if ((auth()->user()->hasRole('admin') || $booking->requester_id === auth()->id()) && in_array($booking->status, ['draft', 'revision_requested']))
                                            <span class="text-gray-300 mx-1">|</span>
                                            <a href="{{ route('bookings.edit', $booking) }}" class="text-slate-600 hover:text-slate-800 font-medium">
                                                Edit
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Belum ada data pengajuan.
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
