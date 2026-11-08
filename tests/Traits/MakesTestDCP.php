<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundPhysicalCardType;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\PhysicalCardRequest;
use App\Models\PhysicalCardType;
use App\Models\RecordType;
use App\Models\Voucher;
use App\Services\DCPService\Models\DCPConnection;
use App\Services\DCPService\Models\DCPOrder;
use App\Services\DCPService\Models\DCPOrderBulk;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Monolog\Handler\NullHandler;
use ReflectionObject;
use Throwable;

trait MakesTestDCP
{
    protected string $dcpTestUrl = 'https://dcp-api.test';

    /**
     * Enable DCP with the test url, don't write the errors of the faked responses to the DCP log
     * and don't allow any real request to DCP.
     *
     * @return void
     */
    protected function setupDCP(): void
    {
        Config::set('dcp.enabled', true);
        Config::set('dcp.allowed_urls', [$this->dcpTestUrl]);
        Config::set('logging.channels.dcp', ['driver' => 'monolog', 'handler' => NullHandler::class]);

        $this->fakeDCPApi();
        Http::preventStrayRequests();
    }

    /**
     * The mapping used by Gelrepas (Nijmegen).
     *
     * @return array
     */
    protected function makeDCPGelrepasOrderTypes(): array
    {
        return [
            'physical_card_request' => [
                'label' => 'Gelrepas',
                'item' => 'card',
                'tags' => [
                    ['key' => 'printedCardType', 'source' => 'card_type.name'],
                    ['key' => 'printedFirstName', 'source' => 'recipient.first_name', 'required' => true],
                    ['key' => 'printedLastName', 'source' => 'recipient.last_name', 'required' => true],
                    [
                        'key' => 'printedDateOfBirth',
                        'source' => 'recipient.birth_date',
                        'format' => 'date:d-m-Y',
                        'required' => true,
                    ],
                    ['key' => 'printedCityCode', 'value' => '1245', 'required' => true],
                    ['key' => 'printedQrCode', 'source' => 'voucher.qr_code', 'required' => true],
                    ['key' => 'printedCardNumber', 'source' => 'card.number', 'required' => true],
                ],
            ],
        ];
    }

    /**
     * @param Implementation $implementation
     * @param array $data
     * @return DCPConnection
     */
    protected function makeTestDCPConnection(Implementation $implementation, array $data = []): DCPConnection
    {
        return DCPConnection::create([
            'implementation_id' => $implementation->id,
            'enabled' => true,
            'url' => $this->dcpTestUrl,
            'username' => 'dcp-test-user',
            'password' => 'dcp-test-password',
            'order_types' => $this->makeDCPGelrepasOrderTypes(),
            ...$data,
        ]);
    }

