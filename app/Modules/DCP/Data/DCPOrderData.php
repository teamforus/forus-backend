<?php

namespace App\Modules\DCP\Data;

use App\Modules\DCP\Models\DCPOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;

readonly class DCPOrderData
{
    public const string LABEL = 'Gelrepas';
    public const string ROW_KEY = 'OrderRow_001';

    /**
     * @param DCPDeliveryAddress $deliveryAddress
     * @param GelrepasCardData $card
     * @param string $label
     * @param string $rowKey
     * @param string $item
     */
    public function __construct(
        public DCPDeliveryAddress $deliveryAddress,
        public GelrepasCardData $card,
        public string $label = self::LABEL,
        public string $rowKey = self::ROW_KEY,
        public string $item = GelrepasCardData::ITEM,
    ) {
    }

    /**
     * @param DCPOrder $order
     * @throws JsonException
     * @throws ValidationException
     * @return self
     */
    public static function fromOrder(DCPOrder $order): self
    {
        $address = new DCPDeliveryAddress(
            $order->delivery_first_name,
            $order->delivery_last_name,
            $order->delivery_address1,
            $order->delivery_postal_code,
            $order->delivery_city,
            country: $order->delivery_country,
        );

        self::validatePayload([
            'label' => $order->label,
            'deliveryAddress' => $address->toArray(),
            'orderRows' => $order->order_rows,
        ]);

        $row = $order->order_rows[0];
        $tag = $row['tagIds'][0];

        return new self(
            $address,
            new GelrepasCardData(
                $tag['printedCardType'],
                $tag['printedFirstName'],
                $tag['printedLastName'],
                CarbonImmutable::createFromFormat('!d-m-Y', $tag['printedDateOfBirth']),
                $tag['printedCityCode'],
                $tag['printedCardNumber'],
                qrCode: $tag['printedQrCode'],
                tagKey: $tag['key'],
            ),
            label: $order->label,
            rowKey: $row['key'],
            item: $row['item'],
        );
    }

    /**
     * @throws JsonException
     * @return self
     */
    public function normalizedForDraft(): self
    {
        return new self(
            new DCPDeliveryAddress(
                trim($this->deliveryAddress->firstName),
                trim($this->deliveryAddress->lastName),
                trim($this->deliveryAddress->address1),
                Str::upper(str_replace(' ', '', trim($this->deliveryAddress->postalCode))),
                Str::upper(trim($this->deliveryAddress->city)),
                country: $this->deliveryAddress->country,
            ),
            new GelrepasCardData(
                trim($this->card->printLabel),
                trim($this->card->firstName),
                trim($this->card->lastName),
                $this->card->birthDate,
                trim($this->card->cityCode),
                $this->card->passCode,
                tagKey: $this->card->tagKey,
            ),
            label: $this->label,
            rowKey: $this->rowKey,
            item: $this->item,
        );
    }

    /**
     * @param string $orderId
     * @return array<string, mixed>
     */
    public function toArray(string $orderId): array
    {
        return [
            'orderId' => $orderId,
            'label' => $this->label,
            'deliveryAddress' => $this->deliveryAddress->toArray(),
            'orderRows' => $this->orderRows(),
        ];
    }

    /**
     * @return array<int, array{key: string, item: string, tagIds: array<int, array<string, string>>}>
     */
    public function orderRows(): array
    {
        return [[
            'key' => $this->rowKey,
            'item' => $this->item,
            'tagIds' => [$this->card->toArray()],
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'label' => $this->label,
            'delivery_first_name' => $this->deliveryAddress->firstName,
            'delivery_last_name' => $this->deliveryAddress->lastName,
            'delivery_address1' => $this->deliveryAddress->address1,
            'delivery_postal_code' => $this->deliveryAddress->postalCode,
            'delivery_city' => $this->deliveryAddress->city,
            'delivery_country' => $this->deliveryAddress->country,
            'order_rows' => $this->orderRows(),
        ];
    }

    /**
     * @throws ValidationException
     * @throws JsonException
     * @return void
     */
    public function validate(): void
    {
        self::validatePayload([
            'label' => $this->label,
            'deliveryAddress' => $this->deliveryAddress->toArray(),
            'orderRows' => $this->orderRows(),
        ]);
    }

    /**
     * @param array $payload
     * @throws ValidationException
     * @throws JsonException
     * @return void
     */
    private static function validatePayload(array $payload): void
    {
        Validator::make($payload, [
            'label' => ['required', Rule::in([self::LABEL])],
            'deliveryAddress.firstname' => ['required', 'string', 'max:200'],
            'deliveryAddress.lastname' => ['required', 'string', 'max:200'],
            'deliveryAddress.address1' => ['required', 'string', 'max:400'],
            'deliveryAddress.zipcode' => ['required', 'string', 'max:20'],
            'deliveryAddress.city' => ['required', 'string', 'max:200'],
            'deliveryAddress.country' => ['required', Rule::in([DCPDeliveryAddress::COUNTRY])],
            'orderRows' => ['required', 'array', 'list', 'size:1'],
            'orderRows.0' => ['required', 'array:key,item,tagIds'],
            'orderRows.0.key' => ['required', 'string'],
            'orderRows.0.item' => ['required', Rule::in([GelrepasCardData::ITEM])],
            'orderRows.0.tagIds' => ['required', 'array', 'list', 'size:1'],
            'orderRows.0.tagIds.0' => ['required', 'array:' . implode(',', [
                'key', 'printedCardType', 'printedFirstName', 'printedLastName', 'printedDateOfBirth',
                'printedCityCode', 'printedQrCode', 'printedCardNumber',
            ])],
            'orderRows.0.tagIds.0.key' => ['required', 'string'],
            'orderRows.0.tagIds.0.printedCardType' => ['required', 'string', 'max:200'],
            'orderRows.0.tagIds.0.printedFirstName' => ['required', 'string', 'max:200'],
            'orderRows.0.tagIds.0.printedLastName' => ['required', 'string', 'max:200'],
            'orderRows.0.tagIds.0.printedDateOfBirth' => ['required', 'date_format:d-m-Y'],
            'orderRows.0.tagIds.0.printedCityCode' => ['required', 'string', 'max:200'],
            'orderRows.0.tagIds.0.printedCardNumber' => ['required', 'string', 'max:20'],
            'orderRows.0.tagIds.0.printedQrCode' => ['required', 'string', 'json'],
        ])->validate();

        $tag = $payload['orderRows'][0]['tagIds'][0];
        $qr = json_decode($tag['printedQrCode'], true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($qr) || ($qr['type'] ?? null) !== GelrepasCardData::QR_TYPE ||
            ($qr['value'] ?? null) !== $tag['printedCardNumber']) {
            throw ValidationException::withMessages([
                'orderRows.0.tagIds.0.printedQrCode' => trans('dcp::validation.code_mismatch'),
            ]);
        }
    }
}
