<?php

namespace App\Actions\TicketTiers;

use App\Data\TicketTiers\CreateTicketTierData;
use App\Models\TicketTier;
use Spatie\LaravelData\Optional;

class CreateTicketTierAction
{
    public function execute(CreateTicketTierData $data): TicketTier
    {
        return TicketTier::query()->create($this->attributes($data));
    }

    private function attributes(CreateTicketTierData $data): array
    {
        return [
            'event_id' => $data->event_id,
            'name' => $data->name,
            'price' => $data->price,
            'quantity' => $data->quantity,
            'sales_channels' => $data->sales_channels instanceof Optional ? null : $data->sales_channels,
            'is_published' => $data->is_published instanceof Optional ? false : $data->is_published,
            'is_active' => $data->is_active instanceof Optional ? true : $data->is_active,
        ];
    }
}