    /**
     * Organization with physical cards, a fund with a card type sent to DCP and a DCP connection on the implementation.
     *
     * @param array $connectionData
     * @return array{
     *     organization: Organization,
     *     implementation: Implementation,
     *     fund: Fund,
     *     card_type: PhysicalCardType,
     *     fund_card_type: FundPhysicalCardType,
     *     connection: DCPConnection,
     * }
     */
    protected function makeTestDCPSetup(array $connectionData = []): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_physical_cards' => true,
        ]);

        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization, [], ['allow_physical_cards' => true], $implementation);

        $cardType = $organization->physical_card_types()->forceCreate([
            'name' => 'Gelrepas ' . $this->faker->word(),
            'description' => $this->faker->sentence(),
            'code_blocks' => 4,
            'code_block_size' => 4,
            'send_to_dcp' => true,
        ]);

        $fundCardType = $fund->fund_physical_card_types()->create([
            'physical_card_type_id' => $cardType->id,
            'allow_physical_card_linking' => true,
            'allow_physical_card_requests' => true,
        ]);

        return [
            'organization' => $organization,
            'implementation' => $implementation,
            'fund' => $fund,
            'card_type' => $cardType,
            'fund_card_type' => $fundCardType,
            'connection' => $this->makeTestDCPConnection($implementation, $connectionData),
        ];
    }

    /**
     * Requester with the records used by the mapping (birth date, the name is taken from the card request).
     *
     * @param array $records
     * @return Identity
     */
    protected function makeTestDCPRequester(array $records = []): Identity
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $records = [
            'birth_date' => '1990-05-17',
            ...$records,
        ];

        foreach (array_filter($records) as $key => $value) {
            $identity->makeRecord(RecordType::findByKey($key), $value);
        }

        return $identity;
    }

    /**
     * @param Voucher $voucher
     * @param PhysicalCardType $type
     * @param array $data
     * @return PhysicalCardRequest
     */
    protected function makeTestDCPCardRequest(
        Voucher $voucher,
        PhysicalCardType $type,
        array $data = [],
    ): PhysicalCardRequest {
        return $voucher->makePhysicalCardRequest([
            ...$this->makeDCPAddressData(),
            'physical_card_type_id' => $type->id,
            ...$data,
        ]);
    }

    /**
     * @return array
     */
    protected function makeDCPAddressData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address' => 'Hoofdstraat',
            'house' => '12',
            'house_addition' => 'A',
            'postcode' => '1234 ab',
            'city' => 'Nijmegen',
        ];
    }

    /**
     * Voucher of the fund with a physical card request and the DCP order created for it.
     *
     * @param array $setup
     * @param array $records records of the requester
     * @param array $cardRequestData
     * @return DCPOrder|null
     */
    protected function makeTestDCPOrder(array $setup, array $records = [], array $cardRequestData = []): ?DCPOrder
    {
        $voucher = $this->makeTestVoucher($setup['fund'], $this->makeTestDCPRequester($records), amount: 100);

        return $this->makeTestDCPCardRequest($voucher, $setup['card_type'], $cardRequestData)->dcp_order()->first();
    }

    /**
     * One order in each state of the dashboard tabs, all except "pending" are in a bulk.
     *
     * @return array{0: array, 1: array<string, int>} the setup and the ids of the orders by state
     */
    protected function makeTestDCPOrdersInEachState(): array
    {
        $setup = $this->makeTestDCPSetup();
        $order = $this->makeTestDCPOrder($setup);
        $bulk = DCPOrderBulk::create([
            'organization_id' => $setup['organization']->id,
            'employee_id' => $setup['organization']->employees()->first()->id,
            'state' => DCPOrderBulk::STATE_PENDING,
        ]);

        $states = [
            'pending' => ['state' => DCPOrder::STATE_PENDING, 'dcp_order_bulk_id' => null],
            'approved' => ['state' => DCPOrder::STATE_PENDING],
            'error' => ['state' => DCPOrder::STATE_ERROR],
            'duplicate' => ['state' => DCPOrder::STATE_SENT, 'order_id_dcp' => null],
            'sent' => ['state' => DCPOrder::STATE_SENT, 'order_id_dcp' => 'DCP-' . Str::random(8)],
            'closed' => ['state' => DCPOrder::STATE_CLOSED, 'order_id_dcp' => 'DCP-' . Str::random(8)],
            'canceled' => ['state' => DCPOrder::STATE_CANCELED, 'order_id_dcp' => 'DCP-' . Str::random(8)],
        ];

        $orders = array_map(function (array $attributes) use ($order, $bulk) {
            $copy = $order->replicate();
            $copy->forceFill([
                'order_id' => (string) Str::uuid(),
                'dcp_order_bulk_id' => $bulk->id,
                ...$attributes,
            ])->save();

            return $copy->id;
        }, $states);

        $order->delete();

        return [$setup, $orders];
    }

    /**
     * Approve the pending orders of the organization (as the scheduler only sends approved orders).
     *
     * @param Organization $organization
     * @return DCPOrderBulk|null
     */
    protected function approveTestDCPOrders(Organization $organization): ?DCPOrderBulk
    {
        return DCPOrderBulk::makeBulk($organization, $organization->employees()->first());
    }

    /**
     * Fake the DCP API, the responses can be a response or a sequence (Http::sequence()).
     *
     * @param PromiseInterface|callable|null $create POST /api/Orders
     * @param PromiseInterface|callable|null $get GET /api/Orders/{orderIdDCP}
     * @return void
     */
    protected function fakeDCPApi(
        PromiseInterface|callable|null $create = null,
        PromiseInterface|callable|null $get = null,
    ): void {
        self::clearExistingDCPFakes();

        Http::fake([
            "$this->dcpTestUrl/api/Orders" => $create ?? $this->makeDCPCreatedResponse(),
            "$this->dcpTestUrl/api/Orders/*" => $get ?? $this->makeDCPStatusResponse(DCPOrder::STATUS_WEB_API_ACCEPTED),
        ]);
    }

    /**
     * @param string $orderIdDcp
     * @return PromiseInterface
     */
    protected function makeDCPCreatedResponse(string $orderIdDcp = 'DCP-0001'): PromiseInterface
    {
        return Http::response([
            'orderIdDCP' => $orderIdDcp,
            'statusId' => DCPOrder::STATUS_WEB_API_ACCEPTED,
            'statusMessage' => 'Web API accepted',
        ], 201);
    }

    /**
     * @param int $statusId
     * @param string|null $shippingDate
     * @return PromiseInterface
     */
    protected function makeDCPStatusResponse(int $statusId, ?string $shippingDate = null): PromiseInterface
    {
        return Http::response(array_filter([
            'statusId' => $statusId,
            'statusMessage' => "Status $statusId",
            'shippingDate' => $shippingDate,
        ]), 200);
    }

    /**
     * @param array $errors
     * @return PromiseInterface
     */
    protected function makeDCPValidationResponse(array $errors): PromiseInterface
    {
        return Http::response(['validationErrors' => $errors], 422);
    }

    /**
     * @return PromiseInterface
     */
    protected function makeDCPDuplicateResponse(): PromiseInterface
    {
        return $this->makeDCPValidationResponse([
            'ReferenceClient: Order with this ReferenceClient already exists.',
        ]);
    }

    /**
     * @param string $method
     * @param string $path
     * @return Request[]
     */
    protected function recordedDCPRequests(string $method, string $path): array
    {
        return Http::recorded(function (Request $request) use ($method, $path) {
            return $request->method() === $method && $request->url() === "$this->dcpTestUrl$path";
        })->map(fn (array $pair) => $pair[0])->all();
    }

    /**
     * Http::fake() appends the stubs, so the previous fakes would keep answering first.
     *
     * @return void
     */
    protected static function clearExistingDCPFakes(): void
    {
        try {
            $reflection = new ReflectionObject(Http::getFacadeRoot());
            $property = $reflection->getProperty('stubCallbacks');
            $property->setValue(Http::getFacadeRoot(), collect());
        } catch (Throwable) {
        }
    }
}
