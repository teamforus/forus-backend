<?php

namespace Tests\Feature;

use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;

class SponsorFundProviderProductAccessTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testPublicFundProductListAndDetailDenyAccessWithoutSponsorPermission(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fundProvider = $this->makeAcceptedProviderForPublicFund($sponsor);
        $product = $this->makeTestProduct($fundProvider->organization);
        $employee = $this->makeTestEmployeeWithPermissions($sponsor, []);

        foreach ([$this->makeIdentity(), $employee->identity] as $identity) {
            $this
                ->apiGetSponsorFundProviderProductsRequest($fundProvider, ['type' => 'provider'], $identity)
                ->assertForbidden();

            $this->apiGetSponsorFundProviderProductRequest($fundProvider, $product, $identity)->assertForbidden();
        }
    }

    /**
     * @return void
     */
    public function testPublicFundProductListAndDetailAllowEitherSponsorPermission(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fundProvider = $this->makeAcceptedProviderForPublicFund($sponsor);
        $product = $this->makeTestProduct($fundProvider->organization);

        foreach ([Permission::VIEW_FINANCES, Permission::MANAGE_PROVIDERS] as $permission) {
            $identity = $this->makeTestEmployeeWithPermissions($sponsor, [$permission])->identity;

            $this
                ->apiGetSponsorFundProviderProductsRequest($fundProvider, ['type' => 'provider'], $identity)
                ->assertOk()
                ->assertJsonPath('data.0.id', $product->id);

            $this
                ->apiGetSponsorFundProviderProductRequest($fundProvider, $product, $identity)
                ->assertOk()
                ->assertJsonPath('data.id', $product->id);
        }
    }

    /**
     * @return void
     */
    public function testProductDetailReturnsNotFoundForAnotherSponsorsProduct(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fundProvider = $this->makeAcceptedProviderForPublicFund($sponsor);
        $otherSponsor = $this->makeTestOrganization($this->makeIdentity());

        $product = $this->makeTestProduct($fundProvider->organization, attributes: [
            'sponsor_organization_id' => $otherSponsor->id,
        ]);

        $employee = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::MANAGE_PROVIDERS]);
        $this->apiGetSponsorFundProviderProductRequest($fundProvider, $product, $employee->identity)->assertNotFound();
    }

    /**
     * @param Organization $sponsor
     * @return FundProvider
     */
    private function makeAcceptedProviderForPublicFund(Organization $sponsor): FundProvider
    {
        $fund = $this->makeTestFund($sponsor, ['public' => true]);
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        return $this->makeTestFundProvider($provider, $fund);
    }
}
