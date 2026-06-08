@php
    $itemOptions = $items
        ->map(fn ($item) => [
            'id' => (int) $item->id,
            'name' => (string) $item->name,
            'stock_qty' => (int) $item->stock_qty,
            'unit' => (string) $item->unit,
        ])
        ->values()
        ->all();

    $rawInitialRows = $initialRows ?? [];
    $normalizedInitialRows = collect($rawInitialRows)
        ->map(function ($row) {
            return [
                'item_id' => isset($row['item_id']) ? (int) $row['item_id'] : 0,
                'requested_qty' => isset($row['requested_qty']) ? (int) $row['requested_qty'] : 0,
            ];
        })
        ->filter(fn ($row) => $row['item_id'] > 0 || $row['requested_qty'] > 0)
        ->values()
        ->all();

    $builderId = $builderId ?? 'item-request-builder';
@endphp

<div
    id="{{ $builderId }}"
    class="space-y-3"
    data-items='@json($itemOptions)'
    data-initial='@json($normalizedInitialRows)'
>
    <div class="flex items-center justify-between gap-3">
        <p class="text-xs text-gray-500">Tambah baris alat sesuai kebutuhan. Hanya baris dengan alat terpilih dan qty > 0 yang akan disimpan.</p>
        <button
            type="button"
            data-action="add-item-row"
            class="inline-flex items-center rounded-md border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100"
        >
            Tambah Alat
        </button>
    </div>

    <div class="overflow-x-auto border rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Nama Alat</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Stok</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Qty Ajukan</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody data-item-rows class="divide-y divide-gray-200">
            </tbody>
        </table>
    </div>
</div>

<template id="{{ $builderId }}-row-template">
    <tr data-item-row>
        <td class="px-4 py-2 text-sm text-gray-900">
            <select
                data-field="item-id"
                class="block w-full rounded-md border-gray-300 text-sm"
            >
                <option value="">Pilih alat</option>
            </select>
        </td>
        <td class="px-4 py-2 text-sm text-gray-600" data-field="stock-label">-</td>
        <td class="px-4 py-2">
            <input
                type="number"
                min="0"
                value="0"
                data-field="qty"
                class="w-28 rounded-md border-gray-300 text-sm"
            >
        </td>
        <td class="px-4 py-2">
            <button
                type="button"
                data-action="remove-item-row"
                class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
            >
                Hapus
            </button>
        </td>
    </tr>
</template>

<script>
(() => {
    const root = document.getElementById(@json($builderId));
    if (!root) {
        return;
    }

    const items = JSON.parse(root.dataset.items || '[]');
    const initialRows = JSON.parse(root.dataset.initial || '[]');
    const tableBody = root.querySelector('[data-item-rows]');
    const rowTemplate = document.getElementById(@json($builderId . '-row-template'));
    const addButton = root.querySelector('[data-action="add-item-row"]');
    const form = root.closest('form');

    const findItemById = (id) => items.find((item) => Number(item.id) === Number(id));

    const refreshStockLabel = (row) => {
        const select = row.querySelector('[data-field="item-id"]');
        const stockLabel = row.querySelector('[data-field="stock-label"]');
        const item = findItemById(select.value);
        stockLabel.textContent = item ? `${item.stock_qty} ${item.unit}` : '-';
    };

    const reindexRows = () => {
        Array.from(tableBody.querySelectorAll('[data-item-row]')).forEach((row, index) => {
            const itemSelect = row.querySelector('[data-field="item-id"]');
            const qtyInput = row.querySelector('[data-field="qty"]');
            itemSelect.name = `items[${index}][item_id]`;
            qtyInput.name = `items[${index}][requested_qty]`;
        });
    };

    const createRow = (data = { item_id: '', requested_qty: 0 }) => {
        const fragment = rowTemplate.content.cloneNode(true);
        const row = fragment.querySelector('[data-item-row]');
        const itemSelect = row.querySelector('[data-field="item-id"]');
        const qtyInput = row.querySelector('[data-field="qty"]');
        const removeButton = row.querySelector('[data-action="remove-item-row"]');

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            itemSelect.appendChild(option);
        });

        if (data.item_id) {
            itemSelect.value = String(data.item_id);
        }
        qtyInput.value = Number.isFinite(Number(data.requested_qty))
            ? String(Math.max(0, Number(data.requested_qty)))
            : '0';

        itemSelect.addEventListener('change', () => refreshStockLabel(row));
        removeButton.addEventListener('click', () => {
            row.remove();
            reindexRows();
        });

        tableBody.appendChild(row);
        refreshStockLabel(row);
        reindexRows();
    };

    addButton.addEventListener('click', () => createRow());

    if (initialRows.length > 0) {
        initialRows.forEach((row) => createRow(row));
    }

    if (form) {
        form.addEventListener('submit', () => {
            Array.from(tableBody.querySelectorAll('[data-item-row]')).forEach((row) => {
                const itemSelect = row.querySelector('[data-field="item-id"]');
                const qtyInput = row.querySelector('[data-field="qty"]');
                const hasItem = String(itemSelect.value || '').trim() !== '';
                const qty = Number(qtyInput.value || 0);

                if (!hasItem || qty <= 0) {
                    itemSelect.disabled = true;
                    qtyInput.disabled = true;
                }
            });
        });
    }
})();
</script>

