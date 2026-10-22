<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Used for both create and update.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->withoutTrashed()],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pick a customer from the list.',
        ];
    }
}
