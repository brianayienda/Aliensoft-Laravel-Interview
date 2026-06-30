<?php

use App\Models\Event;
use App\Models\TicketTier;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'ticket-tiers.viewAny',
        'ticket-tiers.view',
        'ticket-tiers.create',
        'ticket-tiers.update',
        'ticket-tiers.delete',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'ticket-tiers.viewAny',
        'ticket-tiers.view',
        'ticket-tiers.create',
        'ticket-tiers.update',
        'ticket-tiers.delete',
    ]);

    Sanctum::actingAs($this->user);
});

it('stores a ticket tier', function (): void {
    $event = Event::factory()->create();

    $response = $this->postJson(route('ticket-tiers.store'), [
        'event_id' => $event->id,
        'name' => 'VIP',
        'price' => 99.99,
        'quantity' => 150,
        'sales_channels' => ['web', 'box_office'],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', __('ticket-tiers.messages.created'))
        ->assertJsonPath('data.event_id', $event->id)
        ->assertJsonPath('data.name', 'VIP')
        ->assertJsonPath('data.price', '99.99')
        ->assertJsonPath('data.quantity', 150)
        ->assertJsonPath('data.sales_channels.0', 'web')
        ->assertJsonPath('data.sales_channels.1', 'box_office')
        ->assertJsonPath('data.is_published', false)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('ticket_tiers', [
        'event_id' => $event->id,
        'name' => 'VIP',
        'price' => 99.99,
        'quantity' => 150,
        'is_published' => 0,
        'is_active' => 1,
    ]);
});

it('enforces name uniqueness per event while allowing the same name across events', function (): void {
    $firstEvent = Event::factory()->create();
    $secondEvent = Event::factory()->create();

    TicketTier::factory()->create([
        'event_id' => $firstEvent->id,
        'name' => 'Early Bird',
    ]);

    $this->postJson(route('ticket-tiers.store'), [
        'event_id' => $firstEvent->id,
        'name' => 'Early Bird',
        'price' => 20,
        'quantity' => 50,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name']);

    $this->postJson(route('ticket-tiers.store'), [
        'event_id' => $secondEvent->id,
        'name' => 'Early Bird',
        'price' => 20,
        'quantity' => 50,
    ])->assertCreated();

    $this->assertDatabaseCount('ticket_tiers', 2);
});

it('filters ticket tiers by channel including global tiers and excluding mismatched restricted tiers', function (): void {
    $event = Event::factory()->create();

    $globalTier = TicketTier::factory()->create([
        'event_id' => $event->id,
        'sales_channels' => null,
    ]);
    $matchingTier = TicketTier::factory()->create([
        'event_id' => $event->id,
        'sales_channels' => ['web'],
    ]);
    $otherTier = TicketTier::factory()->create([
        'event_id' => $event->id,
        'sales_channels' => ['box_office'],
    ]);

    $response = $this->getJson(route('ticket-tiers.index', [
        'filter' => [
            'channel' => 'web',
        ],
    ]));

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($globalTier->id, $matchingTier->id);
    expect($ids)->not->toContain($otherTier->id);
});

it('publishes a ticket tier', function (): void {
    $ticketTier = TicketTier::factory()->create([
        'is_published' => false,
    ]);

    $this->postJson(route('ticket-tiers.publish', $ticketTier))
        ->assertOk()
        ->assertJsonPath('message', __('ticket-tiers.messages.published'))
        ->assertJsonPath('data.id', $ticketTier->id)
        ->assertJsonPath('data.is_published', true);

    $this->assertDatabaseHas('ticket_tiers', [
        'id' => $ticketTier->id,
        'is_published' => 1,
    ]);
});

it('soft deletes a ticket tier and excludes it from the index', function (): void {
    $ticketTier = TicketTier::factory()->create();

    $this->deleteJson(route('ticket-tiers.destroy', $ticketTier))
        ->assertOk()
        ->assertJsonPath('message', __('ticket-tiers.messages.deleted'))
        ->assertJsonPath('data.id', $ticketTier->id);

    $this->assertSoftDeleted('ticket_tiers', [
        'id' => $ticketTier->id,
    ]);

    $indexResponse = $this->getJson(route('ticket-tiers.index'));

    $indexResponse->assertOk();

    $ids = collect($indexResponse->json('data'))->pluck('id');

    expect($ids)->not->toContain($ticketTier->id);
});
