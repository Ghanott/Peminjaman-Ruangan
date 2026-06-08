<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Jam Operasional Ruangan
        </h2>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-100 via-stone-50 to-slate-100">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('master.partials.tabs')

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                    <p class="font-semibold mb-2">Ada data yang perlu diperbaiki:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                <div class="mb-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Ruangan</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $room->name }} ({{ $room->code }})</p>
                </div>

                <form method="POST" action="{{ route('master.rooms.opening-hours.update', $room) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-4">Hari</th>
                                    <th class="py-2 pr-4">Buka?</th>
                                    <th class="py-2 pr-4">Jam Buka</th>
                                    <th class="py-2 pr-4">Jam Tutup</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($days as $day)
                                    <tr>
                                        <td class="py-3 pr-4 text-sm font-semibold text-slate-800">{{ $day['label'] }}</td>
                                        <td class="py-3 pr-4">
                                            <input type="hidden" name="days[{{ $day['day'] }}][is_open]" value="0">
                                            <input
                                                type="checkbox"
                                                name="days[{{ $day['day'] }}][is_open]"
                                                value="1"
                                                @checked((bool) old("days.{$day['day']}.is_open", $day['is_open']))
                                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                            >
                                        </td>
                                        <td class="py-3 pr-4">
                                            <input
                                                type="time"
                                                name="days[{{ $day['day'] }}][open_time]"
                                                value="{{ old("days.{$day['day']}.open_time", $day['open_time']) }}"
                                                class="w-36 rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
                                            >
                                        </td>
                                        <td class="py-3 pr-4">
                                            <input
                                                type="time"
                                                name="days[{{ $day['day'] }}][close_time]"
                                                value="{{ old("days.{$day['day']}.close_time", $day['close_time']) }}"
                                                class="w-36 rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-600">
                            Simpan Jam Operasional
                        </button>
                        <a href="{{ route('master.rooms.edit', $room) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                            Kembali ke Ruangan
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

