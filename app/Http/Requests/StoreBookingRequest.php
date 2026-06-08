<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'event_name' => ['required', 'string', 'max:255'],
            'event_description' => ['nullable', 'string'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'participant_count' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'action' => ['required', 'in:draft,submit'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.requested_qty' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }
}
