<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company' => $this->company,
            'label' => $this->label(),
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'open_tickets_count' => $this->whenHas('open_tickets_count'),
        ];
    }
}
