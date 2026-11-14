<?php

namespace App\Modules\DCP\Services;

use App\Models\Organization;
use App\Modules\DCP\Contracts\DCPOrderable;
use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Data\DCPResponseData;
use App\Modules\DCP\Data\DCPStatusResult;
use App\Modules\DCP\Data\DCPSubmissionResult;
use App\Modules\DCP\DCPApi;
use App\Modules\DCP\Enums\DCPEventType;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Enums\DCPRemoteStatus;
use App\Modules\DCP\Enums\DCPResponseError;
use App\Modules\DCP\Enums\DCPSubmissionOutcome;
use App\Modules\DCP\Events\DCPLifecycleEvent;
use App\Modules\DCP\Models\DCPConnection;
use App\Modules\DCP\Models\DCPOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

readonly class DCPOrderProcessingService
{
    /**
     * @param DCPOrderService $orders
     * @param DCPLogService $log
     */
    public function __construct(private DCPOrderService $orders, private DCPLogService $log)
    {
    }

    /**
     * @return void
     */
    public function sendApprovedOrders(): void
    {
        if (!Config::get('dcp.enabled')) {
            return;
        }

        $this->requireOutsideTransaction();
        $this->recoverAbandonedClaims();
        $query = DCPOrder::query()->where('state', DCPOrderState::APPROVED)->whereNotNull('dcp_order_bulk_id');
        $lastId = (clone $query)->max('id') ?? 0;

        foreach ($query->where('id', '<=', $lastId)->lazyById(Config::get('dcp.processing_chunk_size')) as $order) {
            try {
                $this->sendOrder($order);
            } catch (Throwable $exception) {
                $this->log->failure(
                    DCPLogService::OPERATION_SEND,
                    $order->organization_id,
                    $order->id,
                    'processing_failed',
                    $exception,
                );
            }
        }
    }

    /**
     * @return void
     */
    public function updateStatuses(): void
    {
        if (!Config::get('dcp.enabled')) {
            return;
        }

        $this->requireOutsideTransaction();
        $query = DCPOrder::query()->where('state', DCPOrderState::ACCEPTED)->whereNotNull('order_id_dcp');
        $lastId = (clone $query)->max('id') ?? 0;

        foreach ($query->where('id', '<=', $lastId)->lazyById(Config::get('dcp.processing_chunk_size')) as $order) {
            try {
                Cache::lock('dcp_status_' . $order->id, $this->claimTimeout())->get(function () use ($order) {
                    $this->updateOrderStatus($order->id);
                });
            } catch (Throwable $exception) {
                $this->log->failure(
                    DCPLogService::OPERATION_STATUS,
                    $order->organization_id,
                    $order->id,
                    'status_failed',
                    $exception,
                );
            }
        }
    }

    /**
     * @param DCPOrder $candidate
     * @throws Throwable
     * @return void
     */
    private function sendOrder(DCPOrder $candidate): void
    {
        $order = $this->claimOrder($candidate);

        if ($order === null) {
            return;
        }

        $result = null;

        try {
            $result = $this->api($order->dcp_connection)->createOrder(
                $order->order_id,
                DCPOrderData::fromOrder($order),
            );
            $this->storeSubmission($order, $result);
        } catch (Throwable $exception) {
            $reason = $result === null ? 'submission_failed' : 'persistence_failed';
            $diagnosticId = $this->log->failure(
                DCPLogService::OPERATION_SEND,
                $order->organization_id,
                $order->id,
                $reason,
                $exception,
                $result?->response,
            );

            $this->markUnknown($order, $reason, $diagnosticId);
        }
    }

    /**
     * @param DCPOrder $candidate
     * @throws Throwable
     * @return DCPOrder|null
     */
    private function claimOrder(DCPOrder $candidate): ?DCPOrder
    {
        return DB::transaction(function () use ($candidate): ?DCPOrder {
            $organization = Organization::query()->lockForUpdate()->findOrFail($candidate->organization_id);
            $reference = DCPOrder::query()->where('organization_id', $organization->id)->find($candidate->id);

            if ($reference === null || $reference->state !== DCPOrderState::APPROVED) {
                return null;
            }

            $source = in_array($reference->orderable_type, Config::get('dcp.orderable_types'), true) ?
                $reference->orderable()->lockForUpdate()->first() : null;

            /** @var DCPConnection|null $connection */
            $connection = $reference->dcp_connection()->lockForUpdate()->first();
            $order = DCPOrder::query()->where('organization_id', $organization->id)
                ->lockForUpdate()->findOrFail($reference->id);

            if ($order->state !== DCPOrderState::APPROVED || $order->dcp_order_bulk_id === null ||
                !$connection?->canSend()) {
                return null;
            }

            if (!$source instanceof DCPOrderable || $connection->id !== $order->dcp_connection_id) {
                $this->blockOrder($order, 'source_unavailable');

                return null;
            }

            try {
                $data = DCPOrderData::fromOrder($order);
            } catch (Throwable $exception) {
                $this->blockOrder($order, 'invalid_snapshot', $exception);

                return null;
            }

            if ($this->orders->sourceValidationErrors($organization, $source, $data->card->passCode) !== []) {
                $this->blockOrder($order, 'source_unavailable');

                return null;
            }

            $this->api($connection);

            $order->update(['state' => DCPOrderState::SENDING, 'sending_at' => Carbon::now()]);
            $order->setRelation('dcp_connection', $connection);
            DCPLifecycleEvent::dispatch($order, DCPEventType::SENDING);

            return $order;
        });
    }

    /**
     * @param DCPOrder $order
     * @param string $reason
     * @param Throwable|null $exception
     * @return void
     */
    private function blockOrder(DCPOrder $order, string $reason, ?Throwable $exception = null): void
    {
        $diagnosticId = $this->log->failure(
            DCPLogService::OPERATION_SEND,
            $order->organization_id,
            $order->id,
            $reason,
            $exception,
        );

        $order->update([
            'state' => DCPOrderState::BLOCKED,
            'status_message' => trans('dcp::processing.' . $reason),
            'diagnostic_id' => $diagnosticId,
        ]);

        DCPLifecycleEvent::dispatch($order, DCPEventType::BLOCKED);
    }

    /**
     * @param DCPOrder $submitted
     * @param DCPSubmissionResult $result
     * @throws Throwable
     * @return void
     */
    private function storeSubmission(DCPOrder $submitted, DCPSubmissionResult $result): void
    {
        DB::transaction(function () use ($submitted, $result) {
            $order = DCPOrder::query()->lockForUpdate()->findOrFail($submitted->id);

            if (!in_array($order->state, [DCPOrderState::SENDING, DCPOrderState::UNKNOWN], true)) {
                return;
            }

            $response = $result->response;
            $previousState = $order->state;
            $state = match ($result->outcome) {
                DCPSubmissionOutcome::ACCEPTED => DCPRemoteStatus::orderState($response->statusId),
                DCPSubmissionOutcome::REJECTED => DCPOrderState::REJECTED,
                DCPSubmissionOutcome::UNKNOWN => DCPOrderState::UNKNOWN,
            };

            $diagnosticId = $result->error ? $this->log->failure(
                DCPLogService::OPERATION_SEND,
                $order->organization_id,
                $order->id,
                $result->error->value,
                response: $response,
            ) : null;

            $order->fill([
                'state' => $state,
                'submission_response' => $response->toArray(),
                'status_message' => $this->responseMessage($response, $result->error),
                'diagnostic_id' => $diagnosticId,
            ]);

            if ($result->outcome === DCPSubmissionOutcome::ACCEPTED) {
                $order->fill([
                    'order_id_dcp' => $response->remoteId,
                    'status_id' => $response->statusId,
                    'submitted_at' => Carbon::now(),
                    'shipped_at' => $response->shippedAt,
                ]);
            }

            $order->save();

            if ($previousState !== $state) {
                DCPLifecycleEvent::dispatch($order, match ($result->outcome) {
                    DCPSubmissionOutcome::ACCEPTED => DCPEventType::ACCEPTED,
                    DCPSubmissionOutcome::REJECTED => DCPEventType::REJECTED,
                    DCPSubmissionOutcome::UNKNOWN => DCPEventType::UNKNOWN,
                });
            }
        });
    }

    /**
     * @return void
     */
    private function recoverAbandonedClaims(): void
    {
        $cutoff = Carbon::now()->subSeconds($this->claimTimeout());
        $query = DCPOrder::query()->where('state', DCPOrderState::SENDING)
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('sending_at', '<=', $cutoff)->orWhereNull('sending_at');
            });

        foreach ($query->lazyById(Config::get('dcp.processing_chunk_size')) as $order) {
            try {
                $this->markUnknown($order, 'abandoned', cutoff: $cutoff);
            } catch (Throwable $exception) {
                $this->log->failure(
                    DCPLogService::OPERATION_RECOVER,
                    $order->organization_id,
                    $order->id,
                    'recovery_failed',
                    $exception,
                );
            }
        }
    }

    /**
     * @param DCPOrder $candidate
     * @param string $reason
     * @param string|null $diagnosticId
     * @param Carbon|null $cutoff
     * @throws Throwable
     * @return void
     */
    private function markUnknown(
        DCPOrder $candidate,
        string $reason,
        ?string $diagnosticId = null,
        ?Carbon $cutoff = null,
    ): void {
        DB::transaction(function () use ($candidate, $reason, $diagnosticId, $cutoff) {
            $order = DCPOrder::query()->lockForUpdate()->findOrFail($candidate->id);

            if ($order->state !== DCPOrderState::SENDING || ($cutoff && $order->sending_at?->gt($cutoff))) {
                return;
            }

            $order->update([
                'state' => DCPOrderState::UNKNOWN,
                'status_message' => trans('dcp::processing.' . $reason),
                'diagnostic_id' => $diagnosticId ?? $this->log->failure(
                    DCPLogService::OPERATION_RECOVER,
                    $order->organization_id,
                    $order->id,
                    $reason,
                ),
            ]);

            DCPLifecycleEvent::dispatch($order, DCPEventType::UNKNOWN);
        });
    }

    /**
     * @param int $orderId
     * @throws Throwable
     * @return void
     */
    private function updateOrderStatus(int $orderId): void
    {
        $order = DCPOrder::query()->with('dcp_connection')->findOrFail($orderId);

        if ($order->state !== DCPOrderState::ACCEPTED || $order->order_id_dcp === null ||
            !$order->dcp_connection->canSend()) {
            return;
        }

        $startedAt = Carbon::now();

        try {
            $result = $this->api($order->dcp_connection)->getOrder($order->order_id, $order->order_id_dcp);
        } catch (Throwable $exception) {
            $diagnosticId = $this->log->failure(
                DCPLogService::OPERATION_STATUS,
                $order->organization_id,
                $order->id,
                'status_failed',
                $exception,
            );

            $result = new DCPStatusResult(new DCPResponseData(), DCPResponseError::TRANSPORT);
        }

        $this->storeStatus($order, $result, $startedAt, $diagnosticId ?? null);
    }

    /**
     * @param DCPOrder $checked
     * @param DCPStatusResult $result
     * @param Carbon $startedAt
     * @param string|null $diagnosticId
     * @throws Throwable
     * @return void
     */
    private function storeStatus(
        DCPOrder $checked,
        DCPStatusResult $result,
        Carbon $startedAt,
        ?string $diagnosticId,
    ): void {
        DB::transaction(function () use ($checked, $result, $startedAt, $diagnosticId) {
            $order = DCPOrder::query()->lockForUpdate()->findOrFail($checked->id);

            if ($order->state !== DCPOrderState::ACCEPTED || $order->order_id_dcp !== $checked->order_id_dcp ||
                $order->last_status_checked_at?->gt($startedAt)) {
                return;
            }

            $response = $result->response;
            $previousStatusId = $order->status_id;
            $order->fill([
                'last_status_checked_at' => Carbon::now(),
                'status_response' => $response->toArray(),
            ]);

            if ($result->error !== null) {
                $order->fill([
                    'status_message' => trans('dcp::processing.status_failed') . ' ' .
                        trans('dcp::processing.errors.' . $result->error->value),
                    'diagnostic_id' => $diagnosticId ?? $this->log->failure(
                        DCPLogService::OPERATION_STATUS,
                        $order->organization_id,
                        $order->id,
                        $result->error->value,
                        response: $response,
                    ),
                ])->save();

                return;
            }

            $order->fill([
                'state' => DCPRemoteStatus::orderState($response->statusId),
                'status_id' => $response->statusId,
                'status_message' => $response->message,
                'shipped_at' => $response->shippedAt ?? $order->shipped_at,
                'diagnostic_id' => null,
            ]);

            $changed = $order->isDirty(['state', 'status_id', 'shipped_at']);
            $order->save();

            if ($changed) {
                DCPLifecycleEvent::dispatch($order, DCPEventType::STATUS_CHANGED, previousStatusId: $previousStatusId);
            }
        });
    }

    /**
     * @param DCPConnection $connection
     * @return DCPApi
     */
    private function api(DCPConnection $connection): DCPApi
    {
        return new DCPApi(
            (string) $connection->endpoint(),
            (string) $connection->username,
            (string) $connection->password,
        );
    }

    /**
     * @return int
     */
    private function claimTimeout(): int
    {
        return max(
            (int) Config::get('dcp.sending_claim_timeout'),
            (int) Config::get('dcp.timeout') + (int) Config::get('dcp.connect_timeout') + 60,
        );
    }

    /**
     * @param DCPResponseData $response
     * @param DCPResponseError|null $error
     * @return string
     */
    private function responseMessage(DCPResponseData $response, ?DCPResponseError $error): string
    {
        return implode(' ', array_filter([$response->message, ...$response->errors])) ?: trans(
            $error ? 'dcp::processing.errors.' . $error->value : 'dcp::processing.accepted',
        );
    }

    /**
     * @throws LogicException
     * @return void
     */
    private function requireOutsideTransaction(): void
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('DCP processing must run outside an existing database transaction.');
        }
    }
}
