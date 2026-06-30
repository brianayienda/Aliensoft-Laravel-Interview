<?php

namespace App\Data\TicketTiers;

use App\Models\TicketTier;
use Closure;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateTicketTierData extends Data
{
    public function __construct(
        public int|Optional $event_id,
        public string|Optional $name,
        public float|Optional $price,
        public int|Optional $quantity,
        public array|Optional|null $sales_channels,
        public bool|Optional $is_published,
        public bool|Optional $is_active,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        /** @var TicketTier $ticketTier */
        $ticketTier = request()->route('ticket_tier');

        $eventId = $context->fullPayload['event_id'] ?? $ticketTier->event_id;
        $name = $context->fullPayload['name'] ?? $ticketTier->name;

        $uniquePerEvent = static function (string $attribute, mixed $value, Closure $fail) use ($ticketTier, $eventId, $name): void {
            $exists = TicketTier::query()
                ->where('event_id', $eventId)
                ->where('name', $name)
                ->whereKeyNot($ticketTier->id)
                ->exists();

            if ($exists) {
                $fail(__('ticket-tiers.validation.name_unique_per_event'));
            }
        };

        return [
            'event_id' => ['sometimes', 'integer', 'exists:events,id', $uniquePerEvent],
            'name' => ['sometimes', 'string', 'max:255', $uniquePerEvent],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'sales_channels' => ['nullable', 'array'],
            'sales_channels.*' => ['string', Rule::in(TicketTier::ALLOWED_SALES_CHANNELS)],
            'is_published' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
