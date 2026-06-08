<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Edit Jadwal Blackout
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
                <form method="POST" action="{{ route('master.blackouts.update', $blackout) }}">
                    @csrf
                    @method('PUT')
                    @php($submitLabel = 'Simpan Perubahan')
                    @include('master.blackouts._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

