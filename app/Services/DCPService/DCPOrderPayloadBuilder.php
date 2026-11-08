<?php

namespace App\Services\DCPService;

use App\Services\DCPService\Contracts\DCPOrderable;
use App\Services\DCPService\Models\DCPConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class DCPOrderPayloadBuilder
{
    public const string COUNTRY = 'NETHERLANDS';
    public const string DEFAULT_ITEM = 'card';

    public const array FORMATS = [
        'upper', 'lower', 'date',
    ];

    public const array DELIVERY_ADDRESS_FIELDS = [
        'firstname', 'lastname', 'fullname', 'address1', 'zipcode', 'city', 'country',
    ];

    public const array DELIVERY_ADDRESS_REQUIRED_FIELDS = [
        'fullname', 'address1', 'zipcode', 'city', 'country',
    ];

    /**
     * @param DCPConnection $connection
     */
    public function __construct(protected DCPConnection $connection)
    {
    }

    /**
     * @param DCPOrderable|Model $orderable
     * @param string $orderId
     * @return array{0: array, 1: string|null}
     */
    public function make(DCPOrderable|Model $orderable, string $orderId): array
    {
        $config = $this->connection->orderTypeConfig($orderable->getMorphClass());

        $payload = [
            'orderId' => $orderId,
            'label' => trim((string) Arr::get($config ?: [], 'label', '')),
            'deliveryAddress' => self::normalizeDeliveryAddress($orderable->dcpOrderDeliveryAddress()),
            'orderRows' => [[
                'key' => 'OrderRow_001',
                'item' => trim((string) Arr::get($config ?: [], 'item', '')) ?: self::DEFAULT_ITEM,
                'tagIds' => [[
                    'key' => 'tag_001',
                    ...$this->makeTags((array) Arr::get($config ?: [], 'tags', []), $orderable->dcpOrderSourceValues()),
                ]],
            ]],
        ];

        return [$payload, $config ? self::validatePayload($payload, $config) : trans('dcp.errors.order_type_missing')];
    }

    /**
     * @param array $payload
     * @param array $config
     * @return string|null
     */
    public static function validatePayload(array $payload, array $config): ?string
    {
        $address = (array) ($payload['deliveryAddress'] ?? []);

        if (!($payload['label'] ?? null)) {
            return trans('dcp.errors.label_missing');
        }

        foreach (self::DELIVERY_ADDRESS_REQUIRED_FIELDS as $field) {
            if (!($address[$field] ?? null)) {
                return trans('dcp.errors.address_field_missing', ['field' => $field]);
            }
        }

        foreach ((array) ($payload['orderRows'] ?? []) as $row) {
            foreach ((array) ($row['tagIds'] ?? []) as $tag) {
                foreach ((array) ($config['tags'] ?? []) as $tagConfig) {
                    $key = (string) ($tagConfig['key'] ?? '');
                    $value = (string) ($tag[$key] ?? '');
                    $max = (int) ($tagConfig['max'] ?? 0);

                    if (($tagConfig['required'] ?? false) && $value === '') {
                        return trans('dcp.errors.tag_field_missing', ['field' => $key]);
                    }

                    if ($max > 0 && mb_strlen($value) > $max) {
                        return trans('dcp.errors.tag_field_too_long', ['field' => $key, 'max' => $max]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param string|null $format
     * @return string|null
     */
    public static function formatValue(mixed $value, ?string $format = null): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);
        [$name, $argument] = array_pad(explode(':', (string) $format, 2), 2, null);

        if ($value === '') {
            return null;
        }

        return match ($name) {
            'upper' => Str::upper($value),
            'lower' => Str::lower($value),
            'date' => self::formatDate($value, $argument ?: 'd-m-Y'),
            default => $value,
        };
    }

    /**
     * @param array $address
     * @return array
     */
    public static function normalizeDeliveryAddress(array $address): array
    {
        $firstName = trim((string) ($address['firstname'] ?? ''));
        $lastName = trim((string) ($address['lastname'] ?? ''));

        return array_filter([
            ...$address,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'fullname' => trim((string) ($address['fullname'] ?? '')) ?: trim("$firstName $lastName"),
            'address1' => trim((string) ($address['address1'] ?? '')),
            'zipcode' => Str::upper(str_replace(' ', '', (string) ($address['zipcode'] ?? ''))),
            'city' => Str::upper(trim((string) ($address['city'] ?? ''))),
            'country' => Str::upper((string) ($address['country'] ?? '')) ?: self::COUNTRY,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param array $tags
     * @param array $values
     * @return array
     */
    protected function makeTags(array $tags, array $values): array
    {
        $result = [];

        foreach ($tags as $tag) {
            $key = is_array($tag) ? trim((string) ($tag['key'] ?? '')) : '';

            // "key" is the key of the tag in the order row, not a field
            if ($key === '' || $key === 'key') {
                continue;
            }

            $value = self::formatValue(
                array_key_exists('value', $tag) ? $tag['value'] : ($values[$tag['source'] ?? ''] ?? null),
                $tag['format'] ?? null,
            );

            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param string $value
     * @param string $format
     * @return string|null
     */
    protected static function formatDate(string $value, string $format): ?string
    {
        try {
            return Carbon::parse($value)->format($format);
        } catch (Throwable) {
            return null;
        }
    }
}
