<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Dashboard Peminjaman Ruangan
        </h2>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-100 via-stone-50 to-slate-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="grid lg:grid-cols-[1.3fr_1fr]">
                    <div class="p-6 md:p-8 bg-[linear-gradient(120deg,#0f766e_0%,#155e75_100%)] text-white">
                        <p class="text-xs uppercase tracking-[0.2em] text-teal-100">Sistem Peminjaman Ruangan</p>
                        <h3 class="mt-2 text-2xl md:text-3xl font-bold">Halo, {{ auth()->user()->name }}</h3>
                        <p class="mt-3 text-sm md:text-base text-teal-50 max-w-xl">
                            Monitor pengajuan, antrean persetujuan, dan jadwal kegiatan dalam satu tampilan yang jelas.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @forelse ($roles as $role)
                                <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/30">
                                    {{ $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role)) }}
                                </span>
                            @empty
                                <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/30">
                                    Role belum terdefinisi
                                </span>
                            @endforelse
                        </div>
                    </div>

                    <div class="p-6 md:p-8 bg-slate-50">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Ringkasan Cepat</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs text-slate-500">Pengajuan Saya</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $myStatusCounts['total'] }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs text-slate-500">Agenda 7 Hari</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $myUpcomingCount }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs text-slate-500">Antrean Persetujuan</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $pendingApprovalCount }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs text-slate-500">Urgent (H-1/Hari H)</p>
                                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $urgentApprovalCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Draft</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $myStatusCounts['draft'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Revisi</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">{{ $myStatusCounts['revision'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Dalam Proses</p>
                    <p class="mt-2 text-3xl font-bold text-cyan-700">{{ $myStatusCounts['in_progress'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Approved Final</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $myStatusCounts['approved'] }}</p>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Komposisi Status Pengajuan Saya</h3>
                    <p class="mt-1 text-sm text-slate-500">Distribusi status untuk memudahkan prioritas tindak lanjut.</p>
                    @php
                        $statusRows = [
                            ['label' => 'Draft', 'count' => $myStatusCounts['draft'], 'bar' => 'bg-slate-500'],
                            ['label' => 'Revisi', 'count' => $myStatusCounts['revision'], 'bar' => 'bg-amber-500'],
                            ['label' => 'Dalam Proses', 'count' => $myStatusCounts['in_progress'], 'bar' => 'bg-cyan-500'],
                            ['label' => 'Approved Final', 'count' => $myStatusCounts['approved'], 'bar' => 'bg-emerald-500'],
                            ['label' => 'Rejected', 'count' => $myStatusCounts['rejected'], 'bar' => 'bg-rose-500'],
                        ];
                        $statusMax = max(1, collect($statusRows)->max('count'));
                    @endphp
                    <div class="mt-5 space-y-4">
                        @foreach ($statusRows as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                                    <span class="text-slate-500">{{ $row['count'] }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $row['bar'] }}" style="width: {{ ($row['count'] / $statusMax) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Antrean Persetujuan per Role</h3>
                    <p class="mt-1 text-sm text-slate-500">Membantu memetakan beban approval di role yang kamu pegang.</p>

                    @if ($pendingByRole->isNotEmpty())
                        @php
                            $roleMax = max(1, $pendingByRole->max());
                        @endphp
                        <div class="mt-5 space-y-4">
                            @foreach ($pendingByRole as $role => $count)
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-700">{{ $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role)) }}</span>
                                        <span class="text-slate-500">{{ $count }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-teal-600" style="width: {{ ($count / $roleMax) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                            Tidak ada role approval aktif pada akun ini.
                        </div>
                    @endif
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Agenda Saya Berikutnya</h3>
                            <p class="mt-1 text-sm text-slate-500">Jadwal kegiatan terdekat yang sudah diajukan.</p>
                        </div>
                        <a href="{{ route('bookings.index') }}" class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-4">Tanggal</th>
                                    <th class="py-2 pr-4">Kegiatan</th>
                                    <th class="py-2 pr-4">Ruangan</th>
                                    <th class="py-2 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse ($myUpcomingBookings as $booking)
                                    <tr class="text-slate-700">
                                        <td class="py-3 pr-4 whitespace-nowrap">
                                            {{ $booking->event_date->format('d M Y') }}<br>
                                            <span class="text-xs text-slate-500">{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}</span>
                                        </td>
                                        <td class="py-3 pr-4">
                                            <a class="font-semibold text-slate-800 hover:text-teal-700" href="{{ route('bookings.show', $booking) }}">
                                                {{ $booking->event_name }}
                                            </a>
                                            <p class="text-xs text-slate-500">{{ $booking->organization?->name }}</p>
                                        </td>
                                        <td class="py-3 pr-4">{{ $booking->room?->name }} ({{ $booking->room?->code }})</td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {{ $booking->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-sm text-slate-500">
                                            Belum ada agenda terdekat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Aksi Cepat</h3>
                    <p class="mt-1 text-sm text-slate-500">Shortcut ke halaman yang paling sering dipakai.</p>

                    <div class="mt-5 grid gap-3">
                        @if ($canManageMasterData)
                            <a href="{{ route('master.rooms.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Kelola Master Data
                            </a>
                        @endif
                        <a href="{{ route('rooms.availability') }}" class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                            Cek Ketersediaan Ruangan
                        </a>
                        @if ($canCreateBooking)
                            <a href="{{ route('bookings.create') }}" class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-600">
                                Buat Pengajuan Baru
                            </a>
                        @endif
                        @if ($canOpenApprovalQueue)
                            <a href="{{ route('bookings.approval-queue') }}" class="inline-flex items-center justify-center rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-800 hover:bg-teal-100">
                                Buka Approval Queue
                            </a>
                        @endif
                        @if ($canOpenKasubbagQueue)
                            <a href="{{ route('bookings.kasubbag-queue') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-semibold text-cyan-800 hover:bg-cyan-100">
                                Buka Kasubbag Queue
                            </a>
                        @endif
                        <a href="{{ route('bookings.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            Daftar Pengajuan
                        </a>
                    </div>

                    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Snapshot Sistem</p>
                        <div class="mt-3 space-y-2 text-sm text-slate-700">
                            <p class="flex items-center justify-between">
                                <span>Penggunaan Hari Ini</span>
                                <span class="font-semibold">{{ $todayUsageCount }}</span>
                            </p>
                            <p class="flex items-center justify-between">
                                <span>Final Approved Bulan Ini</span>
                                <span class="font-semibold">{{ $finalizedThisMonthCount }}</span>
                            </p>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
