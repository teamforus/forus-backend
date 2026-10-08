<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderRequesterLifecycleTest extends TestCase
{
    use DatabaseTransactions;
    use MakesProductReservations;
    use MakesTestFunds;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDisableAndReactivateAffectsAllVouchersButDoesNotRestoreRevokedSessions(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $this->makeIdentityProviderScimUserPayload());
        $identity = $membership->identity;
        $proxy = $this->makeIdentityProviderProxy($membership);
        $fund = $this->makeTestFund($connection->organization);
        $active = $this->makeTestVoucher($fund, $identity, amount: 100);
        $pending = $this->makeTestVoucher($fund, $identity, ['state' => Voucher::STATE_PENDING], amount: 100);
        $manual = $this->makeTestVoucher($fund, $identity, amount: 100);
        $manual->deactivate(note: 'Disabled before provisioning update');

        $otherFund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $crossSponsor = $this->makeTestVoucher($otherFund, $identity, amount: 100);
        $product = $this->findProductForReservation($active);
        $child = $active->buyProductVoucher($product);
        $reservation = $active->reserveProduct($product, extraData: ['first_name' => 'Jane', 'last_name' => 'Doe']);

        $unrelated = $this->makeIdentityProviderRequester($connection);
        $unrelatedProxy = $this->makeIdentityProviderProxy($unrelated);
        $unrelatedVoucher = $this->makeTestVoucher($fund, $unrelated->identity, amount: 100);

        $this->getJson('/api/v1/identity', $this->makeApiHeaders($proxy))->assertOk();

        $patch = [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'Replace', 'path' => 'active', 'value' => 'False']],
        ];

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)
            ->assertOk()->assertJsonPath('active', false);

        foreach ([$active, $pending, $child, $crossSponsor] as $voucher) {
            $this->assertSame(Voucher::STATE_DEACTIVATED, $voucher->refresh()->state);
        }

        $event = $active->logs()->where('event', Voucher::EVENT_DEACTIVATED)->firstOrFail();
        $this->assertSame('entra', $event->data['source']);
        $this->assertNull($event->identity_address);
        $this->assertArrayNotHasKey('employee_id', $event->data);

        $this->assertSame(ProductReservation::STATE_CANCELED_BY_CLIENT, $reservation->refresh()->state);
        $this->assertFalse($proxy->refresh()->isActive());
        $this->getJson('/api/v1/identity', $this->makeApiHeaders($proxy))->assertUnauthorized();
        $this->assertSame(Voucher::STATE_ACTIVE, $unrelatedVoucher->refresh()->state);
        $this->assertIdentityProviderProxy($unrelatedProxy, $unrelated);

        $disabledEvents = $connection->logs()->count();
        $patch['Operations'][0]['value'] = false;
        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();
        $this->assertSame($disabledEvents, $connection->logs()->count());

        $patch['Operations'][0]['value'] = 'True';

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)
            ->assertOk()->assertJsonPath('active', true);

        foreach ([$active, $pending, $child, $crossSponsor, $manual] as $voucher) {
            $this->assertSame(Voucher::STATE_ACTIVE, $voucher->refresh()->state);
        }

        $event = $active->logs()->where('event', Voucher::EVENT_ACTIVATED)->firstOrFail();
        $this->assertSame('entra', $event->data['source']);
        $this->assertNull($event->identity_address);

        $this->assertFalse($proxy->refresh()->isActive());
        $this->getJson('/api/v1/identity', $this->makeApiHeaders($proxy))->assertUnauthorized();
        $this->assertSame(ProductReservation::STATE_CANCELED_BY_CLIENT, $reservation->refresh()->state);

        $activeEvents = $connection->logs()->count();
        $voucherEvents = $active->logs()->count();
        $patch['Operations'][0]['value'] = true;
        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();
        $this->assertSame($activeEvents, $connection->logs()->count());
        $this->assertSame($voucherEvents, $active->logs()->count());
    }

    /**
     * @return void
     */
    public function testDeleteRetainsHistoryAndReprovisionRecoversTheSameRequester(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload();
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);
        $identity = $membership->identity;
        $profile = $identity->profiles()->where('organization_id', $connection->organization_id)->firstOrFail();
        $records = $profile->profile_records()->get()->toArray();
        $proxy = $this->makeIdentityProviderProxy($membership);
        $voucher = $this->makeTestVoucher($this->makeTestFund($connection->organization), $identity, amount: 100);

        $this->apiIdentityProviderScimUsersRequest('DELETE', $connection, $token, uid: $membership->uid)->assertNoContent();

        $this->assertTrue($membership->refresh()->isProvisioningDeleted());
        $this->assertSame($identity->id, $membership->identity_id);
        $this->assertSame($records, $profile->refresh()->profile_records()->get()->toArray());
        $this->assertSame(Voucher::STATE_DEACTIVATED, $voucher->refresh()->state);
        $this->assertFalse($proxy->refresh()->isActive());
        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $membership->uid)->assertNotFound();
        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertOk()->assertJsonPath('Resources', []);

        $events = $connection->logs()->count();
        $voucherEvents = $voucher->logs()->count();

        $this->apiIdentityProviderScimUsersRequest('DELETE', $connection, $token, uid: $membership->uid)->assertNoContent();
        $this->assertSame($events, $connection->logs()->count());
        $this->assertSame($voucherEvents, $voucher->logs()->count());

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $connection->organization,
            $connection,
            'disconnect',
            $connection->organization->identity,
        )->assertConflict();

        $this->assertSame(IdentityProviderConnection::STATUS_ENABLED, $connection->refresh()->status);
        $reprovisioned = $this->provisionIdentityProviderRequester($connection, $token, $payload);

        $this->assertSame($membership->id, $reprovisioned->id);
        $this->assertSame($identity->id, $reprovisioned->identity_id);
        $this->assertTrue($reprovisioned->isProvisioningActive());
        $this->assertSame($records, $profile->profile_records()->get()->toArray());
        $this->assertSame(Voucher::STATE_ACTIVE, $voucher->refresh()->state);
        $this->assertFalse($proxy->refresh()->isActive());
        $this->assertTrue($connection->logs()->where('event', IdentityProviderConnection::EVENT_SCIM_USER_REPROVISIONED)->exists());
    }
}
