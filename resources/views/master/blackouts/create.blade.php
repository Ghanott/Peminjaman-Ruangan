<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Tambah Jadwal Blackout
        </h2>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-100 via-stone-50 to-slate-100">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('master.partials.tabs')

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                <form method="POST" action="{{ route('master.blackouts.store') }}">
                    @csrf
                    @php($submitLabel = 'Simpan Blackout')
                    @include('master.blackouts._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

