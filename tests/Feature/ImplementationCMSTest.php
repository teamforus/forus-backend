<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Faq;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use App\Services\MediaService\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImplementationCMSTest extends TestCase
{
    use DatabaseTransactions, WithFaker, AssertsSentEmails;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/implementations/%s/';

    /**
     * @var string
     */
    protected string $apiMediaUrl = '/api/v1/medias';

    protected string $implementationName = 'nijmegen';

    /**
     * @var array
     */
    protected array $pageResourceStructure = [
        'id',
        'page_type',
        'external',
        'external_url',
        'state',
        'blocks',
        'description',
        'description_alignment',
        'description_position',
        'description_html',
        'implementation_id',
        'url_webshop',
        'implementation' => [
            'id',
            'name',
            'url_webshop',
            'organization_id'
        ],
        'blocks' => [
            '*' => [
                'id',
                'label',
                'title',
                'description',
                'description_html',
                'button_text',
                'button_link',
                'button_target_blank',
                'button_enabled',
                'media' => [
                    'identity_address',
                    'original_name',
                    'dominant_color',
                    'sizes' => [],
                    'is_dark',
                    'type',
                    'ext',
                    'uid',
                ],
            ],
        ],
        'faq'  => [
            '*' => [
                'id',
                'title',
                'description',
                'description_html'
            ],
        ],
    ];

    /**
     * @var array
     */
    protected array $cmsResourceStructure = [
        'id',
        'key',
        'name',
        'url_webshop',
        'title',
        'organization_id',
        'description',
        'description_alignment',
        'description_html',
        'informal_communication',
        'overlay_enabled',
        'overlay_type',
        'overlay_opacity',
        'header_text_color',
        'show_home_map',
        'show_home_products',
        'show_providers_map',
        'show_provider_map',
        'show_office_map',
        'show_voucher_map',
        'show_product_map',
        'allow_per_fund_notification_templates',
        'communication_type',
        'overlay_opacity',
        'announcement' => [
            'id',
            'type',
            'title',
            'description',
            'description_html',
            'scope',
            'active',
            'expire_at',
        ],
        'pages' => [
            '*' => [
                'blocks' => [],
                'description_alignment',
                'description_html',
                'description_position',
                'external',
                'external_url',
                'faq',
                'page_type',
            ],
        ],
        'page_types' => [
            '*' => [
                'blocks',
                'description_position_configurable',
                'faq',
                'key',
                'type',
                'webshop_url',
            ],
        ]
    ];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testStoreImplementationPage(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->postJson(sprintf(
            $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
        ), $this->makeImplementationPageRequestBody(), $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testStoreInvalidImplementationPage(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $body = $this->makeImplementationPageRequestBody();

        // assert has validation errors
        $response = $this->postJson(sprintf(
            $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
        ), array_fill_keys(array_keys($body), null), $this->makeApiHeaders($proxy));

        $response->assertJsonValidationErrors(array_keys(array_except($body, [
            'state', 'description', 'description_position', 'description_alignment', 'blocks', 'external_url'
        ])));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateImplementationPage(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->postJson(sprintf(
            $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
        ), $this->makeImplementationPageRequestBody(), $this->makeApiHeaders($proxy))->assertSuccessful();
        $implementationPage = ImplementationPage::find($response->json('data.id'));

        $state = $implementationPage->state == ImplementationPage::STATE_DRAFT ? ImplementationPage::STATE_PUBLIC : ImplementationPage::STATE_DRAFT;

        // Check page status update
        $response = $this->patchJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), [
            'state'     => $state,
            'external'  => $implementationPage->external
        ], $this->makeApiHeaders($proxy));
        $response->assertSuccessful();

        $implementationPage->refresh();
        $this->assertEquals($implementationPage->state, $state);

        $response = $this->patchJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), $this->makeImplementationPageRequestBody(), $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->pageResourceStructure]);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSyncImplementationPageBlocks(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->postJson(sprintf(
            $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
        ), $this->makeImplementationPageRequestBody(), $this->makeApiHeaders($proxy))->assertSuccessful();
        $implementationPage = ImplementationPage::find($response->json('data.id'));

        $blockFields = [
            'button_enabled', 'button_link', 'button_target_blank', 'button_text',
            'description', 'label', 'title'
        ];

        // Check if page blocks data is sync-ed correctly
        $blocksData = $implementationPage->blocks()->select($blockFields)->get()->toArray();

        // Add an extra block to the existing blocks
        $blocksData[] = $this->generatePageBlockData();

        $this->patchJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), [
            'external'  => $implementationPage->external,
            'blocks'    => $blocksData,
        ], $this->makeApiHeaders($proxy));

        $implementationPage->refresh();
        $this->assertEquals(count($implementationPage->blocks), count($blocksData));

        /** @var ImplementationBlock $block */
        foreach ($implementationPage->blocks as $index => $block) {
            $this->assertEquals($block->only($blockFields), collect($blocksData[$index])->only($blockFields)->toArray());
        }
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSyncImplementationPageFaq(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $valid_faq_page_types = collect(ImplementationPage::PAGE_TYPES)->filter(function ($page) {
            return $page['faq'];
        })->pluck('key')->toArray();
        $faq_pages = $implementation->pages()->whereIn('page_type', $valid_faq_page_types);

        if ($faq_pages->count()) {
            $implementationPage = $faq_pages->first();
        } else {
            $response = $this->postJson(sprintf(
                $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
            ), array_merge($this->makeImplementationPageRequestBody(), [
                'page_type' => $valid_faq_page_types[0]
            ]), $this->makeApiHeaders($proxy))->assertSuccessful();

            $implementationPage = ImplementationPage::find($response->json('data.id'));
        }

        $faqFields = [ 'title', 'description' ];
        $faqData = $this->generatePageFaqData(3);

        $this->patchJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), [
            'external'  => $implementationPage->external,
            'faq'       => $faqData,
        ], $this->makeApiHeaders($proxy));

        $implementationPage->refresh();
        $this->assertEquals(count($implementationPage->faq), count($faqData));

        /** @var Faq $faq */
        foreach ($implementationPage->faq as $index => $faq) {
            $this->assertEquals($faq->only($faqFields), collect($faqData[$index])->only($faqFields)->toArray());
            $this->assertEquals($faq->medias()->pluck('uid')->toArray(), $faqData[$index]['description_media_uid']);
        }

        // Verify if reordering FAQ works
        $faqData = $implementationPage->faq()->select(['id', 'title', 'description'])->get()->toArray();
        shuffle($faqData);

        $this->patchJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), [
            'external'  => $implementationPage->external,
            'faq'       => $faqData,
        ], $this->makeApiHeaders($proxy));

        $implementationPage->refresh();
        $this->assertEquals(count($implementationPage->faq), count($faqData));

        /** @var Faq $faq */
        foreach ($implementationPage->faq as $index => $faq) {
            $this->assertEquals($faq->only($faqFields), collect($faqData[$index])->only($faqFields)->toArray());
        }
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateInvalidImplementationPage(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $body = $this->makeImplementationPageRequestBody();

        $response = $this->postJson(sprintf(
            $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
        ), $this->makeImplementationPageRequestBody(), $this->makeApiHeaders($proxy))->assertSuccessful();
        $implementationPage = ImplementationPage::find($response->json('data.id'));

        // assert has validation errors
        $response = $this->patchJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), array_fill_keys(array_keys($body), null), $this->makeApiHeaders($proxy));

        $response->assertJsonValidationErrors(array_keys(array_except($body, [
            'state', 'description', 'description_position', 'description_alignment', 'blocks', 'external_url', 'page_type',
        ])));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testDeleteImplementationPage(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->postJson(sprintf(
            $this->apiUrl . 'pages', $implementation->organization_id, $implementation->id
        ), $this->makeImplementationPageRequestBody(), $this->makeApiHeaders($proxy))->assertSuccessful();
        $implementationPage = ImplementationPage::find($response->json('data.id'));

        $this->deleteJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ))->assertUnauthorized();

        $this->deleteJson(sprintf(
            $this->apiUrl . 'pages/%s', $implementation->organization_id, $implementation->id, $implementationPage->id
        ), [], $this->makeApiHeaders($proxy))->assertSuccessful();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateImplementationCMS(): void
    {
        $implementation = $this->findImplementation($this->implementationName);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $cmsData = $this->makeCMSData();

        $response = $this->patchJson(sprintf(
            $this->apiUrl . 'cms/', $implementation->organization_id, $implementation->id
        ), $cmsData, $this->makeApiHeaders($proxy));
        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->cmsResourceStructure]);

        $implementation->refresh();

        $this->assertEquals($implementation->show_home_map, $cmsData['show_home_map']);
        $this->assertEquals($implementation->show_home_products, $cmsData['show_home_products']);
        $this->assertEquals($implementation->show_office_map, $cmsData['show_office_map']);
        $this->assertEquals($implementation->show_product_map, $cmsData['show_product_map']);
        $this->assertEquals($implementation->show_provider_map, $cmsData['show_provider_map']);
        $this->assertEquals($implementation->show_providers_map, $cmsData['show_providers_map']);
        $this->assertEquals($implementation->show_voucher_map, $cmsData['show_voucher_map']);
    }

    /**
     * @param string $mediaType
     * @return Media
     * @throws \Exception
     */
    protected function uploadMedia(string $mediaType): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return resolve('media')->uploadSingle($file, $fileName, $mediaType);
    }

    /**
     * @param Media $media
     * @return string
     */
    protected function generateMarkdownDescription(Media $media): string {
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
    protected function generatePageBlockData(): array
    {
        return [
            'button_enabled' => Arr::random([true, false]),
            'button_link'    => $this->faker->url,
            'button_target_blank' => Arr::random([true, false]),
            'button_text'   => $this->faker->text(100),
            'description'   => $this->faker->text(),
            'label'         => $this->faker->text(100),
            'title'         => $this->faker->text(100),
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function generatePageFaqSingle(): array
    {
        $media = $this->uploadMedia('cms_media');

        return [
            'title'                 => $this->faker->text(100),
            'description'           => $this->generateMarkdownDescription($media),
            'description_media_uid' => [ $media->uid ],
        ];
    }

    /**
     * @param $nrBlocks
     * @return array
     * @throws \Exception
     */
    protected function generatePageFaqData($nrBlocks): array
    {
        $data = [];

        for ($i = 0; $i < $nrBlocks; ++$i) {
            $data[] = $this->generatePageFaqSingle();
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function generateSingleAnnouncement(): array
    {
        return [
            'type'         => Arr::random(['warning,danger,success,primary,default']),
            'title'        => $this->faker->text(2000),
            'description'  => $this->faker->text(8000),
            'expire_at'    => now()->addDays(10)->format('Y-m-d'),
            'active'       => Arr::random([true, false]),
            'replace'      => Arr::random([true, false]),
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function makeCMSData(): array
    {
        $announcements = [];
        for ($i = 0; $i < 3; ++$i) {
            $announcements[] = $this->generateSingleAnnouncement();
        }

        return [
            'title'         => $this->faker->text(50),
            'description'   => $this->faker->text(4000),
            'description_alignment' => Arr::random(['left', 'center', 'right']),
            'informal_communication' => Arr::random([true, false]),
            'overlay_enabled'   => Arr::random([true, false]),
            'overlay_type'      => Arr::random(['color', 'dots', 'lines', 'points', 'circles']),
            'overlay_opacity'   => $this->faker->numberBetween(0, 100),
            'header_text_color' => Arr::random(['bright', 'dark', 'auto']),
            'show_home_map'     => Arr::random([true, false]),
            'show_office_map'   => Arr::random([true, false]),
            'show_home_products' => Arr::random([true, false]),
            'show_providers_map' => Arr::random([true, false]),
            'show_provider_map' => Arr::random([true, false]),
            'show_voucher_map'  => Arr::random([true, false]),
            'show_product_map'  => Arr::random([true, false]),
            'announcement'      => $announcements,
        ];
    }

    /**
     * @return string[]
     * @throws \Throwable
     */
    protected function makeImplementationPageRequestBody(): array
    {
        $implementation = $this->findImplementation($this->implementationName);

        $existingPageTypes  = $implementation->pages()->pluck('page_type')->toArray();
        $validPageTypeKeys  = array_diff(
            collect(ImplementationPage::PAGE_TYPES)->pluck('key')->toArray(), $existingPageTypes
        );

        $blocks = [];
        $nrBlocks = rand(1, 5);

        for ($i = 0; $i < $nrBlocks; ++$i) {
            $blocks[] = $this->generatePageBlockData();
        }

        $descriptionMedia = $this->uploadMedia('cms_media');

        return [
            'state'                 => ImplementationPage::STATE_DRAFT,
            'page_type'             => Arr::random($validPageTypeKeys),
            'description'           => $this->generateMarkdownDescription($descriptionMedia),
            'description_position'  => Arr::random(ImplementationPage::DESCRIPTION_POSITIONS),
            'description_alignment' => Arr::random(['left', 'center', 'right']),
            'external'              => Arr::random([true, false]),
            'external_url'          => $this->faker->url,
            'blocks'                => $blocks,
        ];
    }
}
