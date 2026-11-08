<?php

namespace Tests\Feature;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\RecordType;
use App\Services\DCPService\Models\DCPOrder;
use App\Services\DCPService\Models\DCPOrderBulk;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestDCP;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class DCPOrdersTest extends TestCase
{
    use MakesTestDCP;
    use MakesTestFunds;
    use MakesApiRequests;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDCP();
    }

    /**
     * The order is created when the card is requested, with the payload built from the mapping of the connection.
     *
     * @return void
     */
    public function testOrderIsCreatedWithMappedPayload(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);

        $this->assertNotNull($order);
        $cardRequest = $order->orderable;
        $voucherAddress = $cardRequest->voucher->token_without_confirmation->address;

        $this->assertSame(DCPOrder::STATE_PENDING, $order->state);
        $this->assertSame($setup['connection']->id, $order->dcp_connection_id);
        $this->assertSame($setup['organization']->id, $order->organization_id);
        $this->assertNull($order->dcp_order_bulk_id);
        $this->assertSame(16, strlen($cardRequest->card_code), 'The card code is generated for the order.');

        // the payload is stored as json, the order of the keys isn't kept (and doesn't matter for DCP)
        $this->assertEquals([
            'orderId' => $order->order_id,
            'label' => 'Gelrepas',
            'deliveryAddress' => [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'fullname' => 'John Doe',
                'address1' => 'Hoofdstraat 12 A',
                'zipcode' => '1234AB',
                'city' => 'NIJMEGEN',
                'country' => 'NETHERLANDS',
            ],
            'orderRows' => [[
                'key' => 'OrderRow_001',
                'item' => 'card',
                'tagIds' => [[
                    'key' => 'tag_001',
                    'printedCardType' => $setup['card_type']->name,
                    'printedFirstName' => 'John',
                    'printedLastName' => 'Doe',
                    'printedDateOfBirth' => '17-05-1990',
                    'printedCityCode' => '1245',
                    'printedQrCode' => json_encode(['type' => 'voucher', 'value' => $voucherAddress]),
                    'printedCardNumber' => implode(' ', str_split($cardRequest->card_code, 4)),
                ]],
            ]],
        ], $order->payload);

        $this->assertDCPOrderLogs($order, [DCPOrder::EVENT_CREATED]);
    }

    /**
     * @return void
     */
    public function testOrderIsCreatedForRequesterAndSponsorRequests(): void
    {
        $setup = $this->makeTestDCPSetup();
        $sponsor = $setup['organization']->identity;
        $requester = $this->makeTestDCPRequester(['given_name' => 'Rick', 'family_name' => 'Records']);
        $voucher = $this->makeTestVoucher($setup['fund'], $requester, amount: 100);
        $address = $voucher->token_without_confirmation->address;

        $data = [
            ...$this->makeDCPAddressData(),
            'postcode' => '1234 AB',
            'fund_physical_card_type_id' => $setup['fund_card_type']->id,
        ];

        $this->postJson(
            "/api/v1/platform/vouchers/$address/physical-card-requests",
            $data,
            $this->makeApiHeaders($requester),
        )->assertSuccessful();

        $this->postJson(
            "/api/v1/platform/organizations/{$setup['organization']->id}/sponsor/vouchers/$address/physical-card-requests",
            [...$data, 'first_name' => 'Jane', 'last_name' => 'Smith'],
            $this->makeApiHeaders($sponsor),
        )->assertSuccessful();

        $cardRequests = $voucher->physical_card_requests()->with('dcp_order')->orderBy('id')->get();

        $this->assertCount(2, $cardRequests);
        $this->assertSame(DCPOrder::STATE_PENDING, $cardRequests[0]->dcp_order?->state);
        $this->assertSame(DCPOrder::STATE_PENDING, $cardRequests[1]->dcp_order?->state);

        // the name entered in the request is used, not the name from the records of the requester
        $this->assertSame('John Doe', $cardRequests[0]->dcp_order->payload['deliveryAddress']['fullname']);
        $this->assertSame('Jane Smith', $cardRequests[1]->dcp_order->payload['deliveryAddress']['fullname']);
        $this->assertSame('Jane', $cardRequests[1]->dcp_order->payload['orderRows'][0]['tagIds'][0]['printedFirstName']);

        // the identity which requested the card is logged
        $this->assertSame(
            $requester->address,
            $cardRequests[0]->dcp_order->logs()->where('event', 'created')->value('identity_address'),
        );

        $this->assertSame(
            $sponsor->address,
            $cardRequests[1]->dcp_order->logs()->where('event', 'created')->value('identity_address'),
        );

        // without a name in the request the records are not used: the order can't be sent until it is filled in
        $voucher = $this->makeTestVoucher($setup['fund'], $requester, amount: 100);

        $this->postJson(
            "/api/v1/platform/vouchers/{$voucher->token_without_confirmation->address}/physical-card-requests",
            [...$data, 'first_name' => '', 'last_name' => null],
            $this->makeApiHeaders($requester),
        )->assertSuccessful();

        $order = $voucher->physical_card_requests()->first()->dcp_order;

        $this->assertSame(DCPOrder::STATE_ERROR, $order->state);
        $this->assertSame(trans('dcp.errors.address_field_missing', ['field' => 'fullname']), $order->status_message);
        $this->assertArrayNotHasKey('fullname', $order->payload['deliveryAddress']);
        $this->assertArrayNotHasKey('printedFirstName', $order->payload['orderRows'][0]['tagIds'][0]);
    }

    /**
     * @return void
     */
    public function testOrderIsCreatedWhenFundRequestIsApproved(): void
    {
        $setup = $this->makeTestDCPSetup();
        $setup['organization']->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $setup['fund']->fund_config->forceFill([
            'fund_request_physical_card_enable' => true,
            'fund_request_physical_card_type_id' => $setup['card_type']->id,
        ])->save();

        $requester = $this->makeTestDCPRequester();

        $response = $this->makeFundRequest($requester, $setup['fund'], [
            $this->makeRequestCriterionValue($setup['fund'], 'children_nth', 3),
        ], false, data: [
            'physical_card_request_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'city' => 'Nijmegen',
                'street' => 'Hoofdstraat',
                'house_nr' => '12',
                'house_nr_addition' => 'A',
                'postal_code' => '1234 AB',
            ],
        ])->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $cardRequest = $fundRequest->physical_card_requests()->first();

        $this->assertNotNull($cardRequest);
        $this->assertNull($cardRequest->dcp_order, 'No order before the voucher exists.');

        $fundRequest->assignEmployee($setup['organization']->employees()->first());
        $fundRequest->approve();

        $order = $cardRequest->refresh()->dcp_order;

        $this->assertNotNull($order);
        $this->assertNotNull($cardRequest->voucher_id, 'The card request is linked to the new voucher.');
        $this->assertSame(DCPOrder::STATE_PENDING, $order->state);
        $this->assertSame('John Doe', $order->payload['deliveryAddress']['fullname']);
    }

    /**
     * @return void
     */
    public function testOrderIsNotCreatedWhenDCPIsNotUsed(): void
    {
        $cases = [
            'dcp disabled' => fn (array $setup) => Config::set('dcp.enabled', false),
            'connection disabled' => fn (array $setup) => $setup['connection']->update(['enabled' => false]),
            'url not allowed' => fn (array $setup) => $setup['connection']->update(['url' => 'https://other.test']),
            'card type not sent' => fn (array $setup) => $setup['card_type']->forceFill(['send_to_dcp' => false])->save(),
            'no mapping for type' => fn (array $setup) => $setup['connection']->update(['order_types' => []]),
            'no connection' => fn (array $setup) => $setup['connection']->delete(),
        ];

        foreach ($cases as $case => $prepare) {
            Config::set('dcp.enabled', true);

            $setup = $this->makeTestDCPSetup();
            $prepare($setup);

            $voucher = $this->makeTestVoucher($setup['fund'], $this->makeTestDCPRequester(), amount: 100);
            $cardRequest = $this->makeTestDCPCardRequest($voucher, $setup['card_type']);

            $this->assertNull($cardRequest->dcp_order()->first(), "Order created: $case.");
        }
    }

    /**
     * @return void
     */
    public function testOrderWithMissingRequiredFieldIsNotSent(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup, ['birth_date' => null]);

        $this->assertSame(DCPOrder::STATE_ERROR, $order->state);
        $this->assertSame(DCPOrder::maxAttempts(), $order->attempts);
        $this->assertSame(
            trans('dcp.errors.tag_field_missing', ['field' => 'printedDateOfBirth']),
            $order->status_message,
        );

        // nothing to approve, nothing sent
        $this->assertNull($this->approveTestDCPOrders($setup['organization']));

        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_ERROR, $order->refresh()->state);
        $this->assertCount(0, $this->recordedDCPRequests('POST', '/api/Orders'));
    }

    /**
     * @return void
     */
    public function testOrdersAreSentOnlyAfterApproval(): void
    {
        $setup = $this->makeTestDCPSetup();
        $otherSetup = $this->makeTestDCPSetup();

        $order1 = $this->makeTestDCPOrder($setup);
        $order2 = $this->makeTestDCPOrder($setup);
        $otherOrder = $this->makeTestDCPOrder($otherSetup);

        DCPOrder::sendAll();

        $this->assertCount(0, $this->recordedDCPRequests('POST', '/api/Orders'));
        $this->assertSame(DCPOrder::STATE_PENDING, $order1->refresh()->state);

        // the bulk takes only the pending orders of the organization
        $bulk = $this->approveTestDCPOrders($setup['organization']);

        $this->assertNotNull($bulk);
        $this->assertEqualsCanonicalizing([$order1->id, $order2->id], $bulk->dcp_orders()->pluck('id')->all());
        $this->assertNull($otherOrder->refresh()->dcp_order_bulk_id);
        $this->assertDCPOrderLogs($order1, [DCPOrder::EVENT_CREATED, DCPOrder::EVENT_APPROVED]);

        DCPOrder::sendAll();

        $requests = $this->recordedDCPRequests('POST', '/api/Orders');
        $this->assertCount(2, $requests);
        $this->assertTrue($requests[0]->hasHeader('Authorization', 'Basic ' . base64_encode(
            'dcp-test-user:dcp-test-password',
        )));

        foreach ([$order1, $order2] as $order) {
            $order->refresh();

            $this->assertSame(DCPOrder::STATE_SENT, $order->state);
            $this->assertSame('DCP-0001', $order->order_id_dcp);
            $this->assertSame(DCPOrder::STATUS_WEB_API_ACCEPTED, $order->status_id);
            $this->assertSame(1, $order->attempts);
            $this->assertFalse($order->isDuplicate());
            $this->assertNotNull($order->orderable->physical_card_id, 'The physical card is added to the voucher.');
            $this->assertDCPOrderLogs($order, [
                DCPOrder::EVENT_CREATED,
                DCPOrder::EVENT_APPROVED,
                DCPOrder::EVENT_SENT,
            ]);
        }

        $sentPayloads = array_map(fn (Request $request) => $request->data(), $requests);
        $this->assertContains($order1->payload, $sentPayloads);

        $this->assertSame(DCPOrderBulk::STATE_SENT, $bulk->refresh()->state);
        $this->assertNotNull($bulk->processed_at);
        $this->assertSame(DCPOrder::STATE_PENDING, $otherOrder->refresh()->state);
    }

    /**
     * @return void
     */
    public function testDuplicateOrderIsMarkedAsSent(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);

        $this->fakeDCPApi($this->makeDCPDuplicateResponse());
        $this->approveTestDCPOrders($setup['organization']);
        $order->refresh()->send();

        $this->assertSame(DCPOrder::STATE_SENT, $order->state);
        $this->assertNull($order->order_id_dcp);
        $this->assertTrue($order->isDuplicate());
        $this->assertSame(trans('dcp.errors.duplicate_order'), $order->status_message);
        $this->assertTrue($order->logs()->where('event', DCPOrder::EVENT_SENT)->first()->data['dcp_order_duplicate']);

        // the status of a duplicate can't be tracked
        $this->assertFalse(DCPOrder::queryToUpdateStatus()->whereKey($order->id)->exists());
    }

    /**
     * @return void
     */
    public function testRejectedOrderIsNotRetried(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);

        $this->fakeDCPApi($this->makeDCPValidationResponse([
            'DeliveryAddress.Zipcode: The zipcode is invalid.',
            'OrderRows[0].TagIds[0].printedDateOfBirth: The date is invalid.',
        ]));

        $this->approveTestDCPOrders($setup['organization']);
        DCPOrder::sendAll();

        $order->refresh();

        $this->assertSame(DCPOrder::STATE_ERROR, $order->state);
        $this->assertEquals(422, $order->response_code);
        $this->assertSame(DCPOrder::maxAttempts(), $order->attempts);
        $this->assertCount(2, $order->validationErrors());

        $this->assertDCPOrderLogs($order, [
            DCPOrder::EVENT_CREATED,
            DCPOrder::EVENT_APPROVED,
            DCPOrder::EVENT_FAILED,
        ]);

        // the errors are shown with the fields
        $this->assertSame(['DeliveryAddress.Zipcode: The zipcode is invalid.'], collect($order->deliveryAddressFields())
            ->firstWhere('key', 'zipcode')['errors']);

        $this->assertSame(['OrderRows[0].TagIds[0].printedDateOfBirth: The date is invalid.'], collect($order->tagFields())
            ->firstWhere('key', 'printedDateOfBirth')['errors']);

        $this->travel(Config::get('dcp.attempts_interval') + 1)->minutes();
        DCPOrder::sendAll();

        $this->assertCount(1, $this->recordedDCPRequests('POST', '/api/Orders'));
        $this->assertSame(DCPOrderBulk::STATE_ERROR, $order->dcp_order_bulk->refresh()->state);
    }

    /**
     * @return void
     */
    public function testFailedOrderIsRetriedAfterInterval(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);
        $interval = (int) Config::get('dcp.attempts_interval');

        $this->fakeDCPApi(Http::sequence()
            ->push(['message' => 'Server error'], 500)
            ->pushFailedConnection('Connection timed out')
            ->push(['orderIdDCP' => 'DCP-RETRIED', 'statusId' => 100], 201));

        $this->approveTestDCPOrders($setup['organization']);
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_ERROR, $order->refresh()->state);
        $this->assertSame(1, $order->attempts);
        $this->assertEquals(500, $order->response_code);

        // not retried before the interval
        DCPOrder::sendAll();
        $this->assertSame(1, $order->refresh()->attempts);

        $this->travel($interval + 1)->minutes();
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_ERROR, $order->refresh()->state);
        $this->assertSame(2, $order->attempts);
        $this->assertNull($order->response_code);
        $this->assertStringContainsString('Connection timed out', (string) $order->status_message);

        $this->travel($interval + 1)->minutes();
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_SENT, $order->refresh()->state);
        $this->assertSame('DCP-RETRIED', $order->order_id_dcp);
        $this->assertSame(3, $order->attempts);
        $this->assertSame(DCPOrderBulk::STATE_SENT, $order->dcp_order_bulk->refresh()->state);
    }

    /**
     * @return void
     */
    public function testFailedOrderStopsAfterMaxAttempts(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);

        $this->fakeDCPApi(Http::response(['message' => 'Server error'], 500));
        $this->approveTestDCPOrders($setup['organization']);

        $order->update(['attempts' => DCPOrder::maxAttempts() - 1]);
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::maxAttempts(), $order->refresh()->attempts);

        $this->travel(Config::get('dcp.attempts_interval') + 1)->minutes();
        DCPOrder::sendAll();

        $this->assertCount(1, $this->recordedDCPRequests('POST', '/api/Orders'));
    }

    /**
     * @return void
     */
    public function testOrdersAreNotSentForInactiveConnectionOrBlockedSource(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);
        $blockedOrder = $this->makeTestDCPOrder($setup);

        $this->approveTestDCPOrders($setup['organization']);
        $blockedOrder->orderable->voucher->update(['state' => 'deactivated']);
        $setup['connection']->update(['enabled' => false]);

        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_PENDING, $order->refresh()->state);
        $this->assertSame(DCPOrder::STATE_PENDING, $blockedOrder->refresh()->state);
        $this->assertCount(0, $this->recordedDCPRequests('POST', '/api/Orders'));

        // the order is sent once the connection is enabled again, the order of the deactivated voucher is not
        $setup['connection']->update(['enabled' => true]);
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_SENT, $order->refresh()->state);
        $this->assertSame(DCPOrder::STATE_ERROR, $blockedOrder->refresh()->state);
        $this->assertSame(trans('dcp.errors.voucher_deactivated'), $blockedOrder->status_message);
        $this->assertCount(1, $this->recordedDCPRequests('POST', '/api/Orders'));
    }

    /**
     * @return void
     */
    public function testStatusUpdates(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);
        $canceledOrder = $this->makeTestDCPOrder($setup);
        $missingOrder = $this->makeTestDCPOrder($setup);

        $this->approveTestDCPOrders($setup['organization']);
        DCPOrder::sendAll();

        $order->refresh()->update(['order_id_dcp' => 'DCP-STATUS']);
        $canceledOrder->refresh()->update(['order_id_dcp' => 'DCP-CANCELED']);
        $missingOrder->refresh()->update(['order_id_dcp' => 'DCP-MISSING']);

        $this->fakeDCPApi(get: Http::sequence()
            ->pushResponse($this->makeDCPStatusResponse(DCPOrder::STATUS_PROCESSING))
            ->pushResponse($this->makeDCPStatusResponse(DCPOrder::STATUS_PROCESSING))
            ->pushResponse($this->makeDCPStatusResponse(DCPOrder::STATUS_SHIPPED, '2026-11-05'))
            ->pushResponse($this->makeDCPStatusResponse(DCPOrder::STATUS_CLOSED)));

        $order->updateStatus();
        $this->assertSame(DCPOrder::STATUS_PROCESSING, $order->status_id);
        $this->assertSame(DCPOrder::STATE_SENT, $order->state);

        // the same status is not logged again
        $order->updateStatus();
        $this->assertSame(1, $order->logs()->where('event', DCPOrder::EVENT_STATUS_CHANGED)->count());

        $order->updateStatus();
        $this->assertSame(DCPOrder::STATUS_SHIPPED, $order->status_id);
        $this->assertSame('2026-11-05', $order->shipped_at?->format('Y-m-d'));

        $order->updateStatus();
        $this->assertSame(DCPOrder::STATE_CLOSED, $order->state);
        $this->assertSame('2026-11-05', $order->shipped_at?->format('Y-m-d'), 'The shipping date is kept.');
        $this->assertSame(3, $order->logs()->where('event', DCPOrder::EVENT_STATUS_CHANGED)->count());

        $this->fakeDCPApi(get: $this->makeDCPStatusResponse(DCPOrder::STATUS_CANCELLED));
        $canceledOrder->updateStatus();
        $this->assertSame(DCPOrder::STATE_CANCELED, $canceledOrder->state);

        $this->fakeDCPApi(get: Http::response(['statusId' => -1], 404));
        $missingOrder->updateStatus();
        $this->assertSame(DCPOrder::STATE_ERROR, $missingOrder->state);
        $this->assertSame(DCPOrder::STATUS_NOT_FOUND, $missingOrder->status_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRetryRebuildsPayloadFromSource(): void
    {
        $setup = $this->makeTestDCPSetup();
        $employee = $setup['organization']->employees()->first();
        $order = $this->makeTestDCPOrder($setup, ['birth_date' => null]);

        $this->assertTrue($order->isRetryable());

        // the missing data is added to the source
        $identity = $order->orderable->voucher->identity;
        $identity->makeRecord(RecordType::findByKey('birth_date'), '1990-05-17');
        $order->orderable->update(['city' => 'Arnhem']);

        $order->retry($employee);

        $this->assertSame(DCPOrder::STATE_PENDING, $order->state);
        $this->assertSame(0, $order->attempts);
        $this->assertNull($order->status_message);
        $this->assertSame('17-05-1990', $order->payload['orderRows'][0]['tagIds'][0]['printedDateOfBirth']);
        $this->assertSame('ARNHEM', $order->payload['deliveryAddress']['city']);
        $this->assertSame($employee->identity_address, $order->logs()->where('event', 'retried')->value('identity_address'));

        // only failed orders can be retried
        $this->assertFalse($order->isRetryable());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRetryKeepsEditedPayload(): void
    {
        $setup = $this->makeTestDCPSetup();
        $employee = $setup['organization']->employees()->first();
        $order = $this->makeTestDCPOrder($setup);

        $this->fakeDCPApi($this->makeDCPValidationResponse(['DeliveryAddress.City: The city is invalid.']));
        $bulk = $this->approveTestDCPOrders($setup['organization']);
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_ERROR, $order->refresh()->state);
        $this->assertSame(DCPOrderBulk::STATE_ERROR, $bulk->refresh()->state);

        $order->updatePayload(['city' => 'Edited city'], [], $employee);
        $order->refresh();

        $this->assertNotNull($order->payload_edited_at);
        $this->assertSame('EDITED CITY', $order->payload['deliveryAddress']['city']);

        // the source changes, but the edited payload is sent
        $order->orderable->update(['city' => 'Arnhem']);
        $order->retry($employee);

        $this->assertSame(DCPOrder::STATE_PENDING, $order->state);
        $this->assertSame('EDITED CITY', $order->payload['deliveryAddress']['city']);
        $this->assertSame(DCPOrderBulk::STATE_PENDING, $bulk->refresh()->state, 'The bulk is sent again.');

        $this->fakeDCPApi();
        DCPOrder::sendAll();

        $this->assertSame(DCPOrder::STATE_SENT, $order->refresh()->state);

        $this->assertSame('EDITED CITY', collect($this->recordedDCPRequests('POST', '/api/Orders'))
            ->last()
            ->data()['deliveryAddress']['city']);
    }

    /**
     * @return void
     */
    public function testUnexpectedErrorsDontStopTheBatch(): void
    {
        $setup = $this->makeTestDCPSetup();
        $orders = [$this->makeTestDCPOrder($setup), $this->makeTestDCPOrder($setup), $this->makeTestDCPOrder($setup)];

        $this->approveTestDCPOrders($setup['organization']);

        // the first order fails while it is stored, the second gets a json string instead of an object
        DCPOrder::saving(function (DCPOrder $order) use ($orders) {
            if ($order->is($orders[0]) && $order->isDirty('response_code')) {
                throw new RuntimeException('Unexpected error');
            }
        });

        $this->fakeDCPApi(Http::sequence()
            ->push('Internal error', 500)
            ->push('"Internal error"', 500)
            ->pushResponse($this->makeDCPCreatedResponse('DCP-BATCH-' . $orders[2]->id)));

        $this->assertSame(['sent' => 1, 'failed' => 2], DCPOrder::sendAll());

        $orders = array_map(fn (DCPOrder $order) => $order->refresh(), $orders);

        $this->assertSame(DCPOrder::STATE_ERROR, $orders[0]->state);
        $this->assertSame(trans('dcp.errors.unexpected'), $orders[0]->status_message);
        $this->assertSame(1, $orders[0]->attempts, 'Counted as an attempt, retried after the interval.');
        $this->assertSame(DCPOrder::STATE_ERROR, $orders[1]->state);
        $this->assertSame(1, $orders[1]->attempts);
        $this->assertSame(DCPOrder::STATE_SENT, $orders[2]->state);

        // status updates: an invalid shipping date and errors which aren't a list of messages
        $orders[1]->update(['state' => DCPOrder::STATE_SENT, 'order_id_dcp' => 'DCP-BATCH-' . $orders[1]->id]);

        $this->fakeDCPApi(get: Http::sequence()
            ->push(['statusId' => 500, 'shippingDate' => 'n/a', 'validationErrors' => ['DeliveryAddress.Zipcode' => ['Invalid']]])
            ->pushResponse($this->makeDCPStatusResponse(DCPOrder::STATUS_CLOSED)));

        $this->artisan('dcp:update-status')->assertSuccessful();

        $this->assertSame(DCPOrder::STATUS_SHIPPED, $orders[1]->refresh()->status_id);
        $this->assertNull($orders[1]->shipped_at);
        $this->assertSame('DeliveryAddress.Zipcode: Invalid', $orders[1]->status_message);

        // the errors grouped by field are shown with the field
        $this->assertSame(['DeliveryAddress.Zipcode: Invalid'], $orders[1]->validationErrors());
        $this->assertSame(['DeliveryAddress.Zipcode: Invalid'], collect($orders[1]->deliveryAddressFields())
            ->firstWhere('key', 'zipcode')['errors']);
        $this->assertSame(DCPOrder::STATE_CLOSED, $orders[2]->refresh()->state);
    }

    /**
     * @return void
     */
    public function testCommands(): void
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);

        $this->approveTestDCPOrders($setup['organization']);

        $this->artisan('dcp:send-orders')->assertSuccessful();
        $this->assertSame(DCPOrder::STATE_SENT, $order->refresh()->state);

        $this->fakeDCPApi(get: $this->makeDCPStatusResponse(DCPOrder::STATUS_CLOSED, '2026-11-05'));

        $this->artisan('dcp:update-status')->assertSuccessful();
        $this->assertSame(DCPOrder::STATE_CLOSED, $order->refresh()->state);
    }

    /**
     * @param DCPOrder $order
     * @param string[] $events
     * @return void
     */
    protected function assertDCPOrderLogs(DCPOrder $order, array $events): void
    {
        $this->assertSame($events, $order->logs()->orderBy('id')->pluck('event')->all());
    }
}
