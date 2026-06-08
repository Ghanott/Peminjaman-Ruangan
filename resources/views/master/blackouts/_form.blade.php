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
        <label for="room_id" class="block text-sm font-medium text-slate-700">Ruangan</label>
        <select id="room_id" name="room_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
            <option value="">Pilih Ruangan</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" @selected((int) old('room_id', $blackout->room_id ?? null) === (int) $room->id)>
                    {{ $room->name }} ({{ $room->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="reason" class="block text-sm font-medium text-slate-700">Alasan</label>
        <input id="reason" name="reason" value="{{ old('reason', $blackout->reason ?? '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
</div>

<div class="grid gap-5 md:grid-cols-2 mt-5">
    <div>
        <label for="starts_at" class="block text-sm font-medium text-slate-700">Mulai</label>
        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($blackout) ? $blackout->starts_at->format('Y-m-d\TH:i') : '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
    <div>
        <label for="ends_at" class="block text-sm font-medium text-slate-700">Selesai</label>
        <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($blackout) ? $blackout->ends_at->format('Y-m-d\TH:i') : '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-600">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('master.blackouts.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
</div>

