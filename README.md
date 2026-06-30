# Ticket Tier API Assessment

This project implements the ticket tier CRUD API slice from the take-home brief using Laravel 10, Pest, `spatie/laravel-data`, `spatie/laravel-query-builder`, and `spatie/laravel-permission`.

## Purpose

The feature models ticket tiers for an event platform. Each ticket tier belongs to an event and stores:

- `name`
- `price`
- `quantity`
- `sales_channels`
- `is_published`
- `is_active`

In addition to standard CRUD endpoints, the API includes a custom `publish` action that flips `is_published` to `true`.

## How The Code Is Structured

### Models and database

- [Event.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Models/Event.php) is a minimal supporting model because `ticket_tiers.event_id` must reference an existing event.
- [TicketTier.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Models/TicketTier.php) contains:
  - fillable fields
  - casts for price, booleans, quantity, and `sales_channels`
  - query scopes: `forEvent`, `active`, and `availableOnChannel`
- The main migrations are in [database/migrations](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/database/migrations):
  - `create_events_table`
  - `create_permission_tables`
  - `create_ticket_tiers_table`

### Validation with Laravel Data

The request payloads are handled by:

- [CreateTicketTierData.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Data/TicketTiers/CreateTicketTierData.php)
- [UpdateTicketTierData.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Data/TicketTiers/UpdateTicketTierData.php)

These classes are responsible for:

- typing the incoming payload
- validating request data through `rules(ValidationContext $context)`
- using `Optional` for fields that are not required on update

Important validation behavior:

- `event_id` must exist in `events`
- `name` must be unique per event, not globally
- `price` must be `>= 0`
- `quantity` must be `>= 1`
- `sales_channels` may be `null` or an array of allowed values

### Action classes

All write logic lives in dedicated actions under [app/Actions/TicketTiers](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Actions/TicketTiers):

- `CreateTicketTierAction`
- `UpdateTicketTierAction`
- `DeleteTicketTierAction`
- `PublishTicketTierAction`

Each action has exactly one public `execute(...)` method, which keeps controller methods thin and keeps write behavior isolated.

### Controller

[TicketTierController.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Http/Controllers/Api/TicketTierController.php) coordinates the feature.

Controller responsibilities:

- authorize first
- open a DB transaction for every write
- call the correct action
- return a consistent API envelope
- rethrow `ValidationException`
- log unexpected exceptions and return one generic translated error message

The `index` method uses Spatie Query Builder for:

- filtering by `event_id`
- filtering by `channel` through the `availableOnChannel` scope
- sorting by `name`, `price`, and `created_at`
- including the related `event`
- paginating with `per_page`

### API resources

Two resources shape API responses:

- [TicketTierResource.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Http/Resources/TicketTierResource.php)
- [ApiMessageResource.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Http/Resources/ApiMessageResource.php)

`TicketTierResource` always exposes `id`, then uses `whenHas` for other attributes and `whenLoaded` for the related event. `ApiMessageResource` wraps every mutating response in a common envelope:

```json
{
  "message": "Ticket tier created successfully.",
  "data": {
    "...": "..."
  }
}
```

### Authorization

- [TicketTierPolicy.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Policies/TicketTierPolicy.php) checks permissions using `hasPermissionTo(...)`
- [AuthServiceProvider.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/app/Providers/AuthServiceProvider.php) registers the policy
- API routes in [routes/api.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/routes/api.php) are protected with `auth:sanctum`

Permission names assumed by this implementation:

- `ticket-tiers.viewAny`
- `ticket-tiers.view`
- `ticket-tiers.create`
- `ticket-tiers.update`
- `ticket-tiers.delete`

The publish action reuses the `update` permission.

## Request Flow

For `POST /api/ticket-tiers` the flow is:

1. The route hits `TicketTierController@store`
2. `auth:sanctum` authenticates the user
3. The controller calls `$this->authorize('create', TicketTier::class)`
4. Laravel resolves `CreateTicketTierData`, which validates the request
5. A DB transaction starts
6. `CreateTicketTierAction` creates the record
7. The response is returned through `ApiMessageResource` + `TicketTierResource`

The same pattern is used for `update`, `destroy`, and `publish`.

## API Endpoints

- `GET /api/ticket-tiers`
- `POST /api/ticket-tiers`
- `GET /api/ticket-tiers/{ticket_tier}`
- `PUT/PATCH /api/ticket-tiers/{ticket_tier}`
- `DELETE /api/ticket-tiers/{ticket_tier}`
- `POST /api/ticket-tiers/{ticket_tier}/publish`

## Tests

Feature coverage lives in [TicketTierApiTest.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/tests/Feature/TicketTierApiTest.php).

The tests verify:

- storing a ticket tier
- name uniqueness per event
- channel filtering behavior
- publishing a tier
- soft deleting a tier and excluding it from index results

The test suite uses:

- Pest
- in-memory SQLite from [phpunit.xml](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/phpunit.xml)
- `Sanctum::actingAs(...)` for authenticated API requests
- Spatie permission bootstrapping in `beforeEach(...)`

## Assumptions

- Allowed sales channels are `web`, `box_office`, and `mobile`
- `sales_channels = null` means the tier is available on all channels
- A minimal `events` table/model was added because the brief required a valid `event_id` foreign key
- This repository was scaffolded from a fresh Laravel 10 app because the brief explicitly asked for a fresh project

## Run Locally

1. Install dependencies:
   `composer install`
2. Run the test suite:
   `php artisan test`

## Supporting Files

- Postman collection: [postman_collection.json](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/postman_collection.json)
- Localization strings: [lang/en/ticket-tiers.php](/C:/Users/USER/Desktop/Potential-Clients/Aliensoft-Laravel-Interview/lang/en/ticket-tiers.php)

## Notes

- All user-facing success and custom validation strings are wrapped in `__()`
- Mutating endpoints return a consistent response envelope
- Unexpected write errors are logged before a translated generic error is returned
