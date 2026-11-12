<?php

namespace App\Modules\DCP\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Modules\DCP\Contracts\DCPOrderable;
use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Enums\DCPEventType;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Events\DCPLifecycleEvent;
use App\Modules\DCP\Models\DCPConnection;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Models\DCPOrderBulk;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DCPOrderService
{
    /**
     * @param Organization $organization
     * @param Model&DCPOrderable $source
     * @param DCPOrderData $data
     * @param Employee|null $employee
     * @throws Throwable
     * @return DCPOrder
     */
    public function createOrUpdateDraft(
        Organization $organization,
        Model&DCPOrderable $source,
        DCPOrderData $data,
        ?Employee $employee = null,
    ): DCPOrder {
        $data = $data->normalizedForDraft();
        $data->validate();

        if (!$source->exists || !in_array($source->getMorphClass(), Config::get('dcp.orderable_types'), true)) {
            throw ValidationException::withMessages([
                'orderable_id' => trans('dcp::validation.source_unavailable'),
            ]);
        }

        return DB::transaction(function () use ($organization, $source, $data, $employee): DCPOrder {
            $organization = Organization::query()->lockForUpdate()->findOrFail($organization->id);

            /** @var Model&DCPOrderable $source */
            $source = $source->newQuery()->lockForUpdate()->findOrFail($source->getKey());

            $this->validateSource($organization, $source, $data->card->passCode, $employee);

            $connection = $organization->dcp_connection()->lockForUpdate()->first();

            if ($connection === null) {
                throw ValidationException::withMessages([
                    'dcp_connection_id' => trans('dcp::validation.connection_missing'),
                ]);
            }

            $order = DCPOrder::query()
                ->where('orderable_type', $source->getMorphClass())
                ->where('orderable_id', $source->getKey())
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                return $this->createDraft($organization, $source, $connection, $data, $employee);
            }

            return $this->updateDraft($order, $organization, $connection, $data, $employee);
        });
    }

    /**
     * @param Organization $organization
     * @throws ValidationException
     * @return int
     */
    public function readyOrderCount(Organization $organization): int
    {
        return $this->readyOrders($organization)->count();
    }

    /**
     * @param Organization $organization
     * @param Employee|null $employee
     * @throws Throwable
     * @return DCPOrderBulk
     */
    public function approveBulk(Organization $organization, ?Employee $employee = null): DCPOrderBulk
    {
        return DB::transaction(function () use ($organization, $employee): DCPOrderBulk {
            $organization = Organization::query()->lockForUpdate()->findOrFail($organization->id);
            $this->validateEmployee($organization, $employee);
            $orders = $this->readyOrders($organization, lock: true);

            if ($orders->isEmpty()) {
                throw ValidationException::withMessages([
                    'orders' => trans('dcp::validation.no_ready_orders'),
                ]);
            }

            $approvedAt = Carbon::now();
            $bulk = DCPOrderBulk::query()->create([
                'organization_id' => $organization->id,
                'employee_id' => $employee?->id,
                'approved_at' => $approvedAt,
            ]);

            DCPOrder::query()->where('organization_id', $organization->id)->whereKey($orders->modelKeys())->update([
                'dcp_order_bulk_id' => $bulk->id,
                'state' => DCPOrderState::APPROVED,
                'approved_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ]);

            DCPLifecycleEvent::dispatch($bulk, DCPEventType::BULK_APPROVED, $employee, useRequestIdentity: true);

            return $bulk;
        });
    }

    /**
     * @param Organization $organization
     * @param Model&DCPOrderable $source
     * @param string $printCode
     * @return array<string, string>
     */
    public function sourceValidationErrors(
        Organization $organization,
        Model&DCPOrderable $source,
        string $printCode,
    ): array {
        return match (true) {
            $source->dcpOrganizationId() !== $organization->id => [
                'orderable_id' => trans('dcp::validation.source_unavailable'),
            ],
            !$source->dcpPrintRequested() => [
                'orderable_id' => trans('dcp::validation.print_not_requested'),
            ],
            $printCode !== $source->dcpPrintCode() => [
                'card.printedCardNumber' => trans('dcp::validation.code_mismatch'),
            ],
            default => [],
        };
    }

    /**
     * @param DCPOrder $order
     * @param DCPOrderData $data
     * @return array
     */
    public function changedDraftAttributes(DCPOrder $order, DCPOrderData $data): array
    {
        $attributes = $data->toAttributes();

        // MySQL may reorder JSON object keys without changing their values.
        if (Arr::sortRecursive($order->order_rows) === Arr::sortRecursive($attributes['order_rows'])) {
            unset($attributes['order_rows']);
        }

        $changedKeys = array_keys((clone $order)->fill($attributes)->getDirty());

        return Arr::only($attributes, $changedKeys);
    }

    /**
     * @param Organization $organization
     * @param bool $lock
     * @throws ValidationException
     * @return Collection<int, DCPOrder>
     */
    private function readyOrders(Organization $organization, bool $lock = false): Collection
    {
        $connection = $this->requireApprovalConnection($organization->dcp_connection);

        $query = DCPOrder::query()
            ->where('organization_id', $organization->id)
            ->where('dcp_connection_id', $connection->id)
            ->where('state', DCPOrderState::DRAFT)
            ->whereNull('dcp_order_bulk_id')
            ->whereIn('orderable_type', Config::get('dcp.orderable_types'));

        if ($lock) {
            $references = (clone $query)->get(['id', 'orderable_type', 'orderable_id']);
            $sources = $this->lockApprovalSources($references);

            /** @var DCPConnection|null $connection */
            $connection = $organization->dcp_connection()->lockForUpdate()->first();
            $connection = $this->requireApprovalConnection($connection);

            /** @var Collection<int, DCPOrder> $orders */
            $orders = $query->orderBy('id')->lockForUpdate()->get();

            foreach ($orders as $order) {
                $source = ($sources[$order->orderable_type] ?? null)?->get($order->orderable_id);
                $order->setRelation('orderable', $source);
            }
        } else {
            $orders = $query->with('orderable')->get();
        }

        return $orders->filter(fn (DCPOrder $order) => $this->isReadyForApproval($organization, $order, $connection));
    }

    /**
     * @param Collection<int, DCPOrder> $orders
     * @return array<string, Collection<int, Model>>
     */
    private function lockApprovalSources(Collection $orders): array
    {
        $sources = [];

        foreach ($orders->groupBy('orderable_type')->sortKeys() as $type => $references) {
            $class = Relation::getMorphedModel($type);

            if (!$class || !is_subclass_of($class, DCPOrderable::class)) {
                continue;
            }

            $sources[$type] = $class::query()->whereKey($references->pluck('orderable_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        }

        return $sources;
    }

    /**
     * @param Organization $organization
     * @param DCPOrder $order
     * @param DCPConnection $connection
     * @return bool
     */
    private function isReadyForApproval(
        Organization $organization,
        DCPOrder $order,
        DCPConnection $connection,
    ): bool {
        $source = $order->orderable;

        return
            $order->organization_id === $organization->id &&
            $order->dcp_connection_id === $connection->id &&
            $order->canUpdateDraft() &&
            $source instanceof DCPOrderable &&
            $this->sourceValidationErrors($organization, $source, $order->printCode()) === [];
    }

    /**
     * @param DCPConnection|null $connection
     * @throws ValidationException
     * @return DCPConnection
     */
    private function requireApprovalConnection(?DCPConnection $connection): DCPConnection
    {
        if (!$connection?->canSend()) {
            throw ValidationException::withMessages([
                'dcp_connection_id' => trans('dcp::validation.connection_unavailable'),
            ]);
        }

        return $connection;
    }

    /**
     * @param Organization $organization
     * @param Model&DCPOrderable $source
     * @param DCPConnection $connection
     * @param DCPOrderData $data
     * @param Employee|null $employee
     * @throws Throwable
     * @return DCPOrder
     */
    private function createDraft(
        Organization $organization,
        Model&DCPOrderable $source,
        DCPConnection $connection,
        DCPOrderData $data,
        ?Employee $employee,
    ): DCPOrder {
        $order = DCPOrder::query()->create([
            ...$data->toAttributes(),
            'organization_id' => $organization->id,
            'dcp_connection_id' => $connection->id,
            'orderable_type' => $source->getMorphClass(),
            'orderable_id' => $source->getKey(),
            'employee_id' => $employee?->id,
            'order_id' => (string) Str::uuid(),
            'state' => DCPOrderState::DRAFT,
        ]);

        DCPLifecycleEvent::dispatch($order, DCPEventType::CREATED, $employee, useRequestIdentity: true);

        return $order;
    }

    /**
     * @param DCPOrder $order
     * @param Organization $organization
     * @param DCPConnection $connection
     * @param DCPOrderData $data
     * @param Employee|null $employee
     * @throws Throwable
     * @return DCPOrder
     */
    private function updateDraft(
        DCPOrder $order,
        Organization $organization,
        DCPConnection $connection,
        DCPOrderData $data,
        ?Employee $employee,
    ): DCPOrder {
        if ($order->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'orderable_id' => trans('dcp::validation.source_unavailable'),
            ]);
        }

        if ($order->dcp_connection_id !== $connection->id) {
            throw ValidationException::withMessages([
                'dcp_connection_id' => trans('dcp::validation.connection_changed'),
            ]);
        }

        if (!$order->canUpdateDraft()) {
            throw ValidationException::withMessages([
                'order_id' => trans('dcp::validation.order_frozen'),
            ]);
        }

        $order->fill($this->changedDraftAttributes($order, $data));

        if ($order->isDirty()) {
            $order->employee_id = $employee?->id;
            $order->save();
            DCPLifecycleEvent::dispatch($order, DCPEventType::UPDATED, $employee, useRequestIdentity: true);
        }

        return $order;
    }

    /**
     * @param Organization $organization
     * @param Model&DCPOrderable $source
     * @param string $printCode
     * @param Employee|null $employee
     * @throws ValidationException
     * @return void
     */
    private function validateSource(
        Organization $organization,
        Model&DCPOrderable $source,
        string $printCode,
        ?Employee $employee = null,
    ): void {
        $errors = $this->sourceValidationErrors($organization, $source, $printCode);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->validateEmployee($organization, $employee);
    }

    /**
     * @param Organization $organization
     * @param Employee|null $employee
     * @throws ValidationException
     * @return void
     */
    private function validateEmployee(Organization $organization, ?Employee $employee): void
    {
        if ($employee !== null && $employee->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'employee_id' => trans('dcp::validation.employee_mismatch'),
            ]);
        }
    }
}
