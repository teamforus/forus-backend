<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Searches\OrganizationSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class OrganizationSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new OrganizationSearch([], Organization::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $identity = $this->makeIdentity();

        $namePart1 = 'match';
        $namePart2 = 'other';

        $descriptionPart1 = 'unique';
        $descriptionPart2 = 'secondary';

        $emailPart1 = 'something_un';
        $emailPart2 = 'any_un';

        $phonePart1 = '22233';
        $phonePart2 = '55566';

        $websitePart1 = 'forus';
        $websitePart2 = 'dashboard';

        $organization1 = $this->makeTestOrganization($this->makeIdentity(), [
            'name' => "$namePart1 name",
            'description_text' => "$descriptionPart1 description text",
            'email' => $this->makeUniqueEmail($emailPart1),
            'email_public' => true,
            'phone' => "{$phonePart1}444",
            'phone_public' => true,
            'website' => "https://$websitePart1.example.com",
            'website_public' => true,
        ]);

        $organization1->addEmployee($identity);

        $organization2 = $this->makeTestOrganization($this->makeIdentity(), [
            'name' => "$namePart2 name",
            'description_text' => "$descriptionPart2 description text",
            'email' => $this->makeUniqueEmail($emailPart2),
            'email_public' => true,
            'phone' => "{$phonePart2}444",
            'phone_public' => true,
            'website' => "https://$websitePart2.example.com",
            'website_public' => true,
        ]);

        $organization2->addEmployee($identity);

        $this->assertSearchIds(['q' => $namePart1], [$organization1->id], $identity);
        $this->assertSearchIds(['q' => $namePart2], [$organization2->id], $identity);

        $this->assertSearchIds(['q' => $descriptionPart1], [$organization1->id], $identity);
        $this->assertSearchIds(['q' => $descriptionPart2], [$organization2->id], $identity);

        $this->assertSearchIds(['q' => $emailPart1], [$organization1->id], $identity);
        $this->assertSearchIds(['q' => $emailPart2], [$organization2->id], $identity);

        $this->assertSearchIds(['q' => $phonePart1], [$organization1->id], $identity);
        $this->assertSearchIds(['q' => $phonePart2], [$organization2->id], $identity);

        $this->assertSearchIds(['q' => $websitePart1], [$organization1->id], $identity);
        $this->assertSearchIds(['q' => $websitePart2], [$organization2->id], $identity);
    }

    /**
     * @return void
     */
    public function testFiltersByFlags(): void
    {
        $identity = $this->makeIdentity();

        $sponsor = $this->makeTestOrganization($this->makeIdentity(), ['is_sponsor' => true]);
        $provider = $this->makeTestOrganization($this->makeIdentity(), ['is_provider' => true]);
        $validator = $this->makeTestOrganization($this->makeIdentity(), ['is_validator' => true]);

        $sponsor->addEmployee($identity);
        $provider->addEmployee($identity);
        $validator->addEmployee($identity);

        $this->assertSearchIds(['is_sponsor' => true], [$sponsor->id], $identity);
        $this->assertSearchIds(['is_provider' => true], [$provider->id], $identity);
        $this->assertSearchIds(['is_validator' => true], [$validator->id], $identity);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByTypeSponsor(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);

        $this->assertSearchIds([
            'type' => 'sponsor',
            'implementation_id' => $fund->getImplementation()->id,
        ], [$sponsor->id], $identity);

        $fund->update(['state' => Fund::STATE_CLOSED]);

        $this->assertSearchIds([
            'type' => 'sponsor',
            'implementation_id' => $fund->getImplementation()->id,
        ], [], $identity);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByIsEmployee(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $this->assertSearchIds([], [], $identity);

        $sponsor->addEmployee($identity);
        $this->assertSearchIds([], [$sponsor->id], $identity);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByHasReservations(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);

        $this->assertSearchIds([
            'has_reservations' => true,
            'type' => 'provider',
            'implementation_id' => $fund->getImplementation()->id,
        ], [], $identity);

        $this->prepareReservation($fund, $provider, $identity);

        $this->assertSearchIds([
            'has_reservations' => true,
            'type' => 'provider',
            'implementation_id' => $fund->getImplementation()->id,
        ], [$provider->id], $identity);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $identity = $this->makeIdentity();
        $olderOrganization = $this->makeTestOrganization($this->makeIdentity());

        Carbon::setTestNow(now()->addDays(5));
        $newerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $olderOrganization->addEmployee($identity);
        $newerOrganization->addEmployee($identity);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderOrganization->id, $newerOrganization->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerOrganization->id, $olderOrganization->id], $identity);
    }

    /**
     * @return void
     */
    public function testOrdersByFlags(): void
    {
        $identity = $this->makeIdentity();

        $sponsor = $this->makeTestOrganization($this->makeIdentity(), ['is_sponsor' => true]);
        $provider = $this->makeTestOrganization($this->makeIdentity(), ['is_provider' => true]);
        $validator = $this->makeTestOrganization($this->makeIdentity(), ['is_validator' => true]);

        $sponsor->addEmployee($identity);
        $provider->addEmployee($identity);
        $validator->addEmployee($identity);

        $this->assertSearchIds(['is_sponsor' => true], [$sponsor->id], $identity);
        $this->assertSearchIds(['is_provider' => true], [$provider->id], $identity);
        $this->assertSearchIds(['is_validator' => true], [$validator->id], $identity);

        // is_sponsor
        $this->assertSearchOrder([
            'order_by' => 'is_sponsor',
            'order_dir' => 'asc',
        ], [$provider->id, $validator->id, $sponsor->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'is_sponsor',
            'order_dir' => 'desc',
        ], [$sponsor->id, $provider->id, $validator->id], $identity);

        // is_provider
        $this->assertSearchOrder([
            'order_by' => 'is_provider',
            'order_dir' => 'asc',
        ], [$sponsor->id, $validator->id, $provider->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'is_provider',
            'order_dir' => 'desc',
        ], [$provider->id, $sponsor->id, $validator->id], $identity);

        // is_validator
        $this->assertSearchOrder([
            'order_by' => 'is_validator',
            'order_dir' => 'asc',
        ], [$sponsor->id, $provider->id, $validator->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'is_validator',
            'order_dir' => 'desc',
        ], [$validator->id, $sponsor->id, $provider->id], $identity);
    }

    /**
     * @return void
     */
    public function testOrdersByNameAndContact(): void
    {
        $identity = $this->makeIdentity();

        $emailA = 'A@test.com';
        $emailB = 'B@test.com';

        $phoneA = '122333444';
        $phoneB = '255666444';

        $websiteA = 'https://a-forus.io';
        $websiteB = 'https://b-forus.com';

        $organizationA = $this->makeTestOrganization($this->makeIdentity(), [
            'name' => 'A organization name',
            'email' => $emailA,
            'phone' => $phoneA,
            'website' => $websiteA,
        ]);

        $organizationA->addEmployee($identity);

        $organizationB = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => 'B organization name',
            'email' => $emailB,
            'phone' => $phoneB,
            'website' => $websiteB,
        ]);

        $organizationB->addEmployee($identity);

        // name
        $this->assertSearchOrder([
            'order_by' => 'name',
            'order_dir' => 'asc',
        ], [$organizationA->id, $organizationB->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'name',
            'order_dir' => 'desc',
        ], [$organizationB->id, $organizationA->id], $identity);

        // phone
        $this->assertSearchOrder([
            'order_by' => 'phone',
            'order_dir' => 'asc',
        ], [$organizationA->id, $organizationB->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'phone',
            'order_dir' => 'desc',
        ], [$organizationB->id, $organizationA->id], $identity);

        // email
        $this->assertSearchOrder([
            'order_by' => 'email',
            'order_dir' => 'asc',
        ], [$organizationA->id, $organizationB->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'email',
            'order_dir' => 'desc',
        ], [$organizationB->id, $organizationA->id], $identity);

        // website
        $this->assertSearchOrder([
            'order_by' => 'website',
            'order_dir' => 'asc',
        ], [$organizationA->id, $organizationB->id], $identity);

        $this->assertSearchOrder([
            'order_by' => 'website',
            'order_dir' => 'desc',
        ], [$organizationB->id, $organizationA->id], $identity);
    }

    /**
     * @param Fund $fund
     * @param Organization $provider
     * @param Identity $identity
     * @throws Throwable
     * @return ProductReservation
     */
    private function prepareReservation(Fund $fund, Organization $provider, Identity $identity): ProductReservation
    {
        $voucher = $this->makeTestVoucher($fund, $identity);
        $product = $this->createProductForReservation($provider, [$fund]);

        $reservation = $voucher->reserveProduct($product);

        if ($reservation->product->autoAcceptsReservations()) {
            $reservation->acceptProvider();
        }

        return $reservation;
    }

    /**
     * @param array $filters
     * @return OrganizationSearch
     */
    private function makeSearch(array $filters): OrganizationSearch
    {
        return new OrganizationSearch($filters, Organization::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Identity|null $identity
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Identity $identity = null): void
    {
        if ($identity) {
            $filters = [
                ...$filters,
                'auth_address' => $identity->address,
            ];
        }

        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Identity|null $identity
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, Identity $identity = null): void
    {
        if ($identity) {
            $filters = [
                ...$filters,
                'auth_address' => $identity->address,
            ];
        }

        $search = $this->makeSearch($filters);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
