<?php

namespace Tests\Feature;

use App\Modules\DCP\Enums\DCPOrderState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MakesTestDCPOrders;
use Throwable;

class VoucherPassDCPMappingTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestDCPOrders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testCardUsesOwnerAndDeliveryUsesRecipient(): void
    {
        ['organization' => $organization, 'pass' => $pass, 'recipient' => $recipient] = $this->makeDCPFixture();
        $pass->update(['print_label' => 'Gelrepas']);

        $this->setDCPProfileValues($recipient, [
            'mailing_street' => 'Mailing Road',
            'mailing_house_number' => '42',
            'mailing_house_letter' => 'A',
            'mailing_house_number_addition' => 'bis',
            'mailing_postal_code' => '5678 cd',
            'mailing_city' => 'Arnhem',
        ]);

        $expectedCard = [
            'key' => 'tag_001',
            'printedCardType' => 'Gelrepas',
            'printedFirstName' => 'T.O.',
            'printedLastName' => 'van Owner',
            'printedDateOfBirth' => '12-04-1990',
            'printedCityCode' => '2020',
            'printedCardNumber' => $pass->code,
            'printedQrCode' => json_encode(['type' => 'pass', 'value' => $pass->code], JSON_THROW_ON_ERROR),
        ];

        $expectedDelivery = [
            'delivery_first_name' => 'Robin',
            'delivery_last_name' => 'de Recipient',
            'delivery_address1' => 'Mailing Road 42 A bis',
            'delivery_postal_code' => '5678CD',
            'delivery_city' => 'ARNHEM',
            'delivery_country' => 'NETHERLANDS',
        ];

        $preview = $this
            ->apiPrepareVoucherPassDCPRequest(
                $organization,
                ['pass_ids' => [$pass->id]],
                $organization->identity,
                preview: true,
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'would_create')
            ->assertJsonPath('data.0.order_id', null);

        $this->assertFalse($pass->dcp_orders()->exists());
        $changes = collect($preview->json('data.0.changes'))->pluck('value', 'field');

        foreach ($expectedCard as $key => $value) {
            if ($key !== 'key') {
                $this->assertSame($value, $changes->get('order_rows.0.tagIds.0.' . $key));
            }
        }

        foreach ($expectedDelivery as $key => $value) {
            $this->assertSame($value, $changes->get($key));
        }

        $order = $this->prepareDCPOrder($pass);

        $this->assertSame(DCPOrderState::DRAFT, $order->state);
        $this->assertEquals($expectedCard, $order->order_rows[0]['tagIds'][0]);
        $this->assertSame($expectedDelivery, $order->only(array_keys($expectedDelivery)));
        Http::assertNothingSent();
    }
}
