<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Edit Ruangan
        </h2>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-100 via-stone-50 to-slate-100">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('master.partials.tabs')

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Ruangan</p>
                        <p class="text-base font-semibold text-slate-900">{{ $room->name }} ({{ $room->code }})</p>
                    </div>
                    <a href="{{ route('master.rooms.opening-hours.edit', $room) }}" class="inline-flex items-center rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-100">
                        Atur Jam Operasional
                    </a>
                </div>

                <form method="POST" action="{{ route('master.rooms.update', $room) }}">
                    @csrf
                    @method('PUT')
                    @php($submitLabel = 'Simpan Perubahan')
                    @include('master.rooms._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

