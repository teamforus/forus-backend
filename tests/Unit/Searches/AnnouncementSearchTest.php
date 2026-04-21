<?php

namespace Tests\Unit\Searches;

use App\Models\Announcement;
use App\Models\BankConnection;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Searches\AnnouncementSearch;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class AnnouncementSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestBankConnections;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use WithFaker;

    protected Identity $identity;
    protected Organization $organization;
    protected Organization $otherOrganization;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->identity = $this->makeIdentity();

        $this->organization = $this->makeTestOrganization($this->identity);
        $this->makeTestFund($this->organization);

        $this->otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $this->makeTestFund($this->otherOrganization);
    }

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new AnnouncementSearch([], Announcement::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByClientTypeDashboards(): void
    {
        $dashboard = $this->makeAnnouncement(['scope' => 'dashboards']);
        $webshop = $this->makeAnnouncement(['scope' => Implementation::FRONTEND_WEBSHOP]);
        $sponsor = $this->makeAnnouncement(['scope' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);
        $provider = $this->makeAnnouncement(['scope' => Implementation::FRONTEND_PROVIDER_DASHBOARD]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($dashboard->id, $ids);
        $this->assertContains($sponsor->id, $ids);
        $this->assertNotContains($webshop->id, $ids);
        $this->assertNotContains($provider->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersByClientTypeWebshopAndImplementation(): void
    {
        $implementation = $this->organization->implementations()->first();
        $this->assertNotNull($implementation);

        $otherImplementation = $this->otherOrganization->implementations()->first();
        $this->assertNotNull($otherImplementation);

        $global = $this->makeAnnouncement([
            'scope' => Implementation::FRONTEND_WEBSHOP,
        ]);

        $current = $this->makeAnnouncement([
            'scope' => Implementation::FRONTEND_WEBSHOP,
            'implementation_id' => $implementation->id,
        ]);

        $other = $this->makeAnnouncement([
            'scope' => Implementation::FRONTEND_WEBSHOP,
            'implementation_id' => $otherImplementation->id,
        ]);

        $dashboards = $this->makeAnnouncement([
            'scope' => 'dashboards',
        ]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_WEBSHOP,
            'implementation_id' => $implementation->id,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($global->id, $ids);
        $this->assertContains($current->id, $ids);
        $this->assertNotContains($other->id, $ids);
        $this->assertNotContains($dashboards->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $match = $this->makeAnnouncement(['organization_id' => $this->organization->id]);
        $other = $this->makeAnnouncement(['organization_id' => $this->otherOrganization->id]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'organization_id' => $this->organization->id,
            'identity_address' => $this->identity->address,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersByIdentityAddressForRole(): void
    {
        $employee = $this->organization->findEmployee($this->identity->address);
        $this->assertNotNull($employee);

        $role = $employee->roles()->first();
        $this->assertNotNull($role);

        $match = $this->makeAnnouncement(['role_id' => $role->id]);
        $outsider = $this->makeIdentity();

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'organization_id' => $this->organization->id,
            'identity_address' => $this->identity->address,
        ]);

        $ids = $results->pluck('id')->toArray();
        $this->assertContains($match->id, $ids);

        $otherResults = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'organization_id' => $this->organization->id,
            'identity_address' => $outsider->address,
        ]);

        $otherIds = $otherResults->pluck('id')->toArray();
        $this->assertNotContains($match->id, $otherIds);
    }

    /**
     * @return void
     */
    public function testFiltersByIdentityAddressForBankConnection(): void
    {
        $bankConnectionType = (new BankConnection())->getMorphClass();
        $bankConnection = $this->makeBankConnection($this->organization);

        $match = $this->makeAnnouncement([
            'announceable_type' => $bankConnectionType,
            'announceable_id' => $bankConnection->id,
        ]);

        $otherBankConnection = $this->makeBankConnection($this->otherOrganization);

        $other = $this->makeAnnouncement([
            'announceable_type' => $bankConnectionType,
            'announceable_id' => $otherBankConnection->id,
        ]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'organization_id' => $this->organization->id,
            'identity_address' => $this->identity->address,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersFallbackToGlobalWhenMissingIdentity(): void
    {
        $employee = $this->organization->findEmployee($this->identity->address);
        $this->assertNotNull($employee);

        $role = $employee->roles()->first();
        $this->assertNotNull($role);

        $global = $this->makeAnnouncement();
        $organization = $this->makeAnnouncement(['organization_id' => $this->organization->id]);
        $roleAnnouncement = $this->makeAnnouncement(['role_id' => $role->id]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'organization_id' => $this->organization->id,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($global->id, $ids);
        $this->assertNotContains($organization->id, $ids);
        $this->assertNotContains($roleAnnouncement->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersFallbackToGlobalWhenMissingOrganization(): void
    {
        $employee = $this->organization->findEmployee($this->identity->address);
        $this->assertNotNull($employee);

        $role = $employee->roles()->first();
        $this->assertNotNull($role);

        $global = $this->makeAnnouncement();
        $organization = $this->makeAnnouncement(['organization_id' => $this->organization->id]);
        $roleAnnouncement = $this->makeAnnouncement(['role_id' => $role->id]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'identity_address' => $this->identity->address,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($global->id, $ids);
        $this->assertNotContains($organization->id, $ids);
        $this->assertNotContains($roleAnnouncement->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersExcludeInactiveAnnouncements(): void
    {
        $active = $this->makeAnnouncement(['active' => true]);
        $inactive = $this->makeAnnouncement(['active' => false]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersByStartAt(): void
    {
        $future = $this->makeAnnouncement(['start_at' => now()->addDay()]);
        $past = $this->makeAnnouncement(['start_at' => now()->subDay()]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($past->id, $ids);
        $this->assertNotContains($future->id, $ids);
    }

    /**
     * @return void
     */
    public function testFiltersByExpireAt(): void
    {
        $expired = $this->makeAnnouncement(['expire_at' => now()->subDay()]);
        $active = $this->makeAnnouncement(['expire_at' => now()->addDay()]);

        $results = $this->search([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ]);

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($expired->id, $ids);
    }

    /**
     * @param array $overrides
     * @return Announcement
     */
    protected function makeAnnouncement(array $overrides = []): Announcement
    {
        return Announcement::create([
            'type' => 'warning',
            'scope' => 'dashboards',
            'title' => $this->faker->text(),
            'description' => $this->faker->text(),
            'expire_at' => now()->addDay(),
            'active' => true,
            ...$overrides,
        ]);
    }

    /**
     * @param array $filters
     * @return Collection
     */
    protected function search(array $filters): Collection
    {
        return (new AnnouncementSearch($filters, Announcement::query()))->query()->get();
    }
}
