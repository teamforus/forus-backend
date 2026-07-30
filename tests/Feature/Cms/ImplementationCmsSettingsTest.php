<?php

namespace Tests\Feature\Cms;

use App\Helpers\Arr;
use App\Models\Implementation;
use Illuminate\Support\Carbon;
use Throwable;

class ImplementationCmsSettingsTest extends ImplementationCmsTestCase
{
    protected array $cmsResourceStructure = [
        'data' => [
            'id', 'key', 'name', 'url_webshop', 'title', 'organization_id',
            'description', 'description_alignment', 'description_html', 'informal_communication',
            'overlay_enabled', 'overlay_type', 'overlay_opacity',
            'show_home_map', 'show_home_products', 'show_providers_map', 'show_provider_map',
            'show_office_map', 'show_voucher_map', 'show_product_map',
            'allow_per_fund_notification_templates', 'communication_type',
        ],
        'data.announcement' => [
            'id', 'type', 'title', 'description', 'description_html', 'scope',
            'active', 'expire_at',
        ],
        'data.page_types.*' => [
            'blocks', 'description_position_configurable', 'faq', 'key', 'type', 'webshop_url',
        ],
        'data.pages.*' => [
            'description_alignment', 'description_html', 'description_position', 'external',
            'external_url', 'faq', 'page_type',
        ],
        'data.pages.*.blocks' => [],
        'data.pages.*.cms_blocks' => [],
    ];

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationCMS(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makeCMSData();

        $response = $this->patchJson($this->getUrlCMS($implementation), $pageBody, $this->makeApiHeaders($proxy));
        $response->assertSuccessful();
        $response->assertJsonStructure(Arr::undot($this->cmsResourceStructure));

        $this->assertImplementationCMSSaved($response->json('data.id'), $pageBody);
    }

    /**
     * @return array
     */
    protected function makeAnnouncementData(): array
    {
        return [
            'type' => Arr::random(['warning', 'danger', 'success', 'primary', 'default']),
            'title' => $this->faker->text(2000),
            'description' => $this->faker->text(8000),
            'expire_at' => now()->addDays(10)->format('Y-m-d'),
            'replace' => (bool) rand(0, 1),
            'active' => (bool) rand(0, 1),
        ];
    }

    /**
     * @return array
     */
    protected function makeCMSData(): array
    {
        return [
            'title' => $this->faker->text(50),
            'description' => $this->faker->text(400),
            'description_alignment' => Arr::random(['left', 'center', 'right']),
            'informal_communication' => (bool) rand(0, 1),
            'overlay_enabled' => (bool) rand(0, 1),
            'overlay_type' => Arr::random(['color', 'dots', 'lines', 'points', 'circles']),
            'overlay_opacity' => $this->faker->numberBetween(0, 100),
            'show_home_map' => (bool) rand(0, 1),
            'show_office_map' => (bool) rand(0, 1),
            'show_home_products' => (bool) rand(0, 1),
            'show_providers_map' => (bool) rand(0, 1),
            'show_provider_map' => (bool) rand(0, 1),
            'show_voucher_map' => (bool) rand(0, 1),
            'show_product_map' => (bool) rand(0, 1),
            'announcement' => $this->makeAnnouncementData(),
        ];
    }

    /**
     * @param Implementation $implementation
     * @return string
     */
    protected function getUrlCMS(Implementation $implementation): string
    {
        return sprintf($this->apiUrl . '/cms', $implementation->organization_id, $implementation->id);
    }

    /**
     * @param int $id
     * @param array $body
     * @throws Throwable
     * @return void
     */
    protected function assertImplementationCMSSaved(int $id, array $body): void
    {
        $implementation = Implementation::find($id);
        $announcementData = Arr::except(Arr::get($body, 'announcement'), 'replace');

        foreach (Arr::except($body, ['announcement']) as $key => $value) {
            $this->assertEquals($value, $implementation[$key]);
        }

        if ($announcementData) {
            $this->assertEquals(array_merge($announcementData, [
                'expire_at' => Carbon::parse($announcementData['expire_at']),
            ]), $implementation->announcements_webshop[0]->only(array_keys($announcementData)));
        }
    }
}
