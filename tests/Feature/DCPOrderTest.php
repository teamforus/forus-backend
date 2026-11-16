<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Modules\DCP\Enums\DCPEventType;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Events\DCPLifecycleEvent;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Models\DCPOrderBulk;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestDCPOrders;
use Throwable;

class DCPOrderTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestDCPOrders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testBulkApprovalUsesCurrentReadyOrdersInsteadOfEarlierCount(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $first = $this->prepareDCPOrder($pass);
        $revokedPass = $this->makeAdditionalDCPPass($pass);
        $revoked = $this->prepareDCPOrder($revokedPass);

        $this
            ->apiGetDCPReadyCountRequest($organization, $organization->identity)
            ->assertOk()
            ->assertExactJson(['data' => ['count' => 2]]);

        $revokedPass->update(['print_requested' => false]);
        $late = $this->prepareDCPOrder($this->makeAdditionalDCPPass($pass, ['state' => 'deactivated']));
        ['pass' => $foreignPass] = $this->makeDCPFixture();
        $foreign = $this->prepareDCPOrder($foreignPass);

        $response = $this
            ->apiApproveDCPOrderBulkRequest($organization, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.dcp_orders_count', 2)
            ->assertJsonPath('data.counts.approved', 2);

        $bulk = DCPOrderBulk::findOrFail($response->json('data.id'));

        $this->assertEqualsCanonicalizing([$first->id, $late->id], $bulk->dcp_orders->modelKeys());
        $this->assertSame(DCPOrderState::DRAFT, $revoked->refresh()->state);
        $this->assertNull($revoked->dcp_order_bulk_id);
        $this->assertSame(DCPOrderState::DRAFT, $foreign->refresh()->state);
        $this->assertNull($foreign->dcp_order_bulk_id);
        $this->assertSame(['approved'], $bulk->logs()->pluck('event')->all());

        $this
            ->apiGetDCPReadyCountRequest($organization, $organization->identity)
            ->assertOk()
            ->assertExactJson(['data' => ['count' => 0]]);

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBulkApprovalPreservesSavedPrintingData(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'pass' => $pass, 'owner' => $owner, 'recipient' => $recipient] =
            $this->makeDCPFixture();

        $order = $this->prepareDCPOrder($pass);
        $rows = $order->order_rows;
        $reference = $order->order_id;
        $this->setDCPProfileValues($owner, ['initials' => 'Changed']);
        $this->setDCPProfileValues($recipient, ['street' => 'Changed street']);
        $this->travel(1)->minute();

        $response = $this->apiApproveDCPOrderBulkRequest($organization, $organization->identity)->assertSuccessful();
        $bulk = DCPOrderBulk::findOrFail($response->json('data.id'));

        $order->refresh();
        $this->assertSame($rows, $order->order_rows);
        $this->assertSame($reference, $order->order_id);
        $this->assertSame('Residential Road 10', $order->delivery_address1);
        $this->assertSame($bulk->id, $order->dcp_order_bulk_id);
        $this->assertSame(DCPOrderState::APPROVED, $order->state);
        $this->assertTrue($order->approved_at->equalTo($bulk->approved_at));
        $this->assertSame($organization->identity_address, $bulk->employee->identity_address);
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBulkApprovalDoesNotCreateEmptyBulkOrReassignApprovedOrders(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $order = $this->prepareDCPOrder($pass);
        $this->apiApproveDCPOrderBulkRequest($organization, $organization->identity)->assertSuccessful();
        $before = $order->refresh()->getAttributes();

        $this
            ->apiApproveDCPOrderBulkRequest($organization, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('orders');

        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame(1, DCPOrderBulk::where('organization_id', $organization->id)->count());
        $this->assertSame(1, $order->dcp_order_bulk->logs()->count());
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBulkApprovalRollsBackWhenOrderAssignmentFails(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $first = $this->prepareDCPOrder($pass);
        $second = $this->prepareDCPOrder($this->makeAdditionalDCPPass($pass));
        $before = [$first->getAttributes(), $second->getAttributes()];
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        $bulkId = null;
        $assignedCount = 0;
        $connection->setEventDispatcher(clone $dispatcher);
        $this->withoutExceptionHandling();

        try {
            $connection->getEventDispatcher()->listen(QueryExecuted::class, function (QueryExecuted $event) use (
                $organization,
                &$bulkId,
                &$assignedCount,
            ) {
                if (!str_starts_with($event->sql, 'update `dcp_orders`') ||
                    !str_contains($event->sql, '`dcp_order_bulk_id`')) {
                    return;
                }

                $bulkId = DCPOrderBulk::where('organization_id', $organization->id)->value('id');

                if ($bulkId === null) {
                    return;
                }

                $assignedCount = DCPOrder::where('dcp_order_bulk_id', $bulkId)->count();

                throw new RuntimeException('Injected bulk assignment failure.');
            });

            try {
                $this->apiApproveDCPOrderBulkRequest($organization, $organization->identity);
                $this->fail('Expected the bulk assignment to fail.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Injected bulk assignment failure.', $exception->getMessage());
            }
        } finally {
            $connection->setEventDispatcher($dispatcher);
            $this->withExceptionHandling();
        }

        $this->assertSame(2, $assignedCount);
        $this->assertNotNull($bulkId);
        $this->assertDatabaseMissing('dcp_order_bulks', ['id' => $bulkId]);
        $this->assertSame($before, [$first->fresh()->getAttributes(), $second->fresh()->getAttributes()]);

        $this->assertDatabaseMissing('event_logs', [
            'loggable_type' => 'dcp_order_bulk',
            'loggable_id' => $bulkId,
            'event' => 'approved',
        ]);

        $this->assertSame(['created'], $first->logs()->pluck('event')->all());
        $this->assertSame(['created'], $second->logs()->pluck('event')->all());
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDcpReadEndpointsEnforceOrganizationAndManagerAccess(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $order = $this->prepareDCPOrder($pass);
        $this->apiApproveDCPOrderBulkRequest($organization, $organization->identity)->assertSuccessful();
        $bulk = $order->refresh()->dcp_order_bulk;
        $manager = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_IDENTITIES])->identity;
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_IDENTITIES])->identity;
        $this->assertDCPReadStatus($organization, $order, $bulk, $manager, 200);
        $this->assertDCPReadStatus($organization, $order, $bulk, $viewer, 403);
        $this->assertDCPReadStatus($organization, $order, $bulk, false, 401);

        ['organization' => $foreign, 'pass' => $foreignPass] = $this->makeDCPFixture();
        $foreignOrder = $this->prepareDCPOrder($foreignPass);
        $this->apiApproveDCPOrderBulkRequest($foreign, $foreign->identity)->assertSuccessful();
        $foreignBulk = $foreignOrder->refresh()->dcp_order_bulk;

        $this->apiGetDCPOrderRequest($organization, $foreignOrder, $manager)->assertForbidden();
        $this->apiGetDCPOrderLogsRequest($organization, $foreignOrder, $manager)->assertForbidden();
        $this->apiGetDCPOrderBulkRequest($organization, $foreignBulk, $manager)->assertForbidden();
        $this->apiGetDCPOrderBulkLogsRequest($organization, $foreignBulk, $manager)->assertForbidden();

        $order->update([
            'submission_response' => ['private' => 'submission-secret'],
            'status_response' => ['private' => 'status-secret'],
        ]);

        $response = $this
            ->apiGetDCPOrderRequest($organization, $order, $manager)
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.source.code', $pass->code)
            ->assertJsonMissingPath('data.submission_response')
            ->assertJsonMissingPath('data.status_response')
            ->assertJsonMissingPath('data.connection.username')
            ->assertJsonMissingPath('data.connection.password');

        foreach (['fixture-user', 'fixture-password', 'submission-secret', 'status-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDcpHistoryRemainsReadableWhenSendingIsDisabled(): void
    {
        ['organization' => $organization, 'connection' => $connection, 'pass' => $pass] = $this->makeDCPFixture();
        $order = $this->prepareDCPOrder($pass);
        $this->apiApproveDCPOrderBulkRequest($organization, $organization->identity)->assertSuccessful();
        $bulk = $order->refresh()->dcp_order_bulk;
        $draft = $this->prepareDCPOrder($this->makeAdditionalDCPPass($pass));

        foreach (['global', 'connection'] as $gate) {
            Config::set('dcp.enabled', $gate !== 'global');
            $connection->update(['enabled' => $gate !== 'connection']);
            $this->assertDCPReadStatus($organization, $order, $bulk, $organization->identity, 200);

            $this
                ->apiGetDCPOrderLogsRequest($organization, $order, $organization->identity)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.event', 'created');

            $this
                ->apiGetDCPOrderBulkLogsRequest($organization, $bulk, $organization->identity)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.event', 'approved');

            $this->apiApproveDCPOrderBulkRequest($organization, $organization->identity)->assertForbidden();
            $this->assertSame(DCPOrderState::DRAFT, $draft->refresh()->state);
            $this->assertNull($draft->dcp_order_bulk_id);
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreparationAndApprovalEnforceTheirDistinctGates(): void
    {
        ['organization' => $organization, 'connection' => $connection, 'pass' => $pass] = $this->makeDCPFixture();
        $manager = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_IDENTITIES])->identity;
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_IDENTITIES])->identity;
        $connection->update(['enabled' => false]);
        Config::set('dcp.enabled', false);

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => [$pass->id]], $manager)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        $this->apiApproveDCPOrderBulkRequest($organization, $manager)->assertForbidden();
        Config::set('dcp.enabled', true);
        $connection->update(['enabled' => true]);

        foreach (['allow_voucher_passes', 'allow_profiles', 'bsn_enabled', 'dcp_connection_id'] as $flag) {
            $previous = $organization->getAttribute($flag);
            $organization->forceFill([$flag => $flag === 'dcp_connection_id' ? null : false])->save();

            try {
                $this->apiGetVoucherPassDCPIdsRequest($organization, $manager)->assertForbidden();

                foreach ([false, true] as $preview) {
                    $this
                        ->apiPrepareVoucherPassDCPRequest(
                            $organization,
                            ['pass_ids' => [$pass->id]],
                            $manager,
                            $preview,
                        )
                        ->assertForbidden();
                }
            } finally {
                $organization->forceFill([$flag => $previous])->save();
            }
        }

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => [$pass->id]], $viewer)
            ->assertForbidden();

        $this->apiApproveDCPOrderBulkRequest($organization, $viewer)->assertForbidden();
        $organization
            ->forceFill([
                'allow_voucher_passes' => false,
                'allow_profiles' => false,
                'bsn_enabled' => false,
            ])
            ->save();

        $this
            ->apiApproveDCPOrderBulkRequest($organization, $manager)
            ->assertSuccessful()
            ->assertJsonPath('data.dcp_orders_count', 1);

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrderSearchAndCountsStayScopedToOrganization(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $draft = $this->prepareDCPOrder($pass);
        $approved = $this->prepareDCPOrder($this->makeAdditionalDCPPass($pass));
        $unknown = $this->prepareDCPOrder($this->makeAdditionalDCPPass($pass));
        $approved->update(['state' => DCPOrderState::APPROVED]);
        $unknown->update(['state' => DCPOrderState::UNKNOWN]);
        ['organization' => $foreign, 'pass' => $foreignPass] = $this->makeDCPFixture();
        $foreignOrder = $this->prepareDCPOrder($foreignPass);

        $this
            ->apiGetDCPOrdersRequest($organization, $organization->identity, [
                'with_counts' => 1,
                'state_group' => 'draft',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $draft->id)
            ->assertJsonPath('meta.counts', [
                'all' => 3,
                'draft' => 1,
                'in_progress' => 1,
                'needs_attention' => 1,
                'completed' => 0,
            ]);

        foreach ([$pass->external_id, $pass->code] as $q) {
            $this
                ->apiGetDCPOrdersRequest($organization, $organization->identity, ['q' => $q, 'with_counts' => 1])
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $draft->id)
                ->assertJsonPath('meta.counts.all', 1);
        }

        $this->apiApproveDCPOrderBulkRequest($foreign, $foreign->identity)->assertSuccessful();

        $this
            ->apiGetDCPOrdersRequest($organization, $organization->identity, [
                'dcp_order_bulk_id' => $foreignOrder->refresh()->dcp_order_bulk_id,
                'with_counts' => 1,
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.counts.all', 0);

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDcpLifecycleLogsDoNotLeakIntoOtherEntityHistory(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $template = $this->prepareDCPOrder($pass);
        $lastFundId = Fund::query()->max('id');
        $lastOrderId = DCPOrder::query()->max('id');
        $sharedId = max($lastFundId, $lastOrderId) + 1;
        $fund = Fund::unguarded(fn () => $this->makeTestFund($organization, ['id' => $sharedId]));
        $source = $this->makeAdditionalDCPPass($pass);
        $order = $template->replicate();

        $order
            ->forceFill([
                'id' => $sharedId,
                'orderable_id' => $source->id,
                'order_id' => Str::uuid()->toString(),
            ])
            ->save();

        DB::transaction(fn () => DCPLifecycleEvent::dispatch($order, DCPEventType::CREATED));
        $log = $order->logs()->sole();
        $fundLog = $fund->log(Fund::EVENT_VOUCHERS_EXPORTED, [], ['fund_export_voucher_ids' => [$sharedId]]);

        $this
            ->apiGetDCPOrderLogsRequest($organization, $order, $organization->identity)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $log->id);

        $this
            ->apiGetOrganizationLogsRequest($organization, $organization->identity, [
                'loggable' => ['dcp_order'],
                'loggable_id' => $sharedId,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $log->id);

        $fundResponse = $this
            ->apiGetOrganizationLogsRequest($organization, $organization->identity, [
                'loggable' => ['fund'],
                'loggable_id' => $sharedId,
            ])
            ->assertOk();

        $this->assertContains($fundLog->id, array_column($fundResponse->json('data'), 'id'));
        $this->assertNotContains($log->id, array_column($fundResponse->json('data'), 'id'));

        ['organization' => $foreign] = $this->makeDCPFixture();

        $this
            ->apiGetOrganizationLogsRequest($foreign, $foreign->identity, [
                'loggable' => ['dcp_order'],
                'loggable_id' => $sharedId,
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Http::assertNothingSent();
    }

    /**
     * @param Organization $organization
     * @param DCPOrder $order
     * @param DCPOrderBulk $bulk
     * @param Identity|bool $identity
     * @param int $status
     * @return void
     */
    private function assertDCPReadStatus(
        Organization $organization,
        DCPOrder $order,
        DCPOrderBulk $bulk,
        Identity|bool $identity,
        int $status,
    ): void {
        $this->apiGetDCPOrdersRequest($organization, $identity)->assertStatus($status);
        $this->apiGetDCPOrderRequest($organization, $order, $identity)->assertStatus($status);
        $this->apiGetDCPOrderLogsRequest($organization, $order, $identity)->assertStatus($status);
        $this->apiGetDCPOrderBulksRequest($organization, $identity)->assertStatus($status);
        $this->apiGetDCPOrderBulkRequest($organization, $bulk, $identity)->assertStatus($status);
        $this->apiGetDCPOrderBulkLogsRequest($organization, $bulk, $identity)->assertStatus($status);
    }
}
