<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreItemRequest;
use App\Http\Requests\Master\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $items = Item::query()
            ->withCount('bookingItems')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('unit', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('master.items.index', [
            'items' => $items,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('master.items.create');
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $item = Item::query()->create($data);

        return redirect()
            ->route('master.items.edit', $item)
            ->with('status', 'Item inventaris berhasil ditambahkan.');
    }

    public function edit(Item $item): View
    {
        return view('master.items.edit', [
            'item' => $item,
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $item->update($data);

        return redirect()
            ->route('master.items.edit', $item)
            ->with('status', 'Item inventaris berhasil diperbarui.');
    }
}

