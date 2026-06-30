<?php

namespace App\Http\Controllers\Api;

use App\Actions\TicketTiers\CreateTicketTierAction;
use App\Actions\TicketTiers\DeleteTicketTierAction;
use App\Actions\TicketTiers\PublishTicketTierAction;
use App\Actions\TicketTiers\UpdateTicketTierAction;
use App\Data\TicketTiers\CreateTicketTierData;
use App\Data\TicketTiers\UpdateTicketTierData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiMessageResource;
use App\Http\Resources\TicketTierResource;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TicketTierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TicketTier::class);

        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $ticketTiers = QueryBuilder::for(TicketTier::query()->latest())
            ->allowedFilters([
                AllowedFilter::exact('event_id'),
                AllowedFilter::callback('channel', function (Builder $query, mixed $value): void {
                    $query->availableOnChannel((string) $value);
                }),
            ])
            ->allowedSorts(['name', 'price', 'created_at'])
            ->allowedIncludes(['event'])
            ->paginate($perPage)
            ->appends($request->query());

        return TicketTierResource::collection($ticketTiers);
    }

    public function store(CreateTicketTierData $data, CreateTicketTierAction $action)
    {
        $this->authorize('create', TicketTier::class);

        DB::beginTransaction();

        try {
            $ticketTier = $action->execute($data);

            DB::commit();

            return (new ApiMessageResource([
                'message' => __('ticket-tiers.messages.created'),
                'data' => new TicketTierResource($ticketTier),
            ]))->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to create ticket tier.', [
                'exception' => $exception,
            ]);

            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, __('ticket-tiers.messages.unexpected_error'));
        }
    }

    public function show(TicketTier $ticketTier)
    {
        $this->authorize('view', $ticketTier);

        return new TicketTierResource($ticketTier);
    }

    public function update(
        UpdateTicketTierData $data,
        TicketTier $ticketTier,
        UpdateTicketTierAction $action,
    ) {
        $this->authorize('update', $ticketTier);

        DB::beginTransaction();

        try {
            $ticketTier = $action->execute($ticketTier, $data);

            DB::commit();

            return new ApiMessageResource([
                'message' => __('ticket-tiers.messages.updated'),
                'data' => new TicketTierResource($ticketTier),
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to update ticket tier.', [
                'ticket_tier_id' => $ticketTier->id,
                'exception' => $exception,
            ]);

            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, __('ticket-tiers.messages.unexpected_error'));
        }
    }

    public function destroy(TicketTier $ticketTier, DeleteTicketTierAction $action)
    {
        $this->authorize('delete', $ticketTier);

        DB::beginTransaction();

        try {
            $action->execute($ticketTier);

            DB::commit();

            return new ApiMessageResource([
                'message' => __('ticket-tiers.messages.deleted'),
                'data' => new TicketTierResource($ticketTier),
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to delete ticket tier.', [
                'ticket_tier_id' => $ticketTier->id,
                'exception' => $exception,
            ]);

            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, __('ticket-tiers.messages.unexpected_error'));
        }
    }

    public function publish(TicketTier $ticketTier, PublishTicketTierAction $action)
    {
        $this->authorize('update', $ticketTier);

        DB::beginTransaction();

        try {
            $ticketTier = $action->execute($ticketTier);

            DB::commit();

            return new ApiMessageResource([
                'message' => __('ticket-tiers.messages.published'),
                'data' => new TicketTierResource($ticketTier),
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to publish ticket tier.', [
                'ticket_tier_id' => $ticketTier->id,
                'exception' => $exception,
            ]);

            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, __('ticket-tiers.messages.unexpected_error'));
        }
    }
}
