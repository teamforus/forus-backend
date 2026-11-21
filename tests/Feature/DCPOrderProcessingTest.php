<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Modules\DCP\Data\DCPDeliveryAddress;
use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Data\GelrepasCardData;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Models\DCPConnection;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Models\DCPOrderBulk;
use App\Modules\DCP\Services\DCPLogService;
use App\Modules\DCP\Services\DCPOrderProcessingService;
use App\Modules\DCP\Services\DCPOrderService;
use App\Modules\VoucherPass\Models\VoucherPass;
use App\Services\EventLogService\Models\EventLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestDCPOrders;
use Throwable;

class DCPOrderProcessingTest extends TestCase
{
    use MakesTestDCPOrders;

    private ?Organization $organization = null;
    private ?Identity $identity = null;
    private ?DCPConnection $dcpConnection = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Config::set('dcp.enabled', true);
        Config::set('dcp.environments.sandbox', 'https://dcp.test');
        Config::set('dcp.sending_claim_timeout', 300);
        $this->freezeTime();

        $this->assertSame(0, DB::transactionLevel());
        $this->assertFalse(DCPOrder::whereIn('state', [
            DCPOrderState::APPROVED, DCPOrderState::SENDING, DCPOrderState::ACCEPTED,
        ])->exists(), 'Processing tests require a disposable database without existing processable DCP orders.');
    }

    /**
     * @throws Throwable
     * @return void
     */
    protected function tearDown(): void
    {
        try {
            $this->cleanupProcessingFixture();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSendingUsesFrozenPayloadAndStoresAcceptedResultOnce(): void
    {
        $order = $this->makeProcessingOrder();
        $profile = $this->organization->findOrMakeProfile($this->identity);
        $this->makeProfileRecord($profile, RecordType::findByKey('initials'), 'Changed initials');
        $payload = $order->order_rows;
        $claimObserved = false;

        Http::fake(['https://dcp.test/api/Orders' => function (Request $request) use ($order, &$claimObserved) {
            $claimObserved = DB::transactionLevel() === 0 && $order->fresh()->state === DCPOrderState::SENDING;
            $this->assertSame('T.O.', $request->data()['orderRows'][0]['tagIds'][0]['printedFirstName']);
            $this->assertSame($order->order_id, $request->data()['orderId']);

            return Http::response($this->orderResponse($order), 201);
        }]);

        $service = resolve(DCPOrderProcessingService::class);
        $service->sendApprovedOrders();
        $service->sendApprovedOrders();

        $this->assertTrue($claimObserved);
        $this->assertSame(DCPOrderState::ACCEPTED, $order->refresh()->state);
        $this->assertSame('remote-' . $order->id, $order->order_id_dcp);
        $this->assertSame(100, $order->status_id);
        $this->assertNotNull($order->submitted_at);
        $this->assertSame($payload, $order->order_rows);
        $this->assertSame(['created', 'sending', 'accepted'], $order->logs()->orderBy('id')->pluck('event')->all());
        Http::assertSentCount(1);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testInvalidatedSourceBlocksBeforeSending(): void
    {
        $revoked = $this->makeProcessingOrder();
        $changedCode = $this->makeProcessingOrder();
        $revoked->orderable->update(['print_requested' => false]);
        $changedCode->orderable->update(['code' => VoucherPass::generateCode()]);
        $snapshots = [$revoked->order_rows, $changedCode->order_rows];
        resolve(DCPOrderProcessingService::class)->sendApprovedOrders();

        foreach ([$revoked, $changedCode] as $index => $order) {
            $this->assertSame(DCPOrderState::BLOCKED, $order->refresh()->state);
            $this->assertTrue(Str::isUuid($order->diagnostic_id));
            $this->assertSame($snapshots[$index], $order->order_rows);
            $this->assertSame(['created', 'blocked'], $order->logs()->orderBy('id')->pluck('event')->all());
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDisabledConnectionLeavesApprovedOrderUnsent(): void
    {
        $order = $this->makeProcessingOrder();
        $this->dcpConnection->update(['enabled' => false]);
        $before = $order->getAttributes();
        resolve(DCPOrderProcessingService::class)->sendApprovedOrders();

        $this->assertSame($before, $order->fresh()->getAttributes());
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectedSubmissionIsRecordedWithoutAutomaticRetry(): void
    {
        $order = $this->makeProcessingOrder();

        Http::fake(['https://dcp.test/api/Orders' => Http::response([
            'validationErrors' => ['zipcode' => ['Invalid postcode']],
        ], 422)]);

        $service = resolve(DCPOrderProcessingService::class);
        $service->sendApprovedOrders();
        $service->sendApprovedOrders();

        $this->assertSame(DCPOrderState::REJECTED, $order->refresh()->state);
        $this->assertNull($order->order_id_dcp);
        $this->assertTrue(Str::isUuid($order->diagnostic_id));
        $this->assertStringContainsString('Invalid postcode', $order->status_message);
        $this->assertSame(422, $order->submission_response['http_status']);
        Http::assertSentCount(1);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnknownSubmissionIsFrozenAndNotRetried(): void
    {
        $order = $this->makeProcessingOrder();
        $snapshot = $order->order_rows;
        $attempts = 0;

        Http::fake(['https://dcp.test/api/Orders' => function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('Injected ambiguous timeout.');
        }]);

        $service = resolve(DCPOrderProcessingService::class);
        $service->sendApprovedOrders();
        $service->sendApprovedOrders();

        $this->assertSame(DCPOrderState::UNKNOWN, $order->refresh()->state);
        $this->assertTrue(Str::isUuid($order->diagnostic_id));
        $this->assertSame($snapshot, $order->order_rows);
        $this->assertNull($order->order_id_dcp);
        $this->assertFalse($order->canUpdateDraft());
        $this->assertSame(1, $attempts);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAbandonedSendingClaimBecomesUnknown(): void
    {
        $expired = $this->makeProcessingOrder(DCPOrderState::SENDING);
        $fresh = $this->makeProcessingOrder(DCPOrderState::SENDING);
        $expired->update(['sending_at' => Carbon::now()->subMinutes(10)]);
        $fresh->update(['sending_at' => Carbon::now()]);
        $before = $fresh->getAttributes();
        resolve(DCPOrderProcessingService::class)->sendApprovedOrders();

        $this->assertSame(DCPOrderState::UNKNOWN, $expired->refresh()->state);
        $this->assertTrue(Str::isUuid($expired->diagnostic_id));
        $this->assertSame($before, $fresh->fresh()->getAttributes());
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSubmissionFailureDoesNotStopNextOrder(): void
    {
        $failed = $this->makeProcessingOrder();
        $accepted = $this->makeProcessingOrder();
        $attempts = [];

        Http::fake(['https://dcp.test/api/Orders' => function (Request $request) use ($failed, $accepted, &$attempts) {
            $attempts[] = $request->data()['orderId'];

            if ($request->data()['orderId'] === $failed->order_id) {
                throw new ConnectionException('Injected first-order failure.');
            }

            return Http::response($this->orderResponse($accepted), 201);
        }]);

        resolve(DCPOrderProcessingService::class)->sendApprovedOrders();

        $this->assertSame([$failed->order_id, $accepted->order_id], $attempts);
        $this->assertSame(DCPOrderState::UNKNOWN, $failed->refresh()->state);
        $this->assertSame(DCPOrderState::ACCEPTED, $accepted->refresh()->state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPollingUpdatesShippingAndStopsAtTerminalStatus(): void
    {
        $closed = $this->makeProcessingOrder(DCPOrderState::ACCEPTED);
        $canceled = $this->makeProcessingOrder(DCPOrderState::ACCEPTED);
        $closedResponses = Http::sequence()
            ->push([...$this->orderResponse($closed, 500), 'shippingDate' => '2026-11-13T10:00:00Z'])
            ->push($this->orderResponse($closed, 1000));

        Http::fake([
            'https://dcp.test/api/Orders/' . $closed->order_id_dcp => $closedResponses,
            'https://dcp.test/api/Orders/' . $canceled->order_id_dcp => Http::response(
                $this->orderResponse($canceled, 9001),
            ),
        ]);

        $service = resolve(DCPOrderProcessingService::class);
        $service->updateStatuses();
        $this->assertSame(500, $closed->refresh()->status_id);
        $this->assertSame('2026-11-13 10:00:00', $closed->shipped_at->clone()->utc()->toDateTimeString());
        $service->updateStatuses();
        $service->updateStatuses();

        $this->assertSame(DCPOrderState::CLOSED, $closed->refresh()->state);
        $this->assertNotNull($closed->shipped_at);
        $this->assertSame(DCPOrderState::CANCELED, $canceled->refresh()->state);
        $this->assertSame(9001, $canceled->status_id);
        Http::assertSentCount(3);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPollingFailurePreservesLastKnownRemoteState(): void
    {
        $order = $this->makeProcessingOrder(DCPOrderState::ACCEPTED);
        $order->update(['status_id' => 500, 'shipped_at' => Carbon::now()->subDay()]);
        $shippedAt = $order->shipped_at->toDateTimeString();
        Http::fake(['https://dcp.test/api/Orders/*' => Http::response([], 503)]);
        resolve(DCPOrderProcessingService::class)->updateStatuses();

        $this->assertSame(DCPOrderState::ACCEPTED, $order->refresh()->state);
        $this->assertSame(500, $order->status_id);
        $this->assertSame($shippedAt, $order->shipped_at->toDateTimeString());
        $this->assertNotNull($order->last_status_checked_at);
        $this->assertTrue(Str::isUuid($order->diagnostic_id));
        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOlderPollingResponseCannotOverwriteNewerState(): void
    {
        $order = $this->makeProcessingOrder(DCPOrderState::ACCEPTED);
        $newer = [];

        Http::fake(['https://dcp.test/api/Orders/*' => function () use ($order, &$newer) {
            $order->update([
                'status_id' => 500,
                'last_status_checked_at' => Carbon::now()->addMinute(),
                'shipped_at' => Carbon::now(),
            ]);

            $newer = $order->getAttributes();

            return Http::response($this->orderResponse($order, 300));
        }]);

        resolve(DCPOrderProcessingService::class)->updateStatuses();

        $this->assertNotEmpty($newer);
        $this->assertSame($newer, $order->fresh()->getAttributes());
        Http::assertSentCount(1);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testGlobalDisablePreventsSendingAndPolling(): void
    {
        $approved = $this->makeProcessingOrder();
        $accepted = $this->makeProcessingOrder(DCPOrderState::ACCEPTED);
        $before = [$approved->getAttributes(), $accepted->getAttributes()];
        Config::set('dcp.enabled', false);
        $service = resolve(DCPOrderProcessingService::class);
        $service->sendApprovedOrders();
        $service->updateStatuses();

        $this->assertSame($before, [$approved->fresh()->getAttributes(), $accepted->fresh()->getAttributes()]);
        Http::assertNothingSent();
    }

    /**
     * @return void
     */
    public function testDisabledCommandDoesNotInvokeItsProcessor(): void
    {
        $calls = (object) ['send' => 0, 'status' => 0];

        $service = new readonly class (
            resolve(DCPOrderService::class),
            resolve(DCPLogService::class),
            $calls,
        ) extends DCPOrderProcessingService {
            /**
             * @param DCPOrderService $orders
             * @param DCPLogService $log
             * @param object $calls
             */
            public function __construct(DCPOrderService $orders, DCPLogService $log, private object $calls)
            {
                parent::__construct($orders, $log);
            }

            /**
             * @return void
             */
            public function sendApprovedOrders(): void
            {
                $this->calls->send++;
            }

            /**
             * @return void
             */
            public function updateStatuses(): void
            {
                $this->calls->status++;
            }
        };

        $original = resolve(DCPOrderProcessingService::class);
        $this->app->instance(DCPOrderProcessingService::class, $service);

        try {
            Config::set('dcp.disabled_commands.dcp:send-orders', true);
            Config::set('dcp.disabled_commands.dcp:update-status', false);
            $this->artisan('dcp:send-orders')->assertSuccessful();
            $this->artisan('dcp:update-status')->assertSuccessful();
            $this->assertSame(0, $calls->send);
            $this->assertSame(1, $calls->status);

            Config::set('dcp.disabled_commands.dcp:send-orders', false);
            Config::set('dcp.disabled_commands.dcp:update-status', true);
            $this->artisan('dcp:update-status')->assertSuccessful();
            $this->artisan('dcp:send-orders')->assertSuccessful();
            $this->assertSame(1, $calls->send);
            $this->assertSame(1, $calls->status);
        } finally {
            $this->app->instance(DCPOrderProcessingService::class, $original);
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testLifecycleLogsReflectCommittedTransitions(): void
    {
        $order = $this->makeProcessingOrder();
        $observed = [];

        Http::fake(['https://dcp.test/api/Orders*' => function (Request $request) use ($order, &$observed) {
            $observed[] = [DB::transactionLevel(), $order->fresh()->state];

            return Http::response(
                $this->orderResponse($order, $request->method() === 'POST' ? 100 : 300),
                $request->method() === 'POST' ? 201 : 200,
            );
        }]);

        $service = resolve(DCPOrderProcessingService::class);
        $service->sendApprovedOrders();
        $service->updateStatuses();
        $service->updateStatuses();
        $logs = $order->logs()->orderBy('id')->get();

        $this->assertSame(['created', 'sending', 'accepted', 'status_changed'], $logs->pluck('event')->all());
        $this->assertSame(['draft', 'sending', 'accepted', 'accepted'], $logs->pluck('data.state')->all());
        $this->assertSame([null, null, 100, 300], $logs->pluck('data.status_id')->all());
        $this->assertSame(100, $logs->last()->data['previous_status_id']);
        $this->assertSame([0, 0, 0], array_column($observed, 0));
        $this->assertSame(DCPOrderState::SENDING, $observed[0][1]);
        Http::assertSentCount(3);
    }

    /**
     * @param DCPOrder $order
     * @param int $status
     * @return array
     */
    private function orderResponse(DCPOrder $order, int $status = 100): array
    {
        return ['orderId' => $order->order_id, 'orderIdDCP' => 'remote-' . $order->id, 'statusId' => $status];
    }

    /**
     * @param DCPOrderState $state
     * @throws Throwable
     * @return DCPOrder
     */
    private function makeProcessingOrder(DCPOrderState $state = DCPOrderState::APPROVED): DCPOrder
    {
        if ($this->organization === null) {
            [$this->identity, $this->organization, $this->dcpConnection] = DB::transaction(function (): array {
                $identity = $this->makeIdentity();
                $organization = $this->makeTestOrganization($identity, ['is_sponsor' => true], withEmployee: false);
                $connection = $this->makeDCPConnection($organization);
                $organization->forceFill(['dcp_connection_id' => $connection->id])->save();

                return [$identity, $organization, $connection];
            });
        }

        $pass = VoucherPass::create([
            'organization_id' => $this->organization->id,
            'owner_identity_id' => $this->identity->id,
            'target_identity_id' => $this->identity->id,
            'code' => VoucherPass::generateCode(),
            'print_label' => 'Family budget',
            'state' => 'active',
            'print_requested' => true,
        ]);

        $data = new DCPOrderData(
            new DCPDeliveryAddress('Robin', 'Recipient', 'Mail Street 10', '1234AB', 'ARNHEM'),
            new GelrepasCardData(
                'Family budget',
                'T.O.',
                'Owner',
                CarbonImmutable::parse('1990-04-12'),
                '2020',
                $pass->code,
            ),
        );

        $orders = resolve(DCPOrderService::class);
        $order = $orders->createOrUpdateDraft($this->organization, $pass, $data);
        $orders->approveBulk($this->organization);
        $order->refresh();

        if ($state !== DCPOrderState::APPROVED) {
            $order->update([
                'state' => $state,
                'order_id_dcp' => $state === DCPOrderState::ACCEPTED ? 'remote-' . $order->id : null,
                'status_id' => $state === DCPOrderState::ACCEPTED ? 100 : null,
            ]);
        }

        return $order;
    }

    /**
     * @throws Throwable
     * @return void
     */
    private function cleanupProcessingFixture(): void
    {
        if ($this->organization === null) {
            return;
        }

        $organizationId = $this->organization->id;
        $identityId = $this->identity->id;
        $identityAddress = $this->identity->address;
        $connectionId = $this->dcpConnection->id;
        $implementationId = $this->dcpConnection->implementation_id;
        $orders = DCPOrder::where('organization_id', $organizationId)->pluck('id')->all();
        $bulks = DCPOrderBulk::where('organization_id', $organizationId)->pluck('id')->all();

        DB::transaction(function () use (
            $organizationId,
            $identityId,
            $connectionId,
            $implementationId,
            $orders,
            $bulks,
        ) {
            EventLog::where('loggable_type', 'dcp_order')->whereIn('loggable_id', $orders)->delete();
            EventLog::where('loggable_type', 'dcp_order_bulk')->whereIn('loggable_id', $bulks)->delete();
            DCPOrder::whereKey($orders)->delete();
            DCPOrderBulk::whereKey($bulks)->delete();
            VoucherPass::where('organization_id', $organizationId)->delete();
            $profiles = Profile::where('organization_id', $organizationId)->pluck('id');
            ProfileRecord::whereIn('profile_id', $profiles)->delete();
            Profile::whereKey($profiles)->delete();
            $this->organization->forceFill(['dcp_connection_id' => null])->save();
            DCPConnection::whereKey($connectionId)->delete();
            DB::table('implementations')->where('id', $implementationId)->delete();
            DB::table('organizations')->where('id', $organizationId)->delete();
            DB::table('identities')->where('id', $identityId)->delete();
        });

        $this->assertDatabaseMissing('organizations', ['id' => $organizationId]);
        $this->assertDatabaseMissing('identities', ['id' => $identityId]);
        $this->assertDatabaseMissing('dcp_connections', ['id' => $connectionId]);
        $this->assertDatabaseMissing('implementations', ['id' => $implementationId]);
        $this->assertDatabaseMissing('dcp_orders', ['organization_id' => $organizationId]);
        $this->assertDatabaseMissing('dcp_order_bulks', ['organization_id' => $organizationId]);
        $this->assertDatabaseMissing('voucher_passes', ['organization_id' => $organizationId]);
        $this->assertDatabaseMissing('profiles', ['organization_id' => $organizationId]);
        $this->assertDatabaseMissing('record_categories', ['identity_address' => $identityAddress]);
        $this->assertDatabaseMissing('identity_proxies', ['identity_address' => $identityAddress]);
        $this->assertDatabaseMissing('identity_emails', ['identity_address' => $identityAddress]);
        $this->assertSame(0, DB::transactionLevel());
    }
}
