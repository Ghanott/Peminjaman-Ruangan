<div class="flex flex-wrap gap-2">
    <a
        href="{{ route('master.rooms.index') }}"
        class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold {{ request()->routeIs('master.rooms.*') ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
    >
        Master Ruangan
    </a>
    <a
        href="{{ route('master.blackouts.index') }}"
        class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold {{ request()->routeIs('master.blackouts.*') ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
    >
        Jadwal Blackout
    </a>
    <a
        href="{{ route('master.items.index') }}"
        class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold {{ request()->routeIs('master.items.*') ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
    >
        Master Inventaris
    </a>
</div>

