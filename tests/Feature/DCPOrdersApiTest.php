<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PhysicalCardType;
use App\Services\DCPService\Models\DCPOrder;
use App\Services\DCPService\Models\DCPOrderBulk;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestDCP;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class DCPOrdersApiTest extends TestCase
{
    use MakesTestDCP;
    use MakesTestFunds;
    use MakesApiRequests;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'sponsor',
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDCP();
    }

    /**
     * @return void
     */
    public function testIndexAndShow(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeTestDCPOrder($setup);
        $otherOrder = $this->makeTestDCPOrder($setup, cardRequestData: ['first_name' => 'Jane', 'last_name' => 'Smith']);

        // another organization's order is never listed
        $this->makeTestDCPOrder($this->makeTestDCPSetup());

        $this->assertIndexIds($organization, [], [$order->id, $otherOrder->id]);
        $this->assertIndexIds($organization, ['q' => 'jane smith'], [$otherOrder->id]);
        $this->assertIndexIds($organization, ['state' => DCPOrder::STATE_ERROR], []);

        $this->fakeDCPApi($this->makeDCPValidationResponse([
            'DeliveryAddress.Zipcode: The zipcode is invalid.',
            'OrderRows[0].TagIds[0].printedDateOfBirth: The date is invalid.',
        ]));

        $bulk = $this->approveTestDCPOrders($organization);
        DCPOrder::sendAll();

        $this->assertIndexIds($organization, ['state' => DCPOrder::STATE_ERROR], [$order->id, $otherOrder->id]);
        $this->assertIndexIds($organization, ['dcp_order_bulk_id' => $bulk->id], [$order->id, $otherOrder->id]);
        $this->assertIndexIds($organization, ['awaiting_approval' => 1], []);

        $response = $this->apiOrderRequest('get', $organization, "/$order->id")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.state', DCPOrder::STATE_ERROR)
            ->assertJsonPath('data.dcp_order_bulk_id', $bulk->id)
            ->assertJsonPath('data.label', 'Gelrepas')
            ->assertJsonPath('data.delivery_address.fullname', 'John Doe')
            ->assertJsonPath('data.delivery_address.city', 'NIJMEGEN')
            ->assertJsonPath('data.implementation.id', $setup['implementation']->id)
            ->assertJsonPath('data.dcp_connection_active', true)
            ->assertJsonPath('data.is_editable', true)
            ->assertJsonPath('data.is_duplicate', false)
            ->assertJsonPath('data.validation_errors', [
                'DeliveryAddress.Zipcode: The zipcode is invalid.',
                'OrderRows[0].TagIds[0].printedDateOfBirth: The date is invalid.',
            ]);

        $addressFields = collect($response->json('data.delivery_address_fields'))->keyBy('key');
        $tagFields = collect($response->json('data.tag_fields'))->keyBy('key');

        $this->assertSame(['DeliveryAddress.Zipcode: The zipcode is invalid.'], $addressFields['zipcode']['errors']);
        $this->assertSame([], $addressFields['city']['errors']);
        $this->assertTrue($addressFields['zipcode']['required']);
        $this->assertFalse($addressFields['firstname']['required']);

        $this->assertSame(['OrderRows[0].TagIds[0].printedDateOfBirth: The date is invalid.'], $tagFields['printedDateOfBirth']['errors']);
        $this->assertSame('17-05-1990', $tagFields['printedDateOfBirth']['value']);
        $this->assertSame('1245', $tagFields['printedCityCode']['value']);
        $this->assertNull($tagFields['printedCityCode']['source']);
        $this->assertSame('recipient.first_name', $tagFields['printedFirstName']['source']);
        $this->assertTrue($tagFields['printedFirstName']['required']);
        $this->assertFalse($tagFields['printedCardType']['required']);
    }

    /**
     * @return void
     */
    public function testStateGroupTabs(): void
    {
        [$setup, $orders] = $this->makeTestDCPOrdersInEachState();
        $organization = $setup['organization'];

        $groups = [
            'pending' => [$orders['pending']],
            'error' => [$orders['error']],
            'duplicate' => [$orders['duplicate']],
            'in_progress' => [$orders['approved'], $orders['sent']],
            'done' => [$orders['closed'], $orders['canceled']],
        ];

        foreach ($groups as $group => $ids) {
            $this->assertIndexIds($organization, ['state_group' => $group], $ids);
        }

        // the counts ignore the selected tab and the state
        $this->apiOrderRequest('get', $organization, data: [
            'state_group' => 'error',
            'state' => DCPOrder::STATE_ERROR,
            'with_counts' => 1,
        ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.counts', [
                'all' => 7,
                'pending' => 1,
                'error' => 1,
                'duplicate' => 1,
                'in_progress' => 2,
                'done' => 2,
            ]);

        // the counts follow the search
        DCPOrder::find($orders['duplicate'])->update(['order_id' => 'tab-search-1-' . $orders['duplicate']]);
        DCPOrder::find($orders['canceled'])->update(['order_id' => 'tab-search-2-' . $orders['canceled']]);

        $this->apiOrderRequest('get', $organization, data: ['q' => 'tab-search', 'with_counts' => 1])
            ->assertSuccessful()
            ->assertJsonPath('meta.counts', [
                'all' => 2,
                'pending' => 0,
                'error' => 0,
                'duplicate' => 1,
                'in_progress' => 0,
                'done' => 1,
            ]);

        // no counts without "with_counts"
        $this->apiOrderRequest('get', $organization)
            ->assertSuccessful()
            ->assertJsonMissingPath('meta.counts');

        $this->apiOrderRequest('get', $organization, data: ['state_group' => 'unknown'])
            ->assertJsonValidationErrorFor('state_group');
    }

    /**
     * @return void
     */
    public function testUpdateOrder(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeTestDCPOrder($setup);

        // nothing changed: nothing is stored or logged
        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order))
            ->assertSuccessful()
            ->assertJsonPath('data.payload_edited_at', null);

        $this->assertDCPOrderLogs($order, [DCPOrder::EVENT_CREATED]);

        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order, [
            'city' => ' Arnhem ',
        ], [
            'printedFirstName' => 'JOHNNY',
            'printedCardType' => '',
        ]))
            ->assertSuccessful()
            ->assertJsonPath('data.delivery_address.city', 'ARNHEM')
            ->assertJsonPath('data.payload_edited_by_employee_email', $organization->identity->email);

        $order->refresh();

        $this->assertNotNull($order->payload_edited_at);
        $this->assertSame('ARNHEM', $order->payload['deliveryAddress']['city']);
        $this->assertSame('JOHNNY', $order->payload['orderRows'][0]['tagIds'][0]['printedFirstName']);
        $this->assertArrayNotHasKey('printedCardType', $order->payload['orderRows'][0]['tagIds'][0]);
        $this->assertDCPOrderLogs($order, [DCPOrder::EVENT_CREATED, DCPOrder::EVENT_PAYLOAD_UPDATED]);

        $log = $order->logs()->where('event', DCPOrder::EVENT_PAYLOAD_UPDATED)->first();

        $this->assertSame($organization->identity_address, $log->identity_address);

        $this->assertEqualsCanonicalizing([
            'deliveryAddress.city',
            'tagIds.printedFirstName',
            'tagIds.printedCardType',
        ], $log->data['dcp_order_changed_fields']);

        // reset: the payload is built from the card request again
        $this->apiOrderRequest('post', $organization, "/$order->id/reset-payload")
            ->assertSuccessful()
            ->assertJsonPath('data.delivery_address.city', 'NIJMEGEN')
            ->assertJsonPath('data.payload_edited_at', null);

        $this->assertSame('John', $order->refresh()->payload['orderRows'][0]['tagIds'][0]['printedFirstName']);

        $this->assertDCPOrderLogs($order, [
            DCPOrder::EVENT_CREATED,
            DCPOrder::EVENT_PAYLOAD_UPDATED,
            DCPOrder::EVENT_PAYLOAD_RESET,
        ]);

        // nothing to reset anymore
        $this->apiOrderRequest('post', $organization, "/$order->id/reset-payload")->assertForbidden();

        // not editable once approved
        $this->approveTestDCPOrders($organization);

        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order))
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testUpdateOrderValidation(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeTestDCPOrder($setup);
        $address = $order->payload['deliveryAddress'];

        $assertInvalid = function (array $data, array $errors) use ($organization, $order) {
            $this->apiOrderRequest('patch', $organization, "/$order->id", $data)
                ->assertJsonValidationErrors($errors);
        };

        $assertInvalid([], ['delivery_address']);
        $assertInvalid(['delivery_address' => [...$address, 'unknown' => 'x']], ['delivery_address']);

        foreach (['address1', 'zipcode', 'city'] as $field) {
            $assertInvalid(['delivery_address' => [...$address, $field => '']], ["delivery_address.$field"]);
        }

        $assertInvalid(['delivery_address' => [
            ...$address,
            'address1' => str_repeat('a', 201),
        ]], ['delivery_address.address1']);

        // the full name is only required when it can't be made of the first and last name
        $assertInvalid(['delivery_address' => [
            ...Arr::except($address, ['firstname', 'lastname']),
            'fullname' => '',
        ]], ['delivery_address.fullname']);

        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order, [
            'fullname' => '',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ]))->assertSuccessful()->assertJsonPath('data.delivery_address.fullname', 'Jane Doe');

        // the tags of the mapping: required and unknown keys
        $assertInvalid(['delivery_address' => $address, 'tags' => ['printedFirstName' => '']], ['tags.printedFirstName']);
        $assertInvalid(['delivery_address' => $address, 'tags' => ['unknownTag' => 'x']], ['tags']);
        $assertInvalid(['delivery_address' => $address, 'tags' => [
            'printedCardType' => str_repeat('a', 501),
        ]], ['tags.printedCardType']);
    }

    /**
     * @return void
     */
    public function testRetry(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeTestDCPOrder($setup);

        // only failed orders can be retried
        $this->apiOrderRequest('post', $organization, "/$order->id/retry")->assertForbidden();

        $this->fakeDCPApi(Http::response([], 500));
        $this->approveTestDCPOrders($organization);
        DCPOrder::sendAll();

        $order->refresh()->update(['attempts' => DCPOrder::maxAttempts()]);
        $this->assertSame(DCPOrder::STATE_ERROR, $order->state);

        // DCP turned off globally
        Config::set('dcp.enabled', false);
        $this->apiOrderRequest('post', $organization, "/$order->id/retry")->assertForbidden();
        Config::set('dcp.enabled', true);

        $this->apiOrderRequest('post', $organization, "/$order->id/retry")
            ->assertSuccessful()
            ->assertJsonPath('data.state', DCPOrder::STATE_PENDING)
            ->assertJsonPath('data.attempts', 0)
            ->assertJsonPath('data.response_code', null);

        $this->assertSame(
            $organization->identity_address,
            $order->logs()->where('event', DCPOrder::EVENT_RETRIED)->value('identity_address'),
        );
    }

    /**
     * @return void
     */
    public function testSetOrderIdDcpValidation(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeDuplicateOrder($setup);
        $sentOrder = $this->makeTestDCPOrder($setup);

        $this->approveTestDCPOrders($organization);
        $this->fakeDCPApi($this->makeDCPCreatedResponse('DCP-TAKEN-' . $sentOrder->id));
        DCPOrder::sendAll();

        $this->assertNotNull($sentOrder->refresh()->order_id_dcp);

        $this->apiSetOrderIdDcp($organization, $order, '  ')
            ->assertJsonValidationErrorFor('order_id_dcp');

        $this->apiSetOrderIdDcp($organization, $order, $sentOrder->order_id_dcp)
            ->assertJsonValidationErrorFor('order_id_dcp');

        $this->apiSetOrderIdDcp($organization, $order, str_repeat('1', 101))
            ->assertJsonValidationErrorFor('order_id_dcp');

        $this->assertCount(0, $this->recordedDCPRequests('GET', '/api/Orders/DCP-TAKEN-' . $sentOrder->id));

        // DCP doesn't know the number
        $this->fakeDCPApi(get: Http::response(['message' => 'Not found'], 404));

        $this->apiSetOrderIdDcp($organization, $order, 'DCP-UNKNOWN')
            ->assertJsonValidationErrors(['order_id_dcp' => trans('dcp.errors.order_id_dcp_not_found')]);

        $this->fakeDCPApi(get: $this->makeDCPStatusResponse(DCPOrder::STATUS_NOT_FOUND));

        $this->apiSetOrderIdDcp($organization, $order, 'DCP-UNKNOWN')
            ->assertJsonValidationErrors(['order_id_dcp' => trans('dcp.errors.order_id_dcp_not_found')]);

        // DCP can't be reached or fails
        $this->fakeDCPApi(get: Http::response([], 500));

        $this->apiSetOrderIdDcp($organization, $order, 'DCP-5000')
            ->assertJsonValidationErrors(['order_id_dcp' => trans('dcp.errors.order_id_dcp_unverified')]);

        $this->fakeDCPApi(get: Http::failedConnection());

        $this->apiSetOrderIdDcp($organization, $order, 'DCP-5000')
            ->assertJsonValidationErrors(['order_id_dcp' => trans('dcp.errors.order_id_dcp_unverified')]);

        // nothing is stored or logged
        $order->refresh();

        $this->assertTrue($order->isDuplicate());
        $this->assertSame(0, $order->logs()->where('event', DCPOrder::EVENT_ORDER_ID_DCP_SET)->count());

        // a connection without credentials: DCP is not asked at all
        $setup['connection']->update(['username' => null]);
        $this->fakeDCPApi();

        $this->apiSetOrderIdDcp($organization, $order, 'DCP-5000')
            ->assertJsonValidationErrors(['order_id_dcp' => trans('dcp.errors.connection_missing')]);

        $this->assertCount(0, $this->recordedDCPRequests('GET', '/api/Orders/DCP-5000'));
    }

    /**
     * @return void
     */
    public function testSetOrderIdDcp(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeDuplicateOrder($setup);
        $orderIdDcp = 'DCP-SET-' . $order->id;

        $this->assertFalse(DCPOrder::queryToUpdateStatus()->whereKey($order->id)->exists());

        $this->apiOrderRequest('get', $organization, "/$order->id")
            ->assertSuccessful()
            ->assertJsonPath('data.is_duplicate', true);

        $this->fakeDCPApi(get: $this->makeDCPStatusResponse(DCPOrder::STATUS_SHIPPED, '2026-11-05'));

        $this->apiSetOrderIdDcp($organization, $order, " $orderIdDcp ")
            ->assertSuccessful()
            ->assertJsonPath('data.order_id_dcp', $orderIdDcp)
            ->assertJsonPath('data.state', DCPOrder::STATE_SENT)
            ->assertJsonPath('data.status_id', DCPOrder::STATUS_SHIPPED)
            ->assertJsonPath('data.status_message', 'Status ' . DCPOrder::STATUS_SHIPPED)
            ->assertJsonPath('data.is_duplicate', false);

        $order->refresh();

        $this->assertSame('2026-11-05', $order->shipped_at->format('Y-m-d'));
        $this->assertCount(1, $this->recordedDCPRequests('GET', "/api/Orders/$orderIdDcp"));
        $this->assertTrue(DCPOrder::queryToUpdateStatus()->whereKey($order->id)->exists());

        $logs = $order->logs()->orderBy('id')->get()->slice(-2)->values();

        $this->assertSame([DCPOrder::EVENT_ORDER_ID_DCP_SET, DCPOrder::EVENT_STATUS_CHANGED], $logs->pluck('event')->all());
        $this->assertSame($organization->identity_address, $logs[0]->identity_address);
        $this->assertSame($orderIdDcp, $logs[0]->data['dcp_order_order_id_dcp']);

        // a second attempt: the order isn't a duplicate anymore
        $this->apiSetOrderIdDcp($organization, $order, 'DCP-OTHER-' . $order->id)->assertForbidden();
        $this->assertSame($orderIdDcp, $order->refresh()->order_id_dcp);

        // the closed and canceled statuses close the order
        $statuses = [
            DCPOrder::STATUS_CLOSED => DCPOrder::STATE_CLOSED,
            DCPOrder::STATUS_CANCELLED => DCPOrder::STATE_CANCELED,
        ];

        foreach ($statuses as $statusId => $state) {
            $order = $this->makeDuplicateOrder($setup);
            $this->fakeDCPApi(get: $this->makeDCPStatusResponse($statusId));

            $this->apiSetOrderIdDcp($organization, $order, "DCP-$statusId-$order->id")
                ->assertSuccessful()
                ->assertJsonPath('data.state', $state)
                ->assertJsonPath('data.is_duplicate', false);
        }
    }

    /**
     * @return void
     */
    public function testBulks(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];

        $this->apiBulkRequest('post', $organization)
            ->assertStatus(422)
            ->assertJsonPath('message', trans('dcp.errors.nothing_to_approve'));

        $orders = [$this->makeTestDCPOrder($setup), $this->makeTestDCPOrder($setup)];

        // DCP turned off globally
        Config::set('dcp.enabled', false);
        $this->apiBulkRequest('post', $organization)->assertForbidden();
        Config::set('dcp.enabled', true);

        $bulkId = $this->apiBulkRequest('post', $organization)
            ->assertSuccessful()
            ->assertJsonPath('data.state', DCPOrderBulk::STATE_PENDING)
            ->assertJsonPath('data.dcp_orders_count', 2)
            ->assertJsonPath('data.dcp_orders_pending_count', 2)
            ->assertJsonPath('data.employee_email', $organization->identity->email)
            ->json('data.id');

        foreach ($orders as $order) {
            $order->refresh();

            $this->assertSame($bulkId, $order->dcp_order_bulk_id);
            $this->assertSame(
                $organization->identity_address,
                $order->logs()->where('event', DCPOrder::EVENT_APPROVED)->value('identity_address'),
            );
        }

        $this->fakeDCPApi(Http::sequence()
            ->pushResponse($this->makeDCPCreatedResponse('DCP-BULK-' . $bulkId))
            ->pushResponse($this->makeDCPValidationResponse(['DeliveryAddress.City: The city is invalid.'])));

        DCPOrder::sendAll();

        $this->apiBulkRequest('get', $organization, "/$bulkId")
            ->assertSuccessful()
            ->assertJsonPath('data.state', DCPOrderBulk::STATE_ERROR)
            ->assertJsonPath('data.dcp_orders_count', 2)
            ->assertJsonPath('data.dcp_orders_pending_count', 0)
            ->assertJsonPath('data.dcp_orders_sent_count', 1)
            ->assertJsonPath('data.dcp_orders_error_count', 1);

        $this->apiBulkRequest('get', $organization)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bulkId)
            ->assertJsonPath('data.0.dcp_orders_sent_count', 1);

        $this->apiBulkRequest('get', $organization, data: ['state' => DCPOrderBulk::STATE_SENT])
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');

        // another organization's bulk
        $otherOrganization = $this->makeTestDCPSetup()['organization'];

        $this->apiBulkRequest('get', $otherOrganization, "/$bulkId")->assertForbidden();
    }

    /**
     * @return void
     */
    public function testPermissions(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeTestDCPOrder($setup);

        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_VOUCHERS])->identity;
        $manager = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_VOUCHERS])->identity;
        $other = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_IMPLEMENTATION])->identity;

        // view only
        $this->apiOrderRequest('get', $organization, identity: $viewer)->assertSuccessful();
        $this->apiOrderRequest('get', $organization, "/$order->id", identity: $viewer)->assertSuccessful();
        $this->apiBulkRequest('get', $organization, identity: $viewer)->assertSuccessful();

        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order), $viewer)
            ->assertForbidden();

        $this->apiBulkRequest('post', $organization, identity: $viewer)->assertForbidden();

        // no access to the orders
        $this->apiOrderRequest('get', $organization, identity: $other)->assertForbidden();
        $this->apiOrderRequest('get', $organization, "/$order->id", identity: $other)->assertForbidden();
        $this->apiBulkRequest('get', $organization, identity: $other)->assertForbidden();

        // manage
        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order, [
            'city' => 'Arnhem',
        ]), $manager)->assertSuccessful();

        $this->apiOrderRequest('post', $organization, "/$order->id/reset-payload", identity: $manager)
            ->assertSuccessful();

        // the order of another organization: not through the own organization nor the other one
        $otherSetup = $this->makeTestDCPSetup();
        $otherOrder = $this->makeTestDCPOrder($otherSetup);

        $this->apiOrderRequest('get', $organization, "/$otherOrder->id")->assertForbidden();
        $this->apiOrderRequest('patch', $organization, "/$otherOrder->id", $this->makeOrderData($otherOrder))
            ->assertForbidden();

        $this->apiOrderRequest('get', $otherSetup['organization'], "/$order->id")->assertForbidden();

        // physical cards turned off for the organization: no access to the orders and bulks anymore
        $organization->forceFill(['allow_physical_cards' => false])->save();

        $this->apiOrderRequest('get', $organization)->assertForbidden();
        $this->apiOrderRequest('get', $organization, "/$order->id")->assertForbidden();
        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order))->assertForbidden();
        $this->apiOrderRequest('post', $organization, "/$order->id/reset-payload")->assertForbidden();
        $this->apiBulkRequest('get', $organization)->assertForbidden();
        $this->apiBulkRequest('post', $organization)->assertForbidden();

        $this->assertNull($order->refresh()->dcp_order_bulk_id);
    }

    /**
     * @return void
     */
    public function testSetOrderIdDcpPermissions(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];
        $order = $this->makeDuplicateOrder($setup);
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_VOUCHERS])->identity;
        $otherOrganization = $this->makeTestDCPSetup()['organization'];

        $this->fakeDCPApi(get: $this->makeDCPStatusResponse(DCPOrder::STATUS_PROCESSING));

        $this->apiSetOrderIdDcp($organization, $order, 'DCP-1', $viewer)->assertForbidden();
        $this->apiSetOrderIdDcp($otherOrganization, $order, 'DCP-1')->assertForbidden();
        $this->apiOrderRequest('post', $organization, "/$order->id/retry", identity: $viewer)->assertForbidden();

        $this->assertTrue($order->refresh()->isDuplicate());
        $this->assertCount(0, $this->recordedDCPRequests('GET', '/api/Orders/DCP-1'));
    }

    /**
     * Switching off DCP for the card type stops the orders which aren't sent yet.
     *
     * @return void
     */
    public function testSwitchingOffDCPForCardType(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];

        $sentOrder = $this->makeTestDCPOrder($setup);
        $this->approveTestDCPOrders($organization);
        DCPOrder::sendAll();

        $approvedOrder = $this->makeTestDCPOrder($setup);
        $bulk = $this->approveTestDCPOrders($organization);
        $waitingOrder = $this->makeTestDCPOrder($setup);

        $this->assertSame(DCPOrder::STATE_SENT, $sentOrder->refresh()->state);

        $this->apiCardTypeRequest($organization, $setup['card_type'], false)
            ->assertSuccessful()
            ->assertJsonPath('data.send_to_dcp', false);

        foreach ([$approvedOrder, $waitingOrder] as $order) {
            $order->refresh();

            $this->assertSame(DCPOrder::STATE_ERROR, $order->state);
            $this->assertSame(trans('dcp.errors.card_type_not_sent'), $order->status_message);
            $this->assertSame(DCPOrder::maxAttempts(), $order->attempts);
        }

        $this->assertSame(DCPOrder::STATE_SENT, $sentOrder->refresh()->state);
        $this->assertSame(DCPOrderBulk::STATE_ERROR, $bulk->refresh()->state);

        // nothing to approve or send anymore, a retry stays blocked
        $this->apiBulkRequest('post', $organization)->assertStatus(422);
        DCPOrder::sendAll();

        $this->assertCount(1, $this->recordedDCPRequests('POST', '/api/Orders'));

        $this->apiOrderRequest('post', $organization, "/$waitingOrder->id/retry")
            ->assertSuccessful()
            ->assertJsonPath('data.state', DCPOrder::STATE_ERROR)
            ->assertJsonPath('data.status_message', trans('dcp.errors.card_type_not_sent'));

        // switched on again: the order can be retried
        $this->apiCardTypeRequest($organization, $setup['card_type'], true)->assertSuccessful();

        $this->apiOrderRequest('post', $organization, "/$waitingOrder->id/retry")
            ->assertSuccessful()
            ->assertJsonPath('data.state', DCPOrder::STATE_PENDING);
    }

    /**
     * @return void
     */
    public function testLogs(): void
    {
        $setup = $this->makeTestDCPSetup();
        $organization = $setup['organization'];

        $order = $this->makeTestDCPOrder($setup, ['birth_date' => '1987-03-21'], [
            'first_name' => 'Zyxwvutest',
            'last_name' => 'Qponmltest',
        ]);

        $this->apiOrderRequest('patch', $organization, "/$order->id", $this->makeOrderData($order, [
            'address1' => 'Kerkstraatlogtest 7',
        ]))->assertSuccessful();

        $this->apiOrderRequest('post', $organization, "/$order->id/reset-payload")->assertSuccessful();
        $this->apiBulkRequest('post', $organization)->assertSuccessful();

        $this->fakeDCPApi(Http::response([], 500));
        DCPOrder::sendAll();

        $this->apiOrderRequest('post', $organization, "/$order->id/retry")->assertSuccessful();

        $this->fakeDCPApi(
            $this->makeDCPCreatedResponse('DCP-LOG-' . $order->id),
            $this->makeDCPStatusResponse(DCPOrder::STATUS_SHIPPED, '2026-11-05'),
        );

        DCPOrder::sendAll();
        DCPOrder::find($order->id)->updateStatus();

        $events = [
            DCPOrder::EVENT_CREATED => null,
            DCPOrder::EVENT_PAYLOAD_UPDATED => $organization->identity_address,
            DCPOrder::EVENT_PAYLOAD_RESET => $organization->identity_address,
            DCPOrder::EVENT_APPROVED => $organization->identity_address,
            DCPOrder::EVENT_FAILED => null,
            DCPOrder::EVENT_RETRIED => $organization->identity_address,
            DCPOrder::EVENT_SENT => null,
            DCPOrder::EVENT_STATUS_CHANGED => null,
        ];

        $logs = $order->logs()->orderBy('id')->get();

        $this->assertSame(array_keys($events), $logs->pluck('event')->all());

        foreach ($logs->filter(fn ($log) => $events[$log->event]) as $log) {
            $this->assertSame($events[$log->event], $log->identity_address, "Employee of $log->event");
        }

        $response = $this->getJson(sprintf(
            '/api/v1/platform/organizations/%s/logs?%s',
            $organization->id,
            http_build_query(['loggable' => ['dcp_order'], 'loggable_id' => $order->id, 'per_page' => 50]),
        ), $this->makeApiHeaders($organization->identity));

        $response->assertSuccessful()->assertJsonCount(count($events), 'data');

        $locales = collect($response->json('data'))->pluck('event_locale', 'event');

        $this->assertSame(trans('events.dcp_order.dashboard.created'), $locales['created']);
        $this->assertStringContainsString("DCP id: DCP-LOG-$order->id", $locales['sent']);
        $this->assertStringContainsString('#' . $order->dcp_order_bulk_id, $locales['approved']);
        $this->assertStringContainsString('deliveryAddress.address1', $locales['payload_updated']);
        $this->assertStringContainsString(trans('dcp.statuses.' . DCPOrder::STATUS_SHIPPED), $locales['status_changed']);
        $this->assertStringContainsString("#$order->id", $response->json('data.0.loggable_locale'));

        // the employees without voucher permissions don't see the logs
        $employee = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_VOUCHERS]);

        $this->getJson(sprintf(
            '/api/v1/platform/organizations/%s/logs?%s',
            $organization->id,
            http_build_query(['loggable' => ['dcp_order'], 'loggable_id' => $order->id]),
        ), $this->makeApiHeaders($employee->identity))->assertSuccessful()->assertJsonCount(0, 'data');
    }

    /**
     * @param DCPOrder $order
     * @param array $address
     * @param array $tags
     * @return array
     */
    protected function makeOrderData(DCPOrder $order, array $address = [], array $tags = []): array
    {
        return [
            'delivery_address' => [...$order->payload['deliveryAddress'], ...$address],
            'tags' => [...Arr::except($order->payload['orderRows'][0]['tagIds'][0], 'key'), ...$tags],
        ];
    }

    /**
     * @param array $setup
     * @return DCPOrder
     */
    protected function makeDuplicateOrder(array $setup): DCPOrder
    {
        $order = $this->makeTestDCPOrder($setup);

        $this->fakeDCPApi($this->makeDCPDuplicateResponse());
        $this->approveTestDCPOrders($setup['organization']);
        DCPOrder::sendAll();

        $this->assertTrue($order->refresh()->isDuplicate());

        return $order;
    }

    /**
     * @param Organization $organization
     * @param PhysicalCardType $cardType
     * @param bool $sendToDCP
     * @return TestResponse
     */
    protected function apiCardTypeRequest(
        Organization $organization,
        PhysicalCardType $cardType,
        bool $sendToDCP,
    ): TestResponse {
        return $this->apiRequest('patch', "/api/v1/platform/organizations/$organization->id/physical-card-types/$cardType->id", [
            'name' => $cardType->name,
            'send_to_dcp' => $sendToDCP,
        ], $organization->identity);
    }

    /**
     * @param Organization $organization
     * @param DCPOrder $order
     * @param string $orderIdDcp
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiSetOrderIdDcp(
        Organization $organization,
        DCPOrder $order,
        string $orderIdDcp,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->apiOrderRequest('post', $organization, "/$order->id/order-id-dcp", [
            'order_id_dcp' => $orderIdDcp,
        ], $identity);
    }

    /**
     * @param string $method
     * @param Organization $organization
     * @param string $path
     * @param array $data
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiOrderRequest(
        string $method,
        Organization $organization,
        string $path = '',
        array $data = [],
        ?Identity $identity = null,
    ): TestResponse {
        return $this->apiRequest($method, "/api/v1/platform/organizations/$organization->id/dcp-orders$path", $data, $identity ?: $organization->identity);
    }

    /**
     * @param string $method
     * @param Organization $organization
     * @param string $path
     * @param array $data
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiBulkRequest(
        string $method,
        Organization $organization,
        string $path = '',
        array $data = [],
        ?Identity $identity = null,
    ): TestResponse {
        return $this->apiRequest($method, "/api/v1/platform/organizations/$organization->id/dcp-order-bulks$path", $data, $identity ?: $organization->identity);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    protected function apiRequest(string $method, string $url, array $data, Identity $identity): TestResponse
    {
        $headers = $this->makeApiHeaders($identity);

        if ($method === 'get') {
            return $this->getJson($data ? $url . '?' . http_build_query($data) : $url, $headers);
        }

        return $this->json($method, $url, $data, $headers);
    }

    /**
     * @param Organization $organization
     * @param array $filters
     * @param int[] $expectedIds
     * @return void
     */
    protected function assertIndexIds(Organization $organization, array $filters, array $expectedIds): void
    {
        $response = $this->apiOrderRequest('get', $organization, data: [...$filters, 'per_page' => 100]);

        $response->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            array_values($expectedIds),
            array_column($response->json('data'), 'id'),
            'Filters: ' . json_encode($filters),
        );
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
