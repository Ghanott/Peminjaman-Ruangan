@if ($errors->any())
    <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
        <p class="font-semibold mb-2">Ada data yang perlu diperbaiki:</p>
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="code" class="block text-sm font-medium text-slate-700">Kode Ruangan</label>
        <input id="code" name="code" value="{{ old('code', $room->code ?? '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Nama Ruangan</label>
        <input id="name" name="name" value="{{ old('name', $room->name ?? '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
</div>

<div class="grid gap-5 md:grid-cols-2 mt-5">
    <div>
        <label for="location" class="block text-sm font-medium text-slate-700">Lokasi</label>
        <input id="location" name="location" value="{{ old('location', $room->location ?? '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
    <div>
        <label for="capacity" class="block text-sm font-medium text-slate-700">Kapasitas</label>
        <input id="capacity" name="capacity" type="number" min="1" max="5000" value="{{ old('capacity', $room->capacity ?? '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
</div>

<div class="mt-5">
    <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi</label>
    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">{{ old('description', $room->description ?? '') }}</textarea>
</div>

<div class="mt-5">
    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input
            type="hidden"
            name="is_active"
            value="0"
        >
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked((bool) old('is_active', $room->is_active ?? true))
            class="rounded border-slate-300 text-teal-600 focus:ring-teal-600"
        >
        Ruangan aktif dipakai
    </label>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-600">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('master.rooms.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
</div>

