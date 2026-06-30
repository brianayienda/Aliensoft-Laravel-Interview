<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketTier>
 */
class TicketTierFactory extends Factory
{
    protected $model = TicketTier::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->words(2, true),
            'price' => fake()->randomFloat(2, 0, 500),
            'quantity' => fake()->numberBetween(1, 500),
            'sales_channels' => null,
            'is_published' => false,
            'is_active' => true,
        ];
    }
}
