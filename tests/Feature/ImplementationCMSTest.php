<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Faq;
use App\Models\Implementation;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use App\Services\MediaService\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Throwable;

class ImplementationCMSTest extends TestCase
{
    use DatabaseTransactions, WithFaker, AssertsSentEmails;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/implementations/%s';

    /**
     * @var string
     */
    protected string $apiMediaUrl = '/api/v1/medias';

    /**
     * @var string
     */
    protected string $implementationKey = 'nijmegen';

    /**
     * @var array
     */
    protected array $pageResourceStructure = [
        'data' => [
            'id', 'page_type', 'external', 'external_url', 'state', 'blocks', 'description',
            'description_alignment', 'description_position', 'description_html',
            'implementation_id', 'url_webshop',
        ],
        'data.implementation' => [
            'id', 'name', 'url_webshop', 'organization_id',
        ],
        'data.blocks.*' => [
            'id', 'label', 'title', 'description', 'description_html',
            'button_text', 'button_link', 'button_target_blank', 'button_enabled',
        ],
        'data.faq.*'  => [
            'id', 'title', 'description', 'description_html',
        ],
        'data.blocks.*.media' => [
            'identity_address', 'original_name', 'dominant_color', 'is_dark', 'type', 'ext', 'uid',
        ],
        'data.blocks.*.media.sizes' => [],
    ];

