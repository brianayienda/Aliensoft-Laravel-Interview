<?php

namespace App\Actions\TicketTiers;

use App\Data\TicketTiers\UpdateTicketTierData;
use App\Models\TicketTier;
use Spatie\LaravelData\Optional;

class UpdateTicketTierAction
{
    public function execute(TicketTier $ticketTier, UpdateTicketTierData $data): TicketTier
    {
        $ticketTier->fill($this->attributes($data));
        $ticketTier->save();

        return $ticketTier->refresh();
    }

    private function attributes(UpdateTicketTierData $data): array
    {
        $attributes = [];

        foreach ([
            'event_id',
            'name',
            'price',
            'quantity',
            'sales_channels',
            'is_published',
            'is_active',
        ] as $field) {
            if (! ($data->{$field} instanceof Optional)) {
                $attributes[$field] = $data->{$field};
            }
        }

        return $attributes;
    }
}
