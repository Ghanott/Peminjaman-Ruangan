<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking && (bool) $this->user()?->can('processAction', $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject,request_revision'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.booking_item_id' => ['required', 'exists:booking_items,id'],
            'items.*.status_item' => ['nullable', 'in:approved,partial,crossed'],
            'items.*.approved_qty' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'items.*.verification_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