    /**
     * @var array
     */
    protected array $cmsResourceStructure = [
        'data' => [
            'id', 'key', 'name', 'url_webshop', 'title', 'organization_id',
            'description', 'description_alignment', 'description_html', 'informal_communication',
            'overlay_enabled', 'overlay_type', 'overlay_opacity', 'header_text_color',
            'show_home_map', 'show_home_products', 'show_providers_map', 'show_provider_map',
            'show_office_map', 'show_voucher_map', 'show_product_map',
            'allow_per_fund_notification_templates', 'communication_type', 'overlay_opacity',
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
    ];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testStoreImplementationPage(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makePageData();
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $this->makeApiHeaders($proxy));

        $this->assertImplementationPageSaved($response->json('data.id'), $pageBody);
        $response->assertSuccessful();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testStoreInvalidImplementationPage(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageData = $this->makePageData();

        // assert has validation errors
        $response = $this->postJson(
            $this->getUrlPages($implementation),
            array_fill_keys(array_keys($pageData), null),
            $this->makeApiHeaders($proxy),
        );

        $response->assertJsonValidationErrors(array_keys(array_except($pageData, [
            'state', 'description', 'description_position', 'description_alignment', 'blocks', 'external_url'
        ])));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateImplementationPage(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makePageData();

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            $pageBody,
            $this->makeApiHeaders($proxy)
        )->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $urlPage = $this->getUrlPages($implementation, $implementationPage);

        $this->assertImplementationPageSaved($implementationPage->id, $pageBody);

        $state = $implementationPage->state == ImplementationPage::STATE_DRAFT ?
            ImplementationPage::STATE_PUBLIC :
            ImplementationPage::STATE_DRAFT;

        $updateBody = [
            'state' => $state,
            'external' => $implementationPage->external,
            'external_url' => $implementationPage->external_url,
        ];

        // Check page status update
        $this->patchJson($urlPage, $updateBody, $this->makeApiHeaders($proxy))->assertSuccessful();
        $this->assertImplementationPageSaved($implementationPage->id, $updateBody);

        $pageBody = $this->makePageData(['page_type' => $implementationPage->page_type]);
        $response = $this->patchJson($urlPage, $pageBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $response->assertJsonStructure(Arr::undot($this->pageResourceStructure));
        $this->assertImplementationPageSaved($implementationPage->id, $pageBody);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSyncImplementationPageBlocks(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            $this->makePageData(),
            $this->makeApiHeaders($proxy),
        )->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);
        $implementationPageBlocks = $implementationPage->blocks()->select($this->blockKeys())->get();
        $blocksData = $implementationPageBlocks->push($this->makePageBlockData())->toArray();

        $pageBody = [
            'blocks' => $blocksData,
            'external' => $implementationPage->external,
            'external_url' => $implementationPage->external_url,
        ];

        $this->patchJson($implementationPageUrl, $pageBody, $this->makeApiHeaders($proxy));
        $this->assertImplementationPageSaved($implementationPage->id, $pageBody);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSyncImplementationPageFaq(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $pageType = Arr::where(ImplementationPage::PAGE_TYPES, fn ($type) => $type['faq']);
        $pageBody = $this->makePageData(['page_type' => array_values($pageType)[0]['key']]);
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $this->assertImplementationPageSaved($response->json('data.id'), $pageBody);

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);

        $updateBody = [
            'faq' => array_map(fn () => $this->makeFAQData(), range(1, 3)),
            'external' => $implementationPage->external,
            'external_url' => $implementationPage->external_url,
        ];

        $response = $this->patchJson($implementationPageUrl, $updateBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $this->assertImplementationPageSaved($response->json('data.id'), $updateBody);

        // Verify if reordering FAQ works
        $updateBody['faq'] = $implementationPage->faq()->select(['id', 'title', 'description'])->get();
        $updateBody['faq'] = $updateBody['faq']->shuffle()->toArray();

        $response = $this->patchJson($implementationPageUrl, $updateBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $this->assertImplementationPageSaved($response->json('data.id'), $updateBody);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateInvalidImplementationPage(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makePageData();
        $bodyEmpty = array_fill_keys(array_keys($pageBody), null);
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $this->assertImplementationPageSaved($response->json('data.id'), $pageBody);

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);

        $response = $this->patchJson($implementationPageUrl, $bodyEmpty, $this->makeApiHeaders($proxy));

        $response->assertJsonValidationErrors(array_keys(array_except($pageBody, [
            'state', 'description', 'description_position', 'description_alignment', 'blocks',
            'external_url', 'page_type',
        ])));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testDeleteImplementationPage(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $proxyHeaders = $this->makeApiHeaders($proxy);

        $pageBody = $this->makePageData();
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $proxyHeaders);

        $response->assertSuccessful();
        $this->assertImplementationPageSaved($response->json('data.id'), $pageBody);

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);

        $this->deleteJson($implementationPageUrl)->assertUnauthorized();
        $this->deleteJson($implementationPageUrl, [], $proxyHeaders)->assertSuccessful();
        $this->assertNull(ImplementationPage::find($response->json('data.id')));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateImplementationCMS(): void
    {
        $implementation = $this->getImplementation();
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makeCMSData();

        $response = $this->patchJson($this->getUrlCMS($implementation), $pageBody, $this->makeApiHeaders($proxy));
        $response->assertSuccessful();
        $response->assertJsonStructure(Arr::undot($this->cmsResourceStructure));

        $this->assertImplementationCMSSaved($response->json('data.id'), $pageBody);
    }

    /**
     * @param string $mediaType
     * @return Media
     * @throws \Exception
     */
    protected function makeMedia(string $mediaType): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return resolve('media')->uploadSingle($file, $fileName, $mediaType);
    }

    /**
     * @param Media $media
     * @return string
     */
    protected function makeMarkdownDescription(Media $media): string {
        return implode("  \n", [
            '# '.$this->faker->text(50),
            '![]('. $media->urlPublic('public') .')',
            '# '.$this->faker->text(50),
        ]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function makePageBlockData(): array
    {
        return [
            'button_enabled' => (bool) rand(0, 1),
            'button_link' => $this->faker->url,
            'button_target_blank' => (bool) rand(0, 1),
            'button_text' => $this->faker->text(100),
            'description' => $this->faker->text(),
            'label' => $this->faker->text(100),
            'title' => $this->faker->text(100),
            'media_uid' => $this->makeMedia('implementation_block_media')->uid,
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function makeFAQData(): array
    {
        return [
            'title' => $this->faker->text(100),
            'description' => $this->makeMarkdownDescription($this->makeMedia('cms_media')),
        ];
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
     * @throws \Exception
     */
    protected function makeCMSData(): array
    {
        return [
            'title' => $this->faker->text(50),
            'description' => $this->faker->text(4000),
            'description_alignment' => Arr::random(['left', 'center', 'right']),
            'informal_communication' => (bool) rand(0, 1),
            'overlay_enabled' => (bool) rand(0, 1),
            'overlay_type' => Arr::random(['color', 'dots', 'lines', 'points', 'circles']),
            'overlay_opacity' => $this->faker->numberBetween(0, 100),
            'header_text_color' => Arr::random(['bright', 'dark', 'auto']),
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
     * @param array $replace
     * @return array
     * @throws \Exception
     */
    protected function makePageData(array $replace = []): array
    {
        $implementation = $this->getImplementation();
        $pageTypes = Arr::pluck(ImplementationPage::PAGE_TYPES, 'key');
        $pageTypes = array_diff($pageTypes, $implementation->pages()->pluck('page_type')->toArray());
        $external = (bool) rand(0, 1);

        return array_merge([
            'state'=> ImplementationPage::STATE_DRAFT,
            'blocks' => array_map(fn () => $this->makePageBlockData(), range(0, rand(1, 5))),
            'external' => $external,
            'page_type'=> Arr::random($pageTypes),
            'description' => $this->makeMarkdownDescription($this->makeMedia('cms_media')),
            'external_url' => $external ? $this->faker->url : null,
            'description_position' => Arr::random(ImplementationPage::DESCRIPTION_POSITIONS),
            'description_alignment' => Arr::random(['left', 'center', 'right']),
        ], $replace);
    }

    /**
     * @return Implementation
     */
    protected function getImplementation(): Implementation
    {
        $implementation = $this->findImplementation($this->implementationKey);
        $this->assertNotNull($implementation);

        return $implementation;
    }

    /**
     * @return array
     * @throws Throwable
     */
    protected function blockKeys(): array
    {
        return array_keys(Arr::except($this->makePageBlockData(), 'media_uid'));
    }

    /**
     * @return array
     * @throws Throwable
     */
    protected function announcementKeys(): array
    {
        return array_keys($this->makeAnnouncementData());
    }

    /**
     * @return array
     * @throws Throwable
     */
    protected function faqKeys(): array
    {
        return array_keys($this->makeFAQData());
    }

    /**
     * @param Implementation $implementation
     * @param ImplementationPage|null $implementationPage
     * @return string
     */
    protected function getUrlPages(
        Implementation $implementation,
        ?ImplementationPage $implementationPage = null
    ): string {
        return sprintf(
            $this->apiUrl . ($implementationPage ? "/pages/%s" : '/pages'),
            $implementation->organization_id,
            $implementation->id,
            $implementationPage?->id,
        );
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
     * @return void
     * @throws Throwable
     */
    private function assertImplementationPageSaved(int $id, array $body): void
    {
        $page = ImplementationPage::find($id);
        $faqKeys = $this->faqKeys();
        $blockKeys = $this->blockKeys();

        $body['external'] = !$page::isInternalType($page->page_type) && $body['external'];
        $body['external_url'] = $body['external'] ? $body['external_url'] : null;

        foreach (Arr::except($body, ['faq', 'blocks']) as $key => $value) {
            $this->assertEquals($value, $page[$key]);
        }

        if (isset($body['blocks'])) {
            $this->assertEquals($page->blocks->count(), count($body['blocks']));

            /** @var ImplementationBlock $block */
            foreach ($page->blocks as $index => $block) {
                $this->assertEquals($block->only($blockKeys), Arr::only($body['blocks'][$index], $blockKeys));

                // if there's an image check if it was sync-ed
                if (isset($body['blocks'][$index]['media_uid'])) {
                    $this->assertEquals($block->photo->uid, $body['blocks'][$index]['media_uid']);
                }
            }
        }

        if (isset($body['faq'])) {
            $this->assertEquals(count($body['faq']), $page->faq->count());

            /** @var Faq $faq */
            foreach ($page->faq as $index => $faq) {
                $this->assertEquals($faq->only($faqKeys), Arr::only($body['faq'][$index], $faqKeys));
                $this->assertEquals(1, $faq->medias()->count());
            }
        }
    }

    /**
     * @param int $id
     * @param array $body
     * @return void
     * @throws Throwable
     */
    private function assertImplementationCMSSaved(int $id, array $body): void
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
