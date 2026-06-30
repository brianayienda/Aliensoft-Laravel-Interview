<?php

namespace App\Data\TicketTiers;

use App\Models\TicketTier;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CreateTicketTierData extends Data
{
    public function __construct(
        public int $event_id,
        public string $name,
        public float $price,
        public int $quantity,
        public array|Optional|null $sales_channels,
        public bool|Optional $is_published,
        public bool|Optional $is_active,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_tiers', 'name')
                    ->where(fn ($query) => $query
                        ->where('event_id', $context->fullPayload['event_id'] ?? null)
                        ->whereNull('deleted_at')),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'sales_channels' => ['nullable', 'array'],
            'sales_channels.*' => ['string', Rule::in(TicketTier::ALLOWED_SALES_CHANNELS)],
            'is_published' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
