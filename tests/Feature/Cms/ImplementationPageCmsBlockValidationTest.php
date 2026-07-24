<?php

namespace Tests\Feature\Cms;

use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\CalloutCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\FaqCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\LinkPanelsCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProductCategoriesCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProductShowcaseCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProviderSignUpCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProvidersMapCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\TextCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Support\MarkdownParser;
use Exception;
use League\CommonMark\Exception\CommonMarkException;
use Tests\Feature\Cms\Concerns\InteractsWithImplementationCmsBlocks;

class ImplementationPageCmsBlockValidationTest extends ImplementationCmsTestCase
{
    use InteractsWithImplementationCmsBlocks;

    /**
     * @throws CommonMarkException
     * @return void
     */
    public function testCmsBlockConfigEndpointReturnsPageSpecificConfigsAndFieldMetadata(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->getJson(
            $this->getUrlPageCmsBlockConfigs($implementation, ImplementationPage::TYPE_HOME),
            $this->makeApiHeaders($proxy),
        );

        $response->assertSuccessful();

        $this->assertSame([
            InfoCmsBlockConfig::KEY,
            TextCmsBlockConfig::KEY,
            BannerCmsBlockConfig::KEY,
            CalloutCmsBlockConfig::KEY,
            FaqCmsBlockConfig::KEY,
            LinkPanelsCmsBlockConfig::KEY,
            ProvidersMapCmsBlockConfig::KEY,
            ProductCategoriesCmsBlockConfig::KEY,
            ProductShowcaseCmsBlockConfig::KEY,
        ], array_column($response->json('data'), 'key'));

        $configs = collect($response->json('data'))->keyBy('key');
        $infoConfig = $configs->get(InfoCmsBlockConfig::KEY);

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'blocks_per_row',
        ], array_column($infoConfig['fields'], 'key'));

        $this->assertSame(InfoCmsBlockConfig::ITEM_TYPE_POST, $infoConfig['item_types'][0]['key']);
        $this->assertSame([
            'media',
            'label',
            'title',
            'description',
            'button_enabled',
            'button_text',
            'button_link',
            'button_link_label',
            'button_target_blank',
        ], array_column($infoConfig['item_types'][0]['fields'], 'key'));

        $response = $this->getJson(
            $this->getUrlPageCmsBlockConfigs($implementation, ImplementationPage::TYPE_PROVIDER),
            $this->makeApiHeaders($proxy),
        );

        $response->assertSuccessful();
        $configs = collect($response->json('data'))->keyBy('key');

        $providerSignUpConfig = new ProviderSignUpCmsBlockConfig();
        $providerSignUpDescription = $providerSignUpConfig->field('section_description');
        $providerSignUpFields = collect($configs->get(ProviderSignUpCmsBlockConfig::KEY)['fields'])->keyBy('key');

        $this->assertSame(
            resolve(MarkdownParser::class)->toHtml($providerSignUpDescription['default']),
            $providerSignUpFields->get('section_description')['default_html'],
        );

        $response = $this->getJson(
            $this->getUrlPageCmsBlockConfigs($implementation, ImplementationPage::TYPE_PRODUCTS),
            $this->makeApiHeaders($proxy),
        );

        $response->assertSuccessful();
        $this->assertSame([], $response->json('data'));
    }

    /**
     * @return void
     */
    public function testImplementationPageCmsEndpointsRequireCmsPermission(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $headers = $this->makeRestrictedCmsHeaders($organization);
        $page = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_ACCESSIBILITY);
        $pageCount = $implementation->pages()->count();
        $cmsBlockCount = $page->cms_blocks()->count();

        $this->getJson($this->getUrlPageCmsBlockConfigs($implementation), $headers)->assertForbidden();

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $this->makeCmsInfoBlocksPayload(),
        ], $headers)->assertForbidden();

        $this->postJson(
            $this->getUrlPages($implementation),
            $this->makeCmsPageData(['page_type' => ImplementationPage::TYPE_PRIVACY]),
            $headers,
        )->assertForbidden();

        $this->patchJson($this->getUrlPages($implementation, $page), [
            'external' => false,
            'external_url' => null,
            'cms_blocks' => $this->makeCmsInfoBlocksPayload(),
        ], $headers)->assertForbidden();

        $this->assertSame($pageCount, $implementation->pages()->count());
        $this->assertSame($cmsBlockCount, $page->cms_blocks()->count());
    }

    /**
     * @return void
     */
    public function testCmsBlockValidationAcceptsKnownFieldsAndRejectsUnknownFields(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $this->makeCmsInfoBlocksPayload(),
        ], $headers)->assertSuccessful();

        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['values']['unknown'] = 'Unknown';

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $blocks,
        ], $headers)->assertJsonValidationErrors('cms_blocks.0.values.unknown');

        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['values']['unknown.key'] = 'Unknown';

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $blocks,
        ], $headers)->assertJsonValidationErrors('cms_blocks.0.values');
    }

    /**
     * @return void
     */
    public function testPageStoreRejectsInvalidCmsBlocksWithoutCreatingPage(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageCount = $implementation->pages()->count();
        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['values']['unknown'] = 'Unknown';

        $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $blocks,
        ]), $this->makeApiHeaders($proxy))->assertJsonValidationErrors('cms_blocks.0.values.unknown');

        $this->assertSame($pageCount, $implementation->pages()->count());
    }

    /**
     * @return void
     */
    public function testPageUpdateRejectsInvalidCmsBlocksWithoutChangingPageOrBlocks(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            $this->makeCmsPageData(),
            $headers,
        )->assertSuccessful();

        $page = ImplementationPage::findOrFail($response->json('data.id'));
        $page->update(['title' => 'Original title']);
        $cmsBlock = $page->cms_blocks()->firstOrFail();
        $itemIds = $cmsBlock->items()->pluck('id')->all();
        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['id'] = $cmsBlock->id;

        foreach ($blocks[0]['items'] as $index => &$item) {
            $item['id'] = $itemIds[$index];
        }

        unset($item);

        $blocks[0]['values']['unknown'] = 'Unknown';

        $this->patchJson($this->getUrlPages($implementation, $page), [
            'title' => 'Changed title',
            'external' => false,
            'external_url' => null,
            'cms_blocks' => $blocks,
        ], $headers)->assertJsonValidationErrors('cms_blocks.0.values.unknown');

        $this->assertSame('Original title', $page->refresh()->title);
        $this->assertSame([$cmsBlock->id], $page->cms_blocks()->pluck('id')->all());
        $this->assertSame($itemIds, $cmsBlock->items()->pluck('id')->all());
        $this->assertDatabaseHas('implementation_cms_block_values', [
            'implementation_cms_block_id' => $cmsBlock->id,
            'field_key' => 'section_title',
            'value' => 'Section title',
        ]);
        $this->assertDatabaseMissing('implementation_cms_block_values', [
            'implementation_cms_block_id' => $cmsBlock->id,
            'field_key' => 'unknown',
        ]);
    }

    /**
     * @return void
     */
    public function testValidateImplementationPageCmsBlocksRejectsDeletedPageId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $page = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_HOME);

        $page->delete();

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'implementation_page_id' => $page->id,
            'cms_blocks' => 'invalid',
        ], $this->makeApiHeaders($proxy))->assertJsonValidationErrors('implementation_page_id');
    }

    /**
     * @return void
     */
    public function testCmsBlockValidationRejectsDuplicateBlockAndItemIds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $pageResponse = $this->postJson(
            $this->getUrlPages($implementation),
            $this->makeCmsPageData(),
            $headers,
        );

        $pageResponse->assertSuccessful();

        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['id'] = $pageResponse->json('data.cms_blocks.0.id');

        foreach ($blocks[0]['items'] as $index => &$item) {
            $item['id'] = $pageResponse->json("data.cms_blocks.0.items.$index.id");
        }

        unset($item);

        $duplicateBlock = $blocks[0];
        $duplicateBlock['items'] = [];

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'implementation_page_id' => $pageResponse->json('data.id'),
            'cms_blocks' => [$duplicateBlock, $duplicateBlock],
        ], $headers)->assertJsonValidationErrors([
            'cms_blocks.0.id',
            'cms_blocks.1.id',
        ]);

        $duplicateItem = $blocks[0]['items'][0];
        $blocks[0]['items'] = [$duplicateItem, $duplicateItem];

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'implementation_page_id' => $pageResponse->json('data.id'),
            'cms_blocks' => $blocks,
        ], $headers)->assertJsonValidationErrors([
            'cms_blocks.0.items.0.id',
            'cms_blocks.0.items.1.id',
        ]);
    }

    /**
     * @return void
     */
    public function testConditionalCmsBlockFieldsAreValidatedOnlyWhenActiveAndInactiveValuesAreNotStored(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $calloutBlocks = [[
            'block_type_key' => CalloutCmsBlockConfig::KEY,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
            'values' => [
                'button_enabled' => false,
                'button_text' => str_repeat('x', 201),
                'button_link' => 'invalid-url',
                'button_target_blank' => 'invalid-boolean',
            ],
            'items' => [],
        ]];

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $calloutBlocks,
        ], $headers)->assertSuccessful();

        $activeCalloutBlocks = $calloutBlocks;
        $activeCalloutBlocks[0]['values']['button_enabled'] = true;

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $activeCalloutBlocks,
        ], $headers)->assertJsonValidationErrors([
            'cms_blocks.0.values.button_text',
            'cms_blocks.0.values.button_link',
            'cms_blocks.0.values.button_target_blank',
        ]);

        $linkPanelBlocks = [[
            'block_type_key' => LinkPanelsCmsBlockConfig::KEY,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
            'values' => [
                'columns' => LinkPanelsCmsBlockConfig::COLUMNS_TWO,
            ],
            'items' => [[
                'item_type_key' => LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL,
                'values' => [
                    'title' => 'Panel title',
                    'button_text' => '',
                    'button_link' => 'invalid-url',
                    'button_target_blank' => 'invalid-boolean',
                ],
            ]],
        ]];

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $linkPanelBlocks,
        ], $headers)->assertSuccessful();

        $activeLinkPanelBlocks = $linkPanelBlocks;
        $activeLinkPanelBlocks[0]['items'][0]['values']['button_text'] = 'Open panel';

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $activeLinkPanelBlocks,
        ], $headers)->assertJsonValidationErrors([
            'cms_blocks.0.items.0.values.button_link',
            'cms_blocks.0.items.0.values.button_target_blank',
        ]);

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $calloutBlocks,
        ]), $headers);

        $response->assertSuccessful();
        $this->assertNull($response->json('data.cms_blocks.0.values.button_link'));

        $cmsBlock = ImplementationCmsBlock::findOrFail($response->json('data.cms_blocks.0.id'));

        $this->assertSame(['button_enabled'], $cmsBlock->values()->pluck('field_key')->all());
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testCmsBlockValidationUsesLocalizedFieldLabelsInErrors(): void
    {
        app()->setLocale('nl');

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $media = $this->makeMedia('implementation_block_media');
        $blocks = [
            $this->makeCmsBannerBlocksPayload($media->uid)[0],
            $this->makeCmsBannerBlocksPayload($media->uid)[0],
        ];

        unset($blocks[1]['values']['image']);

        $response = $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'page_type' => ImplementationPage::TYPE_HOME,
            'cms_blocks' => $blocks,
        ], $this->makeApiHeaders($proxy));

        $response->assertJsonValidationErrors('cms_blocks.1.values.image');
        $this->assertSame(
            'Het afbeelding veld is verplicht.',
            $response->json('errors')['cms_blocks.1.values.image'][0] ?? null,
        );
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testPageStoreRejectsCmsBlockMediaOwnedByAnotherIdentity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $media = $this->makeMedia('implementation_block_media');
        $media->forceFill(['identity_address' => $this->makeIdentity()->address])->save();
        $pageCount = $implementation->pages()->count();

        $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $this->makeCmsBannerBlocksPayload($media->uid),
        ]), $this->makeApiHeaders($proxy))->assertJsonValidationErrors('cms_blocks.0.values.image');

        $this->assertSame($pageCount, $implementation->pages()->count());
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    /**
     * @return void
     */
    public function testCmsBlockValidationRejectsBlockIdOwnedByAnotherPage(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $page = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_HOME);
        $otherPage = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_PRIVACY);

        $otherBlock = $otherPage->cms_blocks()->create([
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'order' => 0,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);

        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['id'] = $otherBlock->id;

        $this->postJson($this->getUrlPageCmsBlocksValidate($implementation), [
            'implementation_page_id' => $page->id,
            'cms_blocks' => $blocks,
        ], $this->makeApiHeaders($proxy))->assertJsonValidationErrors('cms_blocks.0.id');
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function makeRestrictedCmsHeaders(Organization $organization): array
    {
        $identity = $this->makeIdentity();
        $organization->addEmployee($identity);

        return $this->makeApiHeaders($this->makeIdentityProxy($identity));
    }
}
