<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
        $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        $fundTag = $this->faker->name;
        $tag = $fund->tags()->firstOrCreate([
            'key' => Str::slug($fundTag),
            'scope' => 'provider',
        ]);

        $tag->translateOrNew(app()->getLocale())->fill([
            'name' => $fundTag,
        ])->save();

        // make provider and proxy for requests
        $provider = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail('provider_')));
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        // assert fund exists in a list of funds available for provider, and meta contains organization, implementation and tag
        $response = $this->getJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds-available?per_page=100",
            $headers
        );

        $response->assertSuccessful();
        $this->assertTrue(in_array($fund->id, Arr::pluck($response->json('data'), 'id')));
        $this->assertTrue(in_array($organization->id, Arr::pluck($response->json('meta.organizations'), 'id')));
        $this->assertTrue(in_array($fund->getImplementation()->id, Arr::pluck($response->json('meta.implementations'), 'id')));
        $this->assertTrue(in_array($fund->tags()->first()->id, Arr::pluck($response->json('meta.tags'), 'id')));

        // assert fund doesn't exists in a list of funds available for provider as allow_provider_sign_up = false
        // and meta doesn't contains organization, implementation and tag
        $fund->fund_config->update(['allow_provider_sign_up' => false]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds-available?per_page=100",
            $headers
        );

        $response->assertSuccessful();
        $this->assertFalse(in_array($fund->id, Arr::pluck($response->json('data'), 'id')));
        $this->assertFalse(in_array($organization->id, Arr::pluck($response->json('meta.organizations'), 'id')));
        $this->assertFalse(in_array($fund->getImplementation()->id, Arr::pluck($response->json('meta.implementations'), 'id')));
        $this->assertFalse(in_array($fund->tags()->first()->id, Arr::pluck($response->json('meta.tags'), 'id')));

        // create new fund (fund2) for current sponsor and assert fund doesn't exists in a list but fund2 exists
        // and meta contains organization and implementation (as one of sponsor funds is available)
        // but tag (fund tag) doesn't exist
        $fund2 = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds-available?per_page=100",
            $headers
        );

        $response->assertSuccessful();
        $this->assertFalse(in_array($fund->id, Arr::pluck($response->json('data'), 'id')));
        $this->assertTrue(in_array($fund2->id, Arr::pluck($response->json('data'), 'id')));
        $this->assertTrue(in_array($organization->id, Arr::pluck($response->json('meta.organizations'), 'id')));
        $this->assertTrue(in_array($fund->getImplementation()->id, Arr::pluck($response->json('meta.implementations'), 'id')));
        $this->assertFalse(in_array($fund->tags()->first()->id, Arr::pluck($response->json('meta.tags'), 'id')));

        // assert apply for fund with valid and invalid fund_id
        $this->postJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds",
            [],
            $headers
        )->assertJsonValidationErrors(['fund_id']);

        $this->postJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds",
            ['fund_id' => $fund->id],
            $headers
        )->assertJsonValidationErrors(['fund_id']);

        $this->postJson(
            "/api/v1/platform/organizations/$provider->id/provider/funds",
            ['fund_id' => $fund2->id],
            $headers
        )->assertSuccessful();
    }
}
