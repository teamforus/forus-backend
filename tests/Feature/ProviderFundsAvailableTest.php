<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProviderFundsAvailableTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderFundsAvailable(): void
    {
        // make organization and fund which has allow_provider_sign_up = true
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $provider = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail('provider_')));
        $fundTag = $this->faker->name;

        $fund = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        $tag = $fund->tags()->firstOrCreate([
            'key' => Str::slug($fundTag),
            'scope' => 'provider',
        ]);

        $tag->translateOrNew(app()->getLocale())->fill([
            'name' => $fundTag,
        ])->save();

        $this->makeTestImplementation($organization);

        // assert fund exists in a list of funds available for provider, and meta contains organization, implementation and tag
        $this->makeProviderAvailableFundsRequest($provider)
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($fund->id, $ids))
            ->assertJsonPath('meta.organizations.*.id', fn ($ids) => in_array($organization->id, $ids))
            ->assertJsonPath('meta.implementations.*.id', fn ($ids) => in_array($fund->getImplementation()->id, $ids))
            ->assertJsonPath('meta.tags.*.id', fn ($ids) => in_array($fund->tags()->first()->id, $ids));

        // assert fund doesn't exists in a list of funds available for provider as allow_provider_sign_up = false
        // and meta doesn't contains organization, implementation and tag
        $fund->fund_config->update(['allow_provider_sign_up' => false]);

        $this->makeProviderAvailableFundsRequest($provider)
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($ids) => ! in_array($fund->id, $ids))
            ->assertJsonPath('meta.organizations.*.id', fn ($ids) => !in_array($organization->id, $ids))
            ->assertJsonPath('meta.implementations.*.id', fn ($ids) => !in_array($fund->getImplementation()->id, $ids))
            ->assertJsonPath('meta.tags.*.id', fn ($ids) => ! in_array($fund->tags()->first()->id, $ids));

        // create new fund (fund2) for current sponsor and assert fund doesn't exists in a list but fund2 exists
        // and meta contains organization and implementation (as one of sponsor funds is available)
        // but tag (fund tag) doesn't exist
        $fund2 = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        $this->makeProviderAvailableFundsRequest($provider)
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($ids) => !in_array($fund->id, $ids))
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($fund2->id, $ids))
            ->assertJsonPath('meta.organizations.*.id', fn ($ids) => in_array($organization->id, $ids))
            ->assertJsonPath('meta.implementations.*.id', fn ($ids) => in_array($fund->getImplementation()->id, $ids))
            ->assertJsonPath('meta.tags.*.id', fn ($ids) => !in_array($fund->tags()->first()->id, $ids));

        // assert apply for fund with valid and invalid fund_id
        $this->makeFundApplyRequest($provider, fund: null)->assertJsonValidationErrors('fund_id');
        $this->makeFundApplyRequest($provider, fund: $fund)->assertJsonValidationErrors('fund_id');
        $this->makeFundApplyRequest($provider, fund: $fund2)->assertSuccessful();
    }

    /**
     * Makes a GET request to retrieve available funds for a provider.
     *
     * @param Organization $provider The provider model.
     * @return TestResponse The response from the API call.
     */
    protected function makeProviderAvailableFundsRequest(Organization $provider): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds-available?per_page=100",
            $this->makeApiHeaders($provider->identity),
        );
    }

    /**
     * Makes a POST request to apply funds for a provider.
     *
     * @param Organization $provider The provider model.
     * @return TestResponse The response from the API call.
     */
    protected function makeFundApplyRequest(Organization $provider, ?Fund $fund): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds",
            ['fund_id' => $fund?->id],
            $this->makeApiHeaders($provider->identity),
        );
    }
}
