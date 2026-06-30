<?php

use App\Http\Controllers\Api\TicketTierController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('ticket-tiers/{ticket_tier}/publish', [TicketTierController::class, 'publish'])
        ->name('ticket-tiers.publish');
    Route::apiResource('ticket-tiers', TicketTierController::class);
});
