<?php

namespace App\Policies;

use App\Models\TicketTier;
use App\Models\User;

class TicketTierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ticket-tiers.viewAny');
    }

    public function view(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('ticket-tiers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ticket-tiers.create');
    }

    public function update(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('ticket-tiers.update');
    }

    public function delete(User $user, TicketTier $ticketTier): bool
    {
        return $user->hasPermissionTo('ticket-tiers.delete');
    }
}
