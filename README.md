# Ticket Tier API Assessment

This submission implements the ticket tier CRUD API slice described in the take-home brief using Laravel 10, Pest, `spatie/laravel-data`, `spatie/laravel-query-builder`, and `spatie/laravel-permission`.

## What is included

- `TicketTier` model, migration, scopes, factory, policy, resource, controller, and action classes
- `CreateTicketTierData` and `UpdateTicketTierData` with validation rules in `rules(ValidationContext $context)`
- Query Builder powered `index` endpoint with filters, sorts, includes, and `per_page` pagination
- Consistent mutating response envelope via `ApiMessageResource`
- Pest feature coverage for the five required scenarios
- A Postman collection at [postman_collection.json](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/postman_collection.json)

## Assumptions

- Permission names are:
  `ticket-tiers.viewAny`, `ticket-tiers.view`, `ticket-tiers.create`, `ticket-tiers.update`, and `ticket-tiers.delete`.
- The publish action authorizes through the same permission as update.
- Allowed sales channels are `web`, `box_office`, and `mobile`.
- Because the brief asked for a fresh Laravel project, this workspace was scaffolded from a new Laravel 10 app before the feature was added.
- API routes are protected with Laravel's `auth` middleware and the tests authenticate with the default `web` guard.
- A minimal `events` table/model was added because `ticket_tiers.event_id` must reference an existing event.

## Run locally

1. Install dependencies:
   `composer install`
2. Run the test suite:
   `php artisan test`

## API routes

- `GET /api/ticket-tiers`
- `POST /api/ticket-tiers`
- `GET /api/ticket-tiers/{ticket_tier}`
- `PUT/PATCH /api/ticket-tiers/{ticket_tier}`
- `DELETE /api/ticket-tiers/{ticket_tier}`
- `POST /api/ticket-tiers/{ticket_tier}/publish`

## Notes

- The test suite uses SQLite in memory through `phpunit.xml`.
- All user-facing success and custom validation strings are wrapped in `__()`.
