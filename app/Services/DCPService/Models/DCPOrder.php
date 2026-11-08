<?php

namespace App\Services\DCPService\Models;

use App\Events\DCPOrders\DCPOrderCreated;
use App\Events\DCPOrders\DCPOrderFailed;
use App\Events\DCPOrders\DCPOrderIdDcpSet;
use App\Events\DCPOrders\DCPOrderPayloadReset;
use App\Events\DCPOrders\DCPOrderPayloadUpdated;
use App\Events\DCPOrders\DCPOrderRetried;
use App\Events\DCPOrders\DCPOrderSent;
use App\Events\DCPOrders\DCPOrderStatusChanged;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\DCPService\Contracts\DCPOrderable;
use App\Services\DCPService\DCPApi;
use App\Services\DCPService\DCPOrderPayloadBuilder;
use App\Services\DCPService\Responses\DCPResponse;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * App\Services\DCPService\Models\DCPOrder.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $dcp_order_bulk_id
 * @property int|null $dcp_connection_id
 * @property string $orderable_type
 * @property int $orderable_id
 * @property string $order_id
 * @property string|null $order_id_dcp
 * @property string $state
 * @property int|null $status_id
 * @property string|null $status_message
 * @property array|null $payload
 * @property \Illuminate\Support\Carbon|null $payload_edited_at
 * @property int|null $payload_edited_by_employee_id
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
 * @property string|null $response_code
 * @property array|null $response_body
 * @property \Illuminate\Support\Carbon|null $shipped_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read DCPConnection|null $dcp_connection
 * @property-read DCPOrderBulk|null $dcp_order_bulk
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Model|DCPOrderable $orderable
 * @property-read Organization $organization
 * @property-read Employee|null $payload_edited_by_employee
 * @mixin \Eloquent
 */
class DCPOrder extends Model
{
    use HasLogs;

    public const string EVENT_CREATED = 'created';
    public const string EVENT_APPROVED = 'approved';
    public const string EVENT_SENT = 'sent';
    public const string EVENT_FAILED = 'failed';
    public const string EVENT_STATUS_CHANGED = 'status_changed';
    public const string EVENT_RETRIED = 'retried';
    public const string EVENT_PAYLOAD_UPDATED = 'payload_updated';
    public const string EVENT_PAYLOAD_RESET = 'payload_reset';
    public const string EVENT_ORDER_ID_DCP_SET = 'order_id_dcp_set';

    public const string STATE_PENDING = 'pending';
    public const string STATE_SENT = 'sent';
    public const string STATE_ERROR = 'error';
    public const string STATE_CLOSED = 'closed';
    public const string STATE_CANCELED = 'canceled';

    public const int STATUS_NOT_FOUND = -1;
    public const int STATUS_WEB_API_ACCEPTED = 100;
    public const int STATUS_ACCEPTED_AT_BACKOFFICE = 200;
    public const int STATUS_PROCESSING = 300;
    public const int STATUS_PROCESSED = 400;
    public const int STATUS_SHIPPED = 500;
    public const int STATUS_CLOSED = 1000;
    public const int STATUS_CLIENT_DELETED = 9000;
    public const int STATUS_CANCELLED = 9001;
    public const int STATUS_UNPROCESSABLE = 9422;

    public const array STATUSES_CANCELED = [
        self::STATUS_CLIENT_DELETED,
        self::STATUS_CANCELLED,
        self::STATUS_UNPROCESSABLE,
    ];

    protected $table = 'dcp_orders';

