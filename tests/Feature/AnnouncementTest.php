<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Announcement;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;

class AnnouncementTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'sponsor',
    ];

    /**
     * @var array|array[]
     */
    protected array $announcementStructure = [
        'data.*' => [
            'id', 'type', 'title', 'description_html', 'scope', 'dismissible',
        ]
    ];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSystemAnnouncementVisible(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $systemAnnouncement = $this->makeAnnouncement();

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $systemAnnouncement,
            true,
            'System announcement must be visible'
        );

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $identity,
            $systemAnnouncement,
            false,
            'System announcement must not be visible in organization request'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testOrganizationAnnouncementVisibleOnlyForOrganization(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $announcement = $this->makeAnnouncement([
            'organization_id' => $organization->id,
        ]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $announcement,
            false,
            'Organization announcement must not be visible as system'
        );

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $identity,
            $announcement,
            true,
            'Organization announcement must be visible for connected organization'
        );

        // make another organization
        $secondOrganization = $this->makeTestOrganization($identity);

        $this->assertOrganizationAnnouncementVisibility(
            $secondOrganization,
            $identity,
            $announcement,
            false,
            'Organization announcement must not be visible for other organizations'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testOrganizationAnnouncementVisibleOnlyForRole(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->findEmployee($identity->address);
        $this->assertNotNull($employee);

        /** @var Role $role */
        $role = $employee->roles()->first();
        $this->assertNotNull($role);

        $announcement = $this->makeAnnouncement([
            'role_id' => $role->id,
        ]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $announcement,
            false,
            'Organization announcement must not be visible as system'
        );

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $identity,
            $announcement,
            true,
            'Organization announcement must be visible for connected role'
        );

        // make another employee
        $secondIdentity = $this->makeIdentity();
        $roles = Role::whereNotIn('id', [$role->id])->pluck('id')->toArray();
        $organization->addEmployee($secondIdentity, $roles);

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $secondIdentity,
            $announcement,
            false,
            'Organization announcement must not be visible for other roles'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testOrganizationAnnouncementVisibleOnlyForOrganizationAndRole(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->findEmployee($identity->address);
        $this->assertNotNull($employee);

        /** @var Role $role */
        $role = $employee->roles()->first();
        $this->assertNotNull($role);

        $announcement = $this->makeAnnouncement([
            'role_id' => $role->id,
            'organization_id' => $organization->id,
        ]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $announcement,
            false,
            'Organization announcement must not be visible as system'
        );

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $identity,
            $announcement,
            true,
            'Organization announcement must be visible for connected organization and role'
        );

        // make another employee
        $secondIdentity = $this->makeIdentity();
        $roles = Role::whereNotIn('id', [$role->id])->pluck('id')->toArray();
        $organization->addEmployee($secondIdentity, $roles);

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $secondIdentity,
            $announcement,
            false,
            'Organization announcement must not be visible for other roles from same organization',
        );

        $organization->addEmployee($secondIdentity, [$role->id]);

        $this->assertOrganizationAnnouncementVisibility(
            $organization,
            $secondIdentity,
            $announcement,
            true,
            'Organization announcement must be visible for connected organization and role'
        );

        // make another organization
        $secondOrganization = $this->makeTestOrganization($identity);
        $secondOrganization->addEmployee($secondIdentity, $roles);

        $this->assertOrganizationAnnouncementVisibility(
            $secondOrganization,
            $secondIdentity,
            $announcement,
            false,
            'Organization announcement must not be visible for other roles and other organization'
        );

        $secondOrganization->addEmployee($secondIdentity, [$role->id]);

        $this->assertOrganizationAnnouncementVisibility(
            $secondOrganization,
            $secondIdentity,
            $announcement,
            false,
            'Organization announcement must not be visible for connected role and other organization'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSystemAnnouncementStartAtDateVisibility(): void
    {
        $identity = $this->makeIdentity();

        $systemAnnouncement = $this->makeAnnouncement([
            'start_at' => now()->addDay(),
        ]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $systemAnnouncement,
            false,
            'System announcement must not be visible for implementation if not started'
        );

        $systemAnnouncement->update(['start_at' => now()->subDay()]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $systemAnnouncement,
            true,
            'System announcement must be visible for implementation if started'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSystemAnnouncementExpireAtDateVisibility(): void
    {
        $identity = $this->makeIdentity();

        $systemAnnouncement = $this->makeAnnouncement([
            'expire_at' => now()->subDay(),
        ]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $systemAnnouncement,
            false,
            'System announcement must not be visible for implementation if expired'
        );

        $systemAnnouncement->update(['expire_at' => now()->addDay()]);

        $this->assertSystemAnnouncementVisibility(
            $identity,
            $systemAnnouncement,
            true,
            'System announcement must be visible for implementation if not expired'
        );
    }

    /**
     * @param Identity $identity
     * @param Announcement $announcement
     * @param bool $assertVisible
     * @param string|null $message
     * @return void
     */
    private function assertSystemAnnouncementVisibility(
        Identity $identity,
        Announcement $announcement,
        bool $assertVisible,
        string $message = null,
    ): void {
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->getJson(
            '/api/v1/platform/config/dashboard',
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();
        $response->assertJsonStructure(['announcements']);

        $announcementItem = collect($response->json('announcements'))
            ->first(fn ($item) => $item['id'] === $announcement->id);

        if ($assertVisible) {
            $this->assertNotNull($announcementItem, $message);
        } else {
            $this->assertNull($announcementItem, $message);
        }
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param Announcement $announcement
     * @param bool $assertVisible
     * @param string|null $message
     * @return void
     */
    private function assertOrganizationAnnouncementVisibility(
        Organization $organization,
        Identity $identity,
        Announcement $announcement,
        bool $assertVisible,
        string $message = null,
    ): void {
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/announcements",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();
        $response->assertJsonStructure(Arr::undot($this->announcementStructure));

        $announcementItem = collect($response->json('data'))
            ->first(fn ($item) => $item['id'] === $announcement->id);

        if ($assertVisible) {
            $this->assertNotNull($announcementItem, $message);
        } else {
            $this->assertNull($announcementItem, $message);
        }
    }

    /**
     * @param array $params
     * @return Announcement
     */
    private function makeAnnouncement(array $params = []): Announcement
    {
        return Announcement::create([
            'type' => 'warning',
            'scope' => 'dashboards',
            'title' => $this->faker->text(2000),
            'description' => $this->faker->text(8000),
            'expire_at' => now()->addDays(10)->format('Y-m-d'),
            'active' => true,
            ...$params,
        ]);
    }
}
