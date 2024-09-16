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
    use DatabaseTransactions;
    use WithFaker;
    use MakesTestOrganizations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'client_type' => 'sponsor',
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
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/announcements';

    /**
     * @var string
     */
    protected string $apiUrlConfig = '/api/v1/platform/config/dashboard';

    /**
     * @return void
     * @throws \Throwable
     */
    public function testGlobalAnnouncement(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $announcement = $this->makeAnnouncement();

        $this->assertAnnouncementsInConfig($identity, $announcement);

        $this->assertAnnouncementsForOrganization(
            $organization,
            $identity,
            $announcement,
            false,
            'Global announcement must not be visible in organization request'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testAnnouncementForOrganization(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $announcement = $this->makeAnnouncement([
            'organization_id' => $organization->id,
        ]);

        $this->assertAnnouncementsInConfig(
            $identity,
            $announcement,
            false,
            'Announcement for organization must be not visible for implementation'
        );

        $this->assertAnnouncementsForOrganization($organization, $identity, $announcement);

        // make another organization
        $secondOrganization = $this->makeTestOrganization($identity);

        $this->assertAnnouncementsForOrganization(
            $secondOrganization,
            $identity,
            $announcement,
            false,
            'Announcement must be visible for other organizations'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testAnnouncementForRole(): void
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

        $this->assertAnnouncementsInConfig(
            $identity,
            $announcement,
            false,
            'Announcement for organization must be not visible for implementation'
        );

        $this->assertAnnouncementsForOrganization($organization, $identity, $announcement);

        // make another employee
        $secondIdentity = $this->makeIdentity();
        $roles = Role::whereNotIn('id', [$role->id])->pluck('id')->toArray();
        $organization->addEmployee($secondIdentity, $roles);

        $this->assertAnnouncementsForOrganization(
            $organization,
            $secondIdentity,
            $announcement,
            false,
            'Announcement must be visible for other roles'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testAnnouncementForOrganizationAndRole(): void
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

        $this->assertAnnouncementsInConfig(
            $identity,
            $announcement,
            false,
            'Announcement for organization must be not visible for implementation'
        );

        $this->assertAnnouncementsForOrganization($organization, $identity, $announcement);

        // make another employee
        $secondIdentity = $this->makeIdentity();
        $roles = Role::whereNotIn('id', [$role->id])->pluck('id')->toArray();
        $organization->addEmployee($secondIdentity, $roles);

        $this->assertAnnouncementsForOrganization(
            $organization,
            $secondIdentity,
            $announcement,
            false,
            'Announcement must not be visible for other roles',
        );

        $organization->addEmployee($secondIdentity, [$role->id]);

        $this->assertAnnouncementsForOrganization($organization, $secondIdentity, $announcement);

        // make another organization
        $secondOrganization = $this->makeTestOrganization($identity);
        $secondOrganization->addEmployee($secondIdentity, $roles);

        $this->assertAnnouncementsForOrganization(
            $secondOrganization,
            $secondIdentity,
            $announcement,
            false,
            'Announcement must not be visible for other roles and other organizations'
        );

        $secondOrganization->addEmployee($secondIdentity, [$role->id]);

        $this->assertAnnouncementsForOrganization(
            $secondOrganization,
            $secondIdentity,
            $announcement,
            false,
            'Announcement must not be visible for same role and other organizations'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testAnnouncementStartAtDate(): void
    {
        $identity = $this->makeIdentity();

        $announcement = $this->makeAnnouncement([
            'start_at' => now()->addDay(),
        ]);

        $this->assertAnnouncementsInConfig(
            $identity,
            $announcement,
            false,
            'Announcement must be not visible for implementation if not started'
        );

        $announcement->update(['start_at' => now()->subDay()]);

        $this->assertAnnouncementsInConfig($identity, $announcement);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testAnnouncementExpireAtDate(): void
    {
        $identity = $this->makeIdentity();

        $announcement = $this->makeAnnouncement([
            'expire_at' => now()->subDay(),
        ]);

        $this->assertAnnouncementsInConfig(
            $identity,
            $announcement,
            false,
            'Announcement must be not visible for implementation if not started'
        );

        $announcement->update(['expire_at' => now()->addDay()]);

        $this->assertAnnouncementsInConfig($identity, $announcement);
    }

    /**
     * @param Identity $identity
     * @param Announcement $announcement
     * @param bool $assert
     * @param string $message
     * @return void
     */
    private function assertAnnouncementsInConfig(
        Identity $identity,
        Announcement $announcement,
        bool $assert = true,
        string $message = 'Announcement must be visible for implementation',
    ): void {
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->getJson($this->apiUrlConfig, $this->makeApiHeaders($proxy));
        $response->assertSuccessful();
        $response->assertJsonStructure(['announcements']);

        $announcementItem = collect($response->json('announcements'))
            ->first(fn ($item) => $item['id'] === $announcement->id);

        if ($assert) {
            $this->assertNotNull($announcementItem, $message);
        } else {
            $this->assertNull($announcementItem, $message);
        }
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param Announcement $announcement
     * @param bool $assert
     * @param string $message
     * @return void
     */
    private function assertAnnouncementsForOrganization(
        Organization $organization,
        Identity $identity,
        Announcement $announcement,
        bool $assert = true,
        string $message = 'Announcement must be visible for organization',
    ): void {
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->getJson(
            sprintf($this->apiUrl, $organization->id),
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();
        $response->assertJsonStructure(Arr::undot($this->announcementStructure));

        $announcementItem = collect($response->json('data'))
            ->first(fn ($item) => $item['id'] === $announcement->id);

        if ($assert) {
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
            'type' => Arr::random(['warning', 'danger', 'success', 'primary', 'default']),
            'scope' => 'dashboards',
            'title' => $this->faker->text(2000),
            'description' => $this->faker->text(8000),
            'expire_at' => now()->addDays(10)->format('Y-m-d'),
            'active' => true,
            ...$params,
        ]);
    }
}
