<?php

namespace Tests\Unit;

use App\Models\PhysicalCardRequest;
use App\Services\DCPService\DCPOrderPayloadBuilder;
use App\Services\DCPService\Models\DCPConnection;
use Tests\TestCase;

class DCPOrderPayloadBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function testFormatValue(): void
    {
        $this->assertSame('JOHN', DCPOrderPayloadBuilder::formatValue('john', 'upper'));
        $this->assertSame('john', DCPOrderPayloadBuilder::formatValue('JOHN', 'lower'));
        $this->assertSame('john', DCPOrderPayloadBuilder::formatValue('  john  '));
        $this->assertSame('123', DCPOrderPayloadBuilder::formatValue(123));

        $this->assertSame('17-05-1990', DCPOrderPayloadBuilder::formatValue('1990-05-17', 'date:d-m-Y'));
        $this->assertSame('1990-05-17', DCPOrderPayloadBuilder::formatValue('17-05-1990', 'date:Y-m-d'));
        $this->assertSame('17/05/1990', DCPOrderPayloadBuilder::formatValue('1990-05-17', 'date:d/m/Y'));
        $this->assertSame('17051990', DCPOrderPayloadBuilder::formatValue('1990-05-17', 'date:dmY'));
        $this->assertSame('17-05-1990', DCPOrderPayloadBuilder::formatValue('1990-05-17', 'date'));

        // values which can't be sent
        $this->assertNull(DCPOrderPayloadBuilder::formatValue('not a date', 'date:d-m-Y'));
        $this->assertNull(DCPOrderPayloadBuilder::formatValue(null, 'upper'));
        $this->assertNull(DCPOrderPayloadBuilder::formatValue('   ', 'upper'));
        $this->assertNull(DCPOrderPayloadBuilder::formatValue(['john'], 'upper'));
    }

    /**
     * @return void
     */
    public function testNormalizeDeliveryAddress(): void
    {
        // the order of the keys doesn't matter for DCP
        $this->assertEquals([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'fullname' => 'John Doe',
            'address1' => 'Hoofdstraat 12 A',
            'zipcode' => '1234AB',
            'city' => 'NIJMEGEN',
            'country' => DCPOrderPayloadBuilder::COUNTRY,
        ], DCPOrderPayloadBuilder::normalizeDeliveryAddress([
            'firstname' => ' John ',
            'lastname' => 'Doe',
            'address1' => ' Hoofdstraat 12 A ',
            'zipcode' => '1234 ab',
            'city' => 'Nijmegen ',
        ]));

        // the full name is kept when given, the empty fields are dropped
        $this->assertEquals([
            'fullname' => 'J. Doe',
            'address1' => 'Hoofdstraat 12',
            'zipcode' => '1234AB',
            'city' => 'NIJMEGEN',
            'country' => 'BELGIUM',
        ], DCPOrderPayloadBuilder::normalizeDeliveryAddress([
            'firstname' => '',
            'lastname' => null,
            'fullname' => 'J. Doe',
            'address1' => 'Hoofdstraat 12',
            'zipcode' => '1234AB',
            'city' => 'nijmegen',
            'country' => 'belgium',
        ]));
    }

    /**
     * @return void
     */
    public function testValidatePayload(): void
    {
        $config = [
            'label' => 'Gelrepas',
            'tags' => [
                ['key' => 'printedFirstName', 'source' => 'recipient.first_name', 'required' => true],
                ['key' => 'printedCityCode', 'value' => '1245', 'max' => 4],
                ['key' => 'printedCardType', 'source' => 'card_type.name'],
            ],
        ];

        $payload = $this->makePayload(['printedFirstName' => 'JOHN', 'printedCityCode' => '1245']);

        $this->assertNull(DCPOrderPayloadBuilder::validatePayload($payload, $config));

        $this->assertSame(
            trans('dcp.errors.label_missing'),
            DCPOrderPayloadBuilder::validatePayload([...$payload, 'label' => ''], $config),
        );

        foreach (DCPOrderPayloadBuilder::DELIVERY_ADDRESS_REQUIRED_FIELDS as $field) {
            $invalid = $payload;
            unset($invalid['deliveryAddress'][$field]);

            $this->assertSame(
                trans('dcp.errors.address_field_missing', ['field' => $field]),
                DCPOrderPayloadBuilder::validatePayload($invalid, $config),
            );
        }

        $this->assertSame(
            trans('dcp.errors.tag_field_missing', ['field' => 'printedFirstName']),
            DCPOrderPayloadBuilder::validatePayload($this->makePayload(['printedCityCode' => '1245']), $config),
        );

        $this->assertSame(
            trans('dcp.errors.tag_field_too_long', ['field' => 'printedCityCode', 'max' => 4]),
            DCPOrderPayloadBuilder::validatePayload($this->makePayload([
                'printedFirstName' => 'JOHN',
                'printedCityCode' => '12345',
            ]), $config),
        );
    }

    /**
     * @return void
     */
    public function testMakePayloadFromMapping(): void
    {
        $connection = new DCPConnection(['order_types' => [
            'physical_card_request' => [
                'label' => 'Stadspas',
                'tags' => [
                    ['key' => 'key', 'source' => 'recipient.first_name'],
                    ['key' => 'printedFirstName', 'source' => 'recipient.first_name', 'format' => 'upper'],
                    ['key' => 'printedLastName', 'source' => 'recipient.last_name', 'required' => true],
                    ['key' => 'printedCityCode', 'value' => '1245'],
                    ['key' => 'printedCardNumber', 'source' => 'card.code'],
                    ['key' => 'printedDateOfBirth', 'source' => 'recipient.birth_date', 'format' => 'date:d-m-Y'],
                ],
            ],
        ]]);

        $cardRequest = new PhysicalCardRequest([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address' => 'Hoofdstraat',
            'house' => '12',
            'postcode' => '1234 AB',
            'city' => 'Nijmegen',
            'card_code' => '1234567812345678',
        ]);

        [$payload, $error] = $connection->makePayload($cardRequest, 'order-uuid');

        $this->assertNull($error);
        $this->assertSame('order-uuid', $payload['orderId']);
        $this->assertSame('Stadspas', $payload['label']);
        $this->assertSame('John Doe', $payload['deliveryAddress']['fullname']);
        $this->assertSame('Hoofdstraat 12', $payload['deliveryAddress']['address1']);

        // the item falls back to the default, the "key" tag is skipped and the missing birth date is not sent
        $this->assertSame(DCPOrderPayloadBuilder::DEFAULT_ITEM, $payload['orderRows'][0]['item']);
        $this->assertSame([
            'key' => 'tag_001',
            'printedFirstName' => 'JOHN',
            'printedLastName' => 'Doe',
            'printedCityCode' => '1245',
            'printedCardNumber' => '1234567812345678',
        ], $payload['orderRows'][0]['tagIds'][0]);

        // a required field without value
        $cardRequest->last_name = null;
        [, $error] = $connection->makePayload($cardRequest, 'order-uuid');

        $this->assertSame(trans('dcp.errors.tag_field_missing', ['field' => 'printedLastName']), $error);

        // no mapping for the type of the order
        [, $error] = (new DCPConnection(['order_types' => []]))->makePayload($cardRequest, 'order-uuid');

        $this->assertSame(trans('dcp.errors.order_type_missing'), $error);
    }

    /**
     * @param array $tags
     * @return array
     */
    protected function makePayload(array $tags): array
    {
        return [
            'orderId' => 'order-uuid',
            'label' => 'Gelrepas',
            'deliveryAddress' => [
                'fullname' => 'John Doe',
                'address1' => 'Hoofdstraat 12',
                'zipcode' => '1234AB',
                'city' => 'NIJMEGEN',
                'country' => DCPOrderPayloadBuilder::COUNTRY,
            ],
            'orderRows' => [[
                'key' => 'OrderRow_001',
                'item' => 'card',
                'tagIds' => [['key' => 'tag_001', ...$tags]],
            ]],
        ];
    }
}