    /**
     * @var string[]
     */
    protected $fillable = [
        'organization_id', 'dcp_order_bulk_id', 'dcp_connection_id', 'orderable_type',
        'orderable_id', 'order_id', 'order_id_dcp', 'state', 'status_id', 'status_message',
        'payload', 'payload_edited_at', 'payload_edited_by_employee_id', 'attempts',
        'last_attempt_at', 'response_code', 'response_body', 'shipped_at',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'status_id' => 'integer',
        'payload' => 'array',
        'payload_edited_at' => 'datetime',
        'response_body' => 'array',
        'last_attempt_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    /**
     * @return MorphTo
     * @noinspection PhpUnused
     */
    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function dcp_order_bulk(): BelongsTo
    {
        return $this->belongsTo(DCPOrderBulk::class, 'dcp_order_bulk_id');
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function dcp_connection(): BelongsTo
    {
        return $this->belongsTo(DCPConnection::class, 'dcp_connection_id');
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function payload_edited_by_employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'payload_edited_by_employee_id');
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return (bool) Config::get('dcp.enabled');
    }

    /**
     * @param Model|DCPOrderable $orderable
     * @param Organization $organization
     * @param DCPConnection $connection
     * @param Employee|null $employee
     * @return DCPOrder|null
     */
    public static function makeOrder(
        Model|DCPOrderable $orderable,
        Organization $organization,
        DCPConnection $connection,
        ?Employee $employee = null,
    ): ?DCPOrder {
        if (!self::isEnabled()) {
            return null;
        }

        $orderId = (string) Str::uuid();
        [$payload, $error] = self::makePayload($connection, $orderable, $orderId);

        $order = self::create([
            'organization_id' => $organization->id,
            'dcp_connection_id' => $connection->id,
            'orderable_type' => $orderable->getMorphClass(),
            'orderable_id' => $orderable->getKey(),
            'order_id' => $orderId,
            'payload' => $payload,
            ...$error ? [
                'state' => self::STATE_ERROR,
                'status_message' => $error,
                'attempts' => self::maxAttempts(),
            ] : [
                'state' => self::STATE_PENDING,
            ],
        ]);

        DCPOrderCreated::dispatch($order, $employee);

        return $order;
    }

    /**
     * @return Builder|DCPOrder
     */
    public static function queryToSend(): Builder|DCPOrder
    {
        return self::whereCanBeSent(self::query()->whereNotNull('dcp_order_bulk_id'))
            ->where(function (Builder $builder) {
                $builder->whereNull('last_attempt_at');
                $builder->orWhere('last_attempt_at', '<', now()->subMinutes(Config::get('dcp.attempts_interval')));
            });
    }

    /**
     * @param Builder|DCPOrder $builder
     * @return Builder|DCPOrder
     */
    public static function whereCanBeSent(Builder|DCPOrder $builder): Builder|DCPOrder
    {
        return $builder
            ->whereIn('state', [self::STATE_PENDING, self::STATE_ERROR])
            ->where('attempts', '<', self::maxAttempts());
    }

    /**
     * @param Organization|null $organization
     * @return Builder|DCPOrder
     */
    public static function queryToApprove(?Organization $organization = null): Builder|DCPOrder
    {
        return self::whereAwaitingApproval(self::query(), $organization);
    }

    /**
     * @param Builder|DCPOrder $builder
     * @param Organization|null $organization
     * @return Builder|DCPOrder
     */
    public static function whereAwaitingApproval(
        Builder|DCPOrder $builder,
        ?Organization $organization = null,
    ): Builder|DCPOrder {
        return $builder
            ->whereNull('dcp_order_bulk_id')
            ->where('state', self::STATE_PENDING)
            ->when($organization, fn (Builder $builder) => $builder->where('organization_id', $organization->id));
    }

    /**
     * @return Builder|DCPOrder
     */
    public static function queryToUpdateStatus(): Builder|DCPOrder
    {
        return self::query()
            ->where('state', self::STATE_SENT)
            ->whereNotNull('order_id_dcp');
    }

    /**
     * @return array{sent: int, failed: int}|null
     */
    public static function sendAll(): ?array
    {
        return Cache::lock('dcp_orders_send', 3600)->get(function () {
            $orders = self::queryToSend()
                ->with('orderable', 'dcp_connection')
                ->orderBy('id')
                ->get()
                ->filter(fn (DCPOrder $order) => !$order->dcp_connection || $order->dcp_connection->isActive());

            $sent = $orders->filter(fn (DCPOrder $order) => $order->sendSafe()->state === self::STATE_SENT);

            DCPOrderBulk::whereIn('id', $orders->pluck('dcp_order_bulk_id')->unique())
                ->get()
                ->each(fn (DCPOrderBulk $bulk) => $bulk->updateState());

            return [
                'sent' => $sent->count(),
                'failed' => $orders->count() - $sent->count(),
            ];
        }) ?: null;
    }

    /**
     * @return int
     */
    public static function maxAttempts(): int
    {
        return (int) Config::get('dcp.attempts');
    }

    /**
     * @return $this
     */
    public function send(): self
    {
        if ($reason = $this->blockReason()) {
            return $this->fail($reason);
        }

        $response = $this->dcp_connection->api()->createOrder($this->payload);

        $this->fill([
            'attempts' => $response->rejected() ? self::maxAttempts() : $this->attempts + 1,
            'last_attempt_at' => now(),
            ...$this->responseAttributes($response),
        ]);

        if ($response->isDuplicateOrder()) {
            $this->fill([
                'state' => self::STATE_SENT,
                'status_message' => trans('dcp.errors.duplicate_order'),
            ]);
        } elseif ($response->success()) {
            $this->fill([
                'state' => self::STATE_SENT,
                'order_id_dcp' => $response->orderIdDcp(),
                'status_id' => $response->statusId(),
            ]);
        } else {
            $this->fill(['state' => self::STATE_ERROR]);
        }

        $this->save();

        if ($this->state === self::STATE_SENT) {
            $this->notifyOrderable();

            DCPOrderSent::dispatch($this, null, [
                'dcp_order_duplicate' => $response->isDuplicateOrder(),
            ]);
        } else {
            DCPOrderFailed::dispatch($this);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function sendSafe(): self
    {
        try {
            return $this->send();
        } catch (Throwable $e) {
            DCPApi::logError("DCP order [$this->id]: sending failed.", $e);
        }

        try {
            $this->discardChanges();

            if (!in_array($this->state, [self::STATE_PENDING, self::STATE_ERROR], true)) {
                return $this;
            }

            $this->update([
                'state' => self::STATE_ERROR,
                'status_message' => trans('dcp.errors.unexpected'),
                'attempts' => $this->attempts + 1,
                'last_attempt_at' => now(),
            ]);

            DCPOrderFailed::dispatch($this);
        } catch (Throwable $e) {
            DCPApi::logError("DCP order [$this->id]: could not store the failed attempt.", $e);
        }

        return $this;
    }

    /**
     * Stop the order, it is not sent again until it is retried.
     *
     * @param string $reason
     * @return $this
     */
    public function fail(string $reason): self
    {
        $this->update([
            'state' => self::STATE_ERROR,
            'status_message' => $reason,
            'attempts' => self::maxAttempts(),
            'last_attempt_at' => now(),
        ]);

        DCPOrderFailed::dispatch($this);

        return $this;
    }

    /**
     * @return $this
     */
    public function updateStatusSafe(): self
    {
        try {
            return $this->updateStatus();
        } catch (Throwable $e) {
            DCPApi::logError("DCP order [$this->id]: status update failed.", $e);

            return $this->discardChanges();
        }
    }

    /**
     * @return $this
     */
    public function updateStatus(): self
    {
        if (!$this->dcp_connection?->hasCredentials()) {
            return $this;
        }

        $previousStatusId = $this->status_id;
        $response = $this->dcp_connection->api()->getOrder($this->order_id_dcp);

        if ($response->httpStatus() === 404 || $response->statusId() === self::STATUS_NOT_FOUND) {
            $this->update([
                ...$this->responseAttributes($response),
                'state' => self::STATE_ERROR,
                'status_id' => self::STATUS_NOT_FOUND,
                'status_message' => trans('dcp.statuses.' . self::STATUS_NOT_FOUND),
                'attempts' => self::maxAttempts(),
            ]);

            return $this->dispatchStatusChanged($previousStatusId);
        }

        if (!$response->success()) {
            return $this;
        }

        return $this->applyStatus($response, $previousStatusId);
    }

    /**
     * @param string $orderIdDcp
     * @param Employee $employee
     * @throws ValidationException|Throwable
     * @return DCPOrder|null
     */
    public function setOrderIdDcp(string $orderIdDcp, Employee $employee): ?DCPOrder
    {
        if (!$this->isDuplicate()) {
            return null;
        }

        if (!$this->dcp_connection?->hasCredentials()) {
            throw ValidationException::withMessages([
                'order_id_dcp' => [trans('dcp.errors.connection_missing')],
            ]);
        }

        $response = $this->dcp_connection->api()->getOrder($orderIdDcp);

        if ($response->httpStatus() === 404 || $response->statusId() === self::STATUS_NOT_FOUND) {
            throw ValidationException::withMessages([
                'order_id_dcp' => [trans('dcp.errors.order_id_dcp_not_found')],
            ]);
        }

        if (!$response->success()) {
            throw ValidationException::withMessages([
                'order_id_dcp' => [trans('dcp.errors.order_id_dcp_unverified')],
            ]);
        }

        return DB::transaction(function () use ($orderIdDcp, $employee, $response) {
            /** @var DCPOrder|null $order */
            $order = self::query()->lockForUpdate()->find($this->id);

            if (!$order?->isDuplicate()) {
                return null;
            }

            $previousStatusId = $order->status_id;

            $order->update([
                'order_id_dcp' => $orderIdDcp,
            ]);

            DCPOrderIdDcpSet::dispatch($order, $employee);

            return $order->applyStatus($response, $previousStatusId);
        });
    }

    /**
     * @return bool
     */
    public function isDuplicate(): bool
    {
        return $this->state === self::STATE_SENT && !$this->order_id_dcp;
    }

    /**
     * @return bool
     */
    public function isRetryable(): bool
    {
        return $this->state === self::STATE_ERROR;
    }

    /**
     * @return bool
     */
    public function isEditable(): bool
    {
        return
            $this->state === self::STATE_ERROR ||
            ($this->state === self::STATE_PENDING && !$this->dcp_order_bulk_id);
    }

    /**
     * @param array $deliveryAddress
     * @param array $tags
     * @param Employee $employee
     * @throws ValidationException|Throwable
     * @return DCPOrder|null
     */
    public function updatePayload(array $deliveryAddress, array $tags, Employee $employee): ?DCPOrder
    {
        return DB::transaction(function () use ($deliveryAddress, $tags, $employee) {
            /** @var DCPOrder|null $order */
            $order = self::query()->lockForUpdate()->find($this->id);

            if (!$order?->isEditable()) {
                return null;
            }

            $payload = $order->payload ?: [];
            $oldAddress = (array) Arr::get($payload, 'deliveryAddress', []);
            $oldTags = Arr::except((array) Arr::get($payload, 'orderRows.0.tagIds.0', []), 'key');

            $address = DCPOrderPayloadBuilder::normalizeDeliveryAddress([
                ...$oldAddress,
                ...Arr::only($deliveryAddress, DCPOrderPayloadBuilder::DELIVERY_ADDRESS_FIELDS),
            ]);

            $newTags = $oldTags;

            foreach ($tags as $key => $value) {
                $value = trim((string) $value);

                if ($value === '') {
                    unset($newTags[$key]);
                } else {
                    $newTags[$key] = $value;
                }
            }

            Arr::set($payload, 'deliveryAddress', $address);

            Arr::set($payload, 'orderRows.0.tagIds.0', [
                'key' => Arr::get($payload, 'orderRows.0.tagIds.0.key', 'tag_001'),
                ...$newTags,
            ]);

            if ($error = DCPOrderPayloadBuilder::validatePayload($payload, $order->orderTypeConfig() ?: [])) {
                throw ValidationException::withMessages(['payload' => [$error]]);
            }

            $changed = [
                ...array_map(fn ($key) => "deliveryAddress.$key", self::changedKeys($oldAddress, $address)),
                ...array_map(fn ($key) => "tagIds.$key", self::changedKeys($oldTags, $newTags)),
            ];

            if (!$changed) {
                return $order;
            }

            $order->update([
                'payload' => $payload,
                'payload_edited_at' => now(),
                'payload_edited_by_employee_id' => $employee->id,
            ]);

            DCPOrderPayloadUpdated::dispatch($order, $employee, [
                'dcp_order_changed_fields' => $changed,
            ]);

            return $order;
        });
    }

    /**
     * @param Employee|null $employee
     * @return $this
     */
    public function resetPayload(?Employee $employee = null): self
    {
        [$payload, $error] = $this->rebuildPayload();

        $this->update([
            'payload' => $payload,
            'payload_edited_at' => null,
            'payload_edited_by_employee_id' => null,
            ...$error ? [
                'state' => self::STATE_ERROR,
                'status_message' => $error,
                'attempts' => self::maxAttempts(),
            ] : [],
        ]);

        DCPOrderPayloadReset::dispatch($this, $employee);

        return $this;
    }

    /**
     * @param Employee|null $employee
     * @return $this
     */
    public function retry(?Employee $employee = null): self
    {
        [$payload, $error] = $this->payload_edited_at
            ? [$this->payload, $this->editedPayloadError()]
            : $this->rebuildPayload();

        $this->update([
            'payload' => $payload,
            'state' => $error ? self::STATE_ERROR : self::STATE_PENDING,
            'status_id' => null,
            'status_message' => $error,
            'attempts' => $error ? self::maxAttempts() : 0,
            'last_attempt_at' => null,
            'response_code' => null,
            'response_body' => null,
        ]);

        if (!$error && $this->dcp_order_bulk) {
            $this->dcp_order_bulk->update([
                'state' => DCPOrderBulk::STATE_PENDING,
                'processed_at' => null,
            ]);
        }

        DCPOrderRetried::dispatch($this, $employee);

        return $this;
    }

    /**
     * @return array<array{key: string, value: string|null, required: bool, errors: string[]}>
     */
    public function deliveryAddressFields(): array
    {
        $address = (array) Arr::get($this->payload ?: [], 'deliveryAddress', []);
        $keys = array_unique([...DCPOrderPayloadBuilder::DELIVERY_ADDRESS_FIELDS, ...array_keys($address)]);

        return array_map(fn (string $key) => [
            'key' => $key,
            'value' => $address[$key] ?? null,
            'required' => in_array($key, DCPOrderPayloadBuilder::DELIVERY_ADDRESS_REQUIRED_FIELDS, true),
            'errors' => $this->fieldValidationErrors('deliveryAddress', $key),
        ], $keys);
    }

    /**
     * @return array
     */
    public function tagFields(): array
    {
        $tags = Arr::except((array) Arr::get($this->payload ?: [], 'orderRows.0.tagIds.0', []), 'key');
        $mapping = (array) Arr::get($this->orderTypeConfig() ?: [], 'tags', []);
        $mapping = collect($mapping)->filter(fn ($tag) => is_array($tag) && ($tag['key'] ?? null))->keyBy('key');
        $sourceFields = $this->orderableSourceFields();

        return array_map(function (string $key) use ($tags, $mapping, $sourceFields) {
            $tag = (array) $mapping->get($key, []);
            $source = array_key_exists('value', $tag) ? null : ($tag['source'] ?? null);

            return [
                'key' => $key,
                'value' => isset($tags[$key]) ? (string) $tags[$key] : null,
                'required' => (bool) ($tag['required'] ?? false),
                'max' => isset($tag['max']) ? (int) $tag['max'] : null,
                'source' => $source,
                'source_locale' => match (true) {
                    array_key_exists('value', $tag) => trans('dcp.fixed_value'),
                    (bool) $source => $sourceFields[$source] ?? $source,
                    default => null,
                },
                'errors' => $this->fieldValidationErrors('tagIds', $key),
            ];
        }, array_values(array_unique([...$mapping->keys()->all(), ...array_keys($tags)])));
    }

    /**
     * @return string[]
     */
    public function validationErrors(): array
    {
        return DCPResponse::parseValidationErrors(Arr::get($this->response_body ?: [], 'validationErrors'));
    }

    /**
     * @param Employee|null $employee
     * @return array
     */
    public function getLogModels(?Employee $employee = null): array
    {
        return [
            'dcp_order' => $this,
            'employee' => $employee,
            'organization' => $this->organization,
            'implementation' => $this->dcp_connection?->implementation,
        ];
    }

    /**
     * @return string|null
     */
    public function statusLocale(): ?string
    {
        $key = "dcp.statuses.$this->status_id";

        return !is_null($this->status_id) && Lang::has($key) ? trans($key) : null;
    }

    /**
     * @param DCPConnection $connection
     * @param DCPOrderable|Model $orderable
     * @param string $orderId
     * @return array{0: array, 1: string|null}
     */
    protected static function makePayload(
        DCPConnection $connection,
        DCPOrderable|Model $orderable,
        string $orderId,
    ): array {
        [$payload, $error] = $connection->makePayload($orderable, $orderId);

        return [$payload, $orderable->dcpOrderBlockReason() ?: $error];
    }

    /**
     * @return string|null
     */
    protected function blockReason(): ?string
    {
        if (!$this->dcp_connection) {
            return trans('dcp.errors.connection_missing');
        }

        return $this->orderable instanceof DCPOrderable
            ? $this->orderable->dcpOrderBlockReason()
            : trans('dcp.errors.orderable_missing');
    }

    /**
     * @return void
     */
    protected function notifyOrderable(): void
    {
        try {
            if ($this->orderable instanceof DCPOrderable) {
                $this->orderable->onDCPOrderSent($this);
            }
        } catch (Throwable $e) {
            DCPApi::logError("DCP order [$this->id]: orderable callback failed.", $e);
        }
    }

    /**
     * @return array{0: array|null, 1: string|null}
     */
    protected function rebuildPayload(): array
    {
        $orderable = $this->orderable;

        return match (true) {
            !$this->dcp_connection => [$this->payload, trans('dcp.errors.connection_missing')],
            !$orderable instanceof DCPOrderable => [$this->payload, trans('dcp.errors.orderable_missing')],
            default => self::makePayload($this->dcp_connection, $orderable, $this->order_id),
        };
    }

    /**
     * @return string|null
     */
    protected function editedPayloadError(): ?string
    {
        return $this->blockReason() ?: DCPOrderPayloadBuilder::validatePayload(
            $this->payload ?: [],
            $this->orderTypeConfig() ?: [],
        );
    }

    /**
     * @return array|null
     */
    protected function orderTypeConfig(): ?array
    {
        return $this->dcp_connection?->orderTypeConfig($this->orderable_type);
    }

    /**
     * @param array $old
     * @param array $new
     * @return string[]
     */
    protected static function changedKeys(array $old, array $new): array
    {
        $changed = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            if ((string) ($old[$key] ?? '') !== (string) ($new[$key] ?? '')) {
                $changed[] = (string) $key;
            }
        }

        return $changed;
    }

    /**
     * @param string $group
     * @param string $key
     * @return string[]
     */
    protected function fieldValidationErrors(string $group, string $key): array
    {
        return array_values(array_filter($this->validationErrors(), function (string $error) use ($group, $key) {
            $path = Str::lower(Str::before($error, ':'));

            return Str::contains($path, Str::lower($group)) && Str::afterLast($path, '.') === Str::lower($key);
        }));
    }

    /**
     * @return array<string, string>
     */
    protected function orderableSourceFields(): array
    {
        $class = Relation::getMorphedModel($this->orderable_type) ?: $this->orderable_type;

        return is_subclass_of($class, DCPOrderable::class) ? $class::dcpOrderSourceFields() : [];
    }

    /**
     * @param DCPResponse $response
     * @param int|null $previousStatusId
     * @return $this
     */
    protected function applyStatus(DCPResponse $response, ?int $previousStatusId): self
    {
        $statusId = $response->statusId();

        $this->fill([
            ...$this->responseAttributes($response),
            'status_id' => $statusId,
            'shipped_at' => $response->shippingDate() ?: $this->shipped_at,
        ]);

        if ($statusId === self::STATUS_CLOSED) {
            $this->fill(['state' => self::STATE_CLOSED]);
        }

        if (in_array($statusId, self::STATUSES_CANCELED, true)) {
            $this->fill(['state' => self::STATE_CANCELED]);
        }

        $this->save();

        return $this->dispatchStatusChanged($previousStatusId);
    }

    /**
     * @param int|null $previousStatusId
     * @return $this
     */
    protected function dispatchStatusChanged(?int $previousStatusId): self
    {
        if ($previousStatusId !== $this->status_id) {
            DCPOrderStatusChanged::dispatch($this, null, [
                'dcp_order_previous_status_id' => $previousStatusId,
            ]);
        }

        return $this;
    }

    /**
     * @param DCPResponse $response
     * @return array
     */
    protected function responseAttributes(DCPResponse $response): array
    {
        return [
            'response_code' => $response->httpStatus(),
            'response_body' => $response->body(),
            'status_message' => Str::limit((string) $response->statusMessage(), 1000, '') ?: null,
        ];
    }
}
