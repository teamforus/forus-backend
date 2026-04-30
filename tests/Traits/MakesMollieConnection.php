<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\MollieService\Models\MollieConnection;

trait MakesMollieConnection
{
    /**
     * @param Organization $provider
     * @param bool $existingMollieAccount
     * @return MollieConnection
     */
    protected function createPendingMollieConnection(
        Organization $provider,
        bool $existingMollieAccount = true,
    ): MollieConnection {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        if ($existingMollieAccount) {
            $response = $this->postJson("/api/v1/platform/organizations/$provider->id/mollie-connection/connect", [], $apiHeaders);
        } else {
            $response = $this->postJson("/api/v1/platform/organizations/$provider->id/mollie-connection", [
                'name' => $this->faker->name(),
                'country_code' => $this->faker->countryCode(),
                'profile_name' => $this->faker->name(),
                'phone' => $this->faker->e164PhoneNumber(),
                'website' => $this->faker->url(),
                'email' => $this->faker->email(),
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'street' => $this->faker->streetName(),
                'city' => $this->faker->city(),
                'postcode' => $this->faker->postcode(),
            ], $apiHeaders);
        }

        $response->assertSuccessful();
        $response->assertJsonStructure(['url']);

        $connection = $this->findMollieConnectionById($provider, $response->json('id'));

        static::assertNotNull($connection);
        $this->assertEquals(MollieConnection::STATE_PENDING, $connection->connection_state);

        return $connection;
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param bool $allowExtraPayments
     * @return void
     */
    protected function enableFundProviderExtraPayments(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        bool $allowExtraPayments = true
    ): void {
        $this->assertNotNull($organization->identity);
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fund->id/providers/$fundProvider->id",
            [ 'allow_extra_payments' => $allowExtraPayments ],
            $apiHeaders,
        );

        $response->assertSuccessful();
        $response->assertJsonFragment(['allow_extra_payments' => $allowExtraPayments]);
        $fundProvider->refresh();
        $this->assertEquals($allowExtraPayments, $fundProvider->allow_extra_payments);
    }

    /**
     * @param MollieConnection $mollieConnection
     * @return void
     */
    protected function activateMollieConnection(MollieConnection $mollieConnection): void
    {
        $code = token_generator()->generate(64);
        $response = $this->getJson("/mollie/callback?state=$mollieConnection->state_code&code=$code");

        $expectUrl = Implementation::general()->urlProviderDashboard(
            "/organizations/$mollieConnection->organization_id/payment-methods"
        );

        $response->assertRedirect($expectUrl);
        $mollieConnection->refresh();
    }

    /**
     * @param Organization $provider
     * @param MollieConnection $mollieConnection
     * @return void
     */
    protected function assertConnectionActiveAndOnboarded(
        Organization $provider,
        MollieConnection $mollieConnection
    ): void {
        $this->assertEquals(MollieConnection::STATE_ACTIVE, $mollieConnection->connection_state);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson("/api/v1/platform/organizations/$provider->id/mollie-connection/fetch", $apiHeaders);

        $response->assertSuccessful();
        $provider->refresh();
        $mollieConnection->refresh();

        $this->assertEquals(MollieConnection::ONBOARDING_STATE_COMPLETED, $mollieConnection->onboarding_state);
        $this->assertTrue($provider->canReceiveExtraPayments());
    }

    /**
     * @param Organization $provider
     * @param int $id
     * @return MollieConnection|null
     */
    protected function findMollieConnectionById(Organization $provider, int $id): MollieConnection | null
    {
        /** @var MollieConnection $connection */
        $connection = $provider->mollie_connections()->where('id', $id)->first();
        static::assertNotNull($connection);

        return $connection;
    }
}
