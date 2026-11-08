<?php

namespace App\Services\DCPService\Models;

use App\Models\Implementation;
use App\Services\DCPService\Contracts\DCPOrderable;
use App\Services\DCPService\DCPApi;
use App\Services\DCPService\DCPOrderPayloadBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $implementation_id
 * @property bool $enabled
 * @property string $url
 * @property string|null $username
 * @property string|null $password
 * @property array|null $order_types
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|DCPOrder[] $dcp_orders
 * @property-read int|null $dcp_orders_count
 * @property-read Implementation $implementation
 * @mixin \Eloquent
 */
class DCPConnection extends Model
{
    protected $table = 'dcp_connections';

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_id', 'enabled', 'url', 'username', 'password', 'order_types',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
        'password' => 'encrypted',
        'order_types' => 'array',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function dcp_orders(): HasMany
    {
        return $this->hasMany(DCPOrder::class, 'dcp_connection_id');
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->enabled && $this->hasCredentials();
    }

    /**
     * @return bool
     */
    public function hasCredentials(): bool
    {
        return self::isAllowedUrl((string) $this->url) && $this->username && $this->password;
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function isAllowedUrl(string $url): bool
    {
        return $url && in_array(rtrim($url, '/'), (array) Config::get('dcp.allowed_urls', []), true);
    }

    /**
     * @return array<string, class-string<DCPOrderable|Model>>
     */
    public static function orderableTypes(): array
    {
        return collect((array) Config::get('dcp.orderable_types', []))
            ->mapWithKeys(fn (string $type) => [$type => Relation::getMorphedModel($type)])
            ->filter(fn (?string $class) => $class && is_subclass_of($class, DCPOrderable::class))
            ->all();
    }

    /**
     * @param Implementation $implementation
     * @param array $data
     * @return DCPConnection
     */
    public static function saveForImplementation(Implementation $implementation, array $data): DCPConnection
    {
        $connection = $implementation->dcp_connection ?: $implementation->dcp_connection()->make();

        $connection->fill([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'url' => rtrim((string) ($data['url'] ?? ''), '/'),
            'username' => ($data['username'] ?? null) ?: null,
            'order_types' => self::normalizeOrderTypes((array) ($data['order_types'] ?? [])),
            ...($data['password'] ?? null) ? ['password' => $data['password']] : [],
        ])->save();

        return $connection;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public function testConnection(): array
    {
        $response = $this->api()->getOrder((string) Str::uuid());
        $status = $response->httpStatus();

        return match (true) {
            in_array($status, [401, 403], true) => [false, trans('dcp.connection_test.unauthorized')],
            $status === 404 || $response->success() => [true, trans('dcp.connection_test.success')],
            $status === null => [false, trans('dcp.connection_test.unreachable')],
            default => [false, trans('dcp.connection_test.failed', ['status' => $status])],
        };
    }

    /**
     * @param DCPOrderable|Model $orderable
     * @return bool
     */
    public function acceptsOrderable(DCPOrderable|Model $orderable): bool
    {
        return $this->isActive() && $this->orderTypeConfig($orderable->getMorphClass()) !== null;
    }

    /**
     * @param string $orderableType
     * @return array{label: string, item: string|null, tags: array}|null
     */
    public function orderTypeConfig(string $orderableType): ?array
    {
        $config = Arr::get($this->order_types ?: [], $orderableType);

        return is_array($config) ? $config : null;
    }

    /**
     * @return DCPApi
     */
    public function api(): DCPApi
    {
        return new DCPApi($this->url, (string) $this->username, (string) $this->password);
    }

    /**
     * @param DCPOrderable|Model $orderable
     * @param string $orderId
     * @return array{0: array, 1: string|null}
     */
    public function makePayload(DCPOrderable|Model $orderable, string $orderId): array
    {
        return (new DCPOrderPayloadBuilder($this))->make($orderable, $orderId);
    }

    /**
     * @param array $orderTypes
     * @return array
     */
    protected static function normalizeOrderTypes(array $orderTypes): array
    {
        return array_map(fn (array $config) => [
            'label' => trim((string) ($config['label'] ?? '')),
            ...trim((string) ($config['item'] ?? '')) ? ['item' => trim((string) $config['item'])] : [],
            'tags' => array_values(array_map(
                fn (array $tag) => self::normalizeTag($tag),
                array_filter((array) ($config['tags'] ?? []), 'is_array'),
            )),
        ], array_filter($orderTypes, 'is_array'));
    }

    /**
     * @param array $tag
     * @return array
     */
    protected static function normalizeTag(array $tag): array
    {
        $value = $tag['value'] ?? null;

        return array_filter([
            'key' => trim((string) ($tag['key'] ?? '')),
            ...($value !== null && $value !== '' ? ['value' => (string) $value] : ['source' => $tag['source'] ?? null]),
            'required' => (bool) ($tag['required'] ?? false),
            'format' => ($tag['format'] ?? null) ?: null,
            'max' => isset($tag['max']) && $tag['max'] !== '' ? (int) $tag['max'] : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
