<?php

namespace Tests\Feature\Cms;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockDeleteService;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockSyncService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\MediaService\Models\Media;
use Exception;
use RuntimeException;
use Tests\Feature\Cms\Concerns\InteractsWithImplementationCmsBlocks;
use Throwable;

class ImplementationPageCmsBlockTest extends ImplementationCmsTestCase
{
    use InteractsWithImplementationCmsBlocks;

    /**
     * @return void
     */
    public function testStoreImplementationPageRollsBackWhenCmsBlockSyncFails(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageCount = $implementation->pages()->count();

        $this->bindFailingCmsBlockSyncService();
        $this->withoutExceptionHandling();

        $this->assertThrows(
            fn () => $this->postJson(
                $this->getUrlPages($implementation),
                $this->makeCmsPageData(),
                $this->makeApiHeaders($proxy),
            ),
            RuntimeException::class,
            'Forced CMS block synchronization failure.',
        );

        $this->assertSame($pageCount, $implementation->pages()->count());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationPageRollsBackWhenCmsBlockSyncFails(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $page = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_HOME);
        $page->update(['title' => 'Original title']);

        $this->bindFailingCmsBlockSyncService();
        $this->withoutExceptionHandling();

        $this->assertThrows(
            fn () => $this->patchJson(
                $this->getUrlPages($implementation, $page),
                [
                    'title' => 'Changed title',
                    'external' => false,
                    'external_url' => null,
                    'cms_blocks' => [],
                ],
                $this->makeApiHeaders($proxy),
            ),
            RuntimeException::class,
            'Forced CMS block synchronization failure.',
        );

        $this->assertSame('Original title', $page->refresh()->title);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFailedPageUpdateRestoresCmsBlockAndItsMedia(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $media = $this->makeMedia('implementation_block_media');
        $media->forceFill(['identity_address' => $organization->identity_address])->save();

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $this->makeCmsBannerBlocksPayload($media->uid),
        ]), $this->makeApiHeaders($proxy))->assertSuccessful();

        $page = ImplementationPage::findOrFail($response->json('data.id'));
        $block = $page->cms_blocks()->firstOrFail();
        $media->load('presets');

        $this->bindFailingCmsBlockSyncServiceAfterSync();
        $this->withoutExceptionHandling();

        $this->assertThrows(
            fn () => $this->patchJson(
                $this->getUrlPages($implementation, $page),
                [
                    'external' => false,
                    'external_url' => null,
                    'cms_blocks' => [],
                ],
                $this->makeApiHeaders($proxy),
            ),
            RuntimeException::class,
            'Forced failure after CMS block synchronization.',
        );

        $this->assertDatabaseHas('implementation_cms_blocks', ['id' => $block->id]);
        $this->assertDatabaseHas('media', ['id' => $media->id]);

        $media->presets->each(function ($preset) {
            $this->assertDatabaseHas('media_presets', ['id' => $preset->id]);
            $this->assertTrue($preset->fileExists());
        });
    }

    /**
     * @return void
     */
    public function testStoreAndUpdateSynchronizeCmsBlockValuesAndItems(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);
        $createBlocks = $this->makeCmsInfoBlocksPayload();
        $createBlocks[0]['state'] = ImplementationCmsBlock::STATE_DRAFT;

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $createBlocks,
        ]), $headers);
        $response->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $block = $response->json('data.cms_blocks.0');
        $items = $response->json('data.cms_blocks.0.items');

        $this->assertSame(InfoCmsBlockConfig::KEY, $block['block_type_key']);
        $this->assertSame(ImplementationCmsBlock::STATE_DRAFT, $block['state']);
        $this->assertSame('Section title', $block['values']['section_title']);
        $this->assertSame('<p>Section description</p>' . "\n", $block['values_html']['section_description']);
        $this->assertSame([], $response->json('data.blocks'));
        $this->assertSame(1, $implementationPage->cms_blocks()->count());
        $cmsBlock = $implementationPage->cms_blocks()->first();
        $this->assertSame(2, $cmsBlock->items()->count());

        $updateBlocks = [[
            'id' => $block['id'],
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
            'values' => [
                'section_title' => 'Updated section title',
                'blocks_per_row' => 2,
            ],
            'items' => [[
                'id' => $items[1]['id'],
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'title' => 'Updated second post',
                    'description' => 'Updated second description',
                    'button_enabled' => false,
                ],
            ]],
        ]];

        $response = $this->patchJson($this->getUrlPages($implementation, $implementationPage), [
            'external' => false,
            'external_url' => null,
            'cms_blocks' => $updateBlocks,
        ], $headers);

        $response->assertSuccessful();
        $this->assertSame(ImplementationCmsBlock::STATE_PUBLIC, $response->json('data.cms_blocks.0.state'));
        $this->assertSame('Updated section title', $response->json('data.cms_blocks.0.values.section_title'));
        $this->assertSame(1, count($response->json('data.cms_blocks.0.items')));
        $this->assertSame('Updated second post', $response->json('data.cms_blocks.0.items.0.values.title'));
        $cmsBlock = $implementationPage->refresh()->cms_blocks()->first();
        $this->assertSame(1, $cmsBlock->items()->count());
    }

    /**
     * @return void
     */
    public function testLegacyAndCmsBlocksCanBeUpdatedAndClearedIndependently(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'blocks' => [$this->makeStaticPageBlockData()],
        ]), $headers);
        $response->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $url = $this->getUrlPages($implementation, $implementationPage);

        $this->patchJson($url, [
            'external' => false,
            'external_url' => null,
            'description' => 'Updated without block payloads',
        ], $headers)->assertSuccessful();

        $implementationPage->refresh();
        $this->assertSame(1, $implementationPage->cms_blocks()->count());
        $this->assertSame(1, $implementationPage->blocks()->count());

        $this->patchJson($url, [
            'external' => false,
            'external_url' => null,
            'cms_blocks' => [],
        ], $headers)->assertSuccessful();

        $implementationPage->refresh();
        $this->assertSame(0, $implementationPage->cms_blocks()->count());
        $this->assertSame(1, $implementationPage->blocks()->count());

        $this->patchJson($url, [
            'external' => false,
            'external_url' => null,
            'cms_blocks' => $this->makeCmsInfoBlocksPayload(),
        ], $headers)->assertSuccessful();

        $implementationPage->refresh();
        $this->assertSame(1, $implementationPage->cms_blocks()->count());
        $this->assertSame(1, $implementationPage->blocks()->count());

        $this->patchJson($url, [
            'external' => false,
            'external_url' => null,
            'blocks' => [],
        ], $headers)->assertSuccessful();

        $implementationPage->refresh();
        $this->assertSame(1, $implementationPage->cms_blocks()->count());
        $this->assertSame(0, $implementationPage->blocks()->count());
    }

    /**
     * @return void
     */
    public function testStoresMultipleCmsBlocksOfTheSameTypeInSubmissionOrder(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[] = $this->makeCmsInfoBlocksPayload()[0];
        $blocks[1]['values']['section_title'] = 'Second section title';

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $blocks,
        ]), $this->makeApiHeaders($proxy));
        $response->assertSuccessful();

        $this->assertSame([0, 1], array_column($response->json('data.cms_blocks'), 'order'));
        $this->assertSame('Section title', $response->json('data.cms_blocks.0.values.section_title'));
        $this->assertSame('Second section title', $response->json('data.cms_blocks.1.values.section_title'));
        $this->assertSame(
            [InfoCmsBlockConfig::KEY, InfoCmsBlockConfig::KEY],
            array_column($response->json('data.cms_blocks'), 'block_type_key'),
        );
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testPageResourceReturnsCmsBlockSourceHtmlAndResolvedMedia(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $itemMedia = $this->makeMedia('implementation_block_media');
        $blockMedia = $this->makeMedia('implementation_block_media');

        $itemMedia->forceFill(['identity_address' => $organization->identity_address])->save();
        $blockMedia->forceFill(['identity_address' => $organization->identity_address])->save();

        $blocks = $this->makeCmsInfoBlocksPayload();
        $blocks[0]['items'][0]['values']['media'] = $itemMedia->uid;
        $blocks[] = $this->makeCmsBannerBlocksPayload($blockMedia->uid)[0];

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'cms_blocks' => $blocks,
        ]), $this->makeApiHeaders($proxy));
        $response->assertSuccessful();

        $this->assertSame('Section description', $response->json('data.cms_blocks.0.values.section_description'));
        $this->assertSame(
            '<p>Section description</p>' . "\n",
            $response->json('data.cms_blocks.0.values_html.section_description'),
        );
        $this->assertSame($itemMedia->uid, $response->json('data.cms_blocks.0.items.0.values.media'));
        $this->assertSame('<p>First description</p>' . "\n", $response->json(
            'data.cms_blocks.0.items.0.values_html.description',
        ));
        $this->assertSame($itemMedia->uid, $response->json('data.cms_blocks.0.items.0.media.media.uid'));
        $this->assertSame($blockMedia->uid, $response->json('data.cms_blocks.1.values.image'));
        $this->assertSame($blockMedia->uid, $response->json('data.cms_blocks.1.media.image.uid'));
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testUserWithCmsPermissionCanDeleteMediaAttachedToCmsBlockAndItemValues(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $unrelatedIdentity = $this->makeIdentity();
        $medias = $this->makeCmsBlockAndItemMedia($implementation);

        foreach ($medias as $media) {
            $media->forceFill(['identity_address' => $unrelatedIdentity->address])->save();

            $this->deleteJson("$this->apiMediaUrl/$media->uid", [], $this->makeApiHeaders($proxy))
                ->assertSuccessful();

            $this->assertDatabaseMissing('media', ['id' => $media->id]);
        }
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testUserWithoutCmsPermissionCannotDeleteMediaAttachedToCmsBlockAndItemValues(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $identity = $this->makeIdentity();
        $organization->addEmployee($identity);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));
        $medias = $this->makeCmsBlockAndItemMedia($implementation);

        foreach ($medias as $media) {
            $media->forceFill(['identity_address' => $identity->address])->save();

            $this->deleteJson("$this->apiMediaUrl/$media->uid", [], $headers)->assertForbidden();
            $this->assertDatabaseHas('media', ['id' => $media->id]);
        }
    }

    /**
     * @return void
     */
    public function testPublicConfigReturnsCmsAndLegacyBlocksSeparately(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'state' => ImplementationPage::STATE_PUBLIC,
            'blocks' => [$this->makeStaticPageBlockData()],
        ]), $headers);
        $response->assertSuccessful();
        $page = ImplementationPage::find($response->json('data.id'));

        $this->postJson($this->getUrlPages($implementation), $this->makeCmsPageData([
            'page_type' => ImplementationPage::TYPE_EXPLANATION,
            'state' => ImplementationPage::STATE_DRAFT,
        ]), $headers)->assertSuccessful();

        $response = $this->getJson($this->getUrlPlatformConfig(), [
            'Client-Type' => 'webshop',
            'Client-Key' => $implementation->key,
        ]);

        $response->assertSuccessful();
        $this->assertSame(InfoCmsBlockConfig::KEY, $response->json('pages.home.cms_blocks.0.block_type_key'));
        $this->assertSame('Page block title', $response->json('pages.home.blocks.0.title'));
        $this->assertNull($response->json('pages.explanation'));

        $cmsBlocks = $this->makeCmsInfoBlocksPayload();
        $cmsBlocks[0]['id'] = $page->cms_blocks()->first()->id;
        $cmsBlocks[0]['state'] = ImplementationCmsBlock::STATE_DRAFT;

        $this->patchJson($this->getUrlPages($implementation, $page), [
            'external' => false,
            'external_url' => null,
            'cms_blocks' => $cmsBlocks,
        ], $headers)->assertSuccessful();

        $response = $this->getJson($this->getUrlPlatformConfig(), [
            'Client-Type' => 'webshop',
            'Client-Key' => $implementation->key,
        ]);

        $response->assertSuccessful();
        $this->assertSame([], $response->json('pages.home.cms_blocks'));
        $this->assertSame('Page block title', $response->json('pages.home.blocks.0.title'));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPageDeletionRequiresAuthorizationAndRemovesCmsBlockHierarchy(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $proxyHeaders = $this->makeApiHeaders($proxy);

        $pageBody = $this->makeCmsPageData();
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $proxyHeaders);

        $response->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $cmsBlock = $implementationPage->cms_blocks()->first();
        $cmsBlockItemIds = $cmsBlock->items()->pluck('id')->all();
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);

        $this->deleteJson($implementationPageUrl)->assertUnauthorized();
        $this->deleteJson($implementationPageUrl, [], $proxyHeaders)->assertSuccessful();
        $this->assertNull(ImplementationPage::find($response->json('data.id')));
        $this->assertDatabaseMissing('implementation_cms_blocks', ['id' => $cmsBlock->id]);

        $this->assertDatabaseMissing('implementation_cms_block_values', [
            'implementation_cms_block_id' => $cmsBlock->id,
        ]);

        foreach ($cmsBlockItemIds as $cmsBlockItemId) {
            $this->assertDatabaseMissing('implementation_cms_block_items', ['id' => $cmsBlockItemId]);
            $this->assertDatabaseMissing('implementation_cms_block_item_values', [
                'implementation_cms_block_item_id' => $cmsBlockItemId,
            ]);
        }
    }

    /**
     * @return void
     */
    public function testFailedPageDeletionRestoresPageAndCmsBlocks(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $proxyHeaders = $this->makeApiHeaders($proxy);

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            $this->makeCmsPageData(),
            $proxyHeaders,
        )->assertSuccessful();

        $page = ImplementationPage::findOrFail($response->json('data.id'));
        $block = $page->cms_blocks()->firstOrFail();

        $this->bindFailingCmsBlockSyncServiceAfterSync();
        $this->withoutExceptionHandling();

        $this->assertThrows(
            fn () => $this->deleteJson($this->getUrlPages($implementation, $page), [], $proxyHeaders),
            RuntimeException::class,
            'Forced failure after CMS block synchronization.',
        );

        $this->assertDatabaseHas('implementation_pages', ['id' => $page->id]);
        $this->assertDatabaseHas('implementation_cms_blocks', ['id' => $block->id]);
    }

    /**
     * @param array $replace
     * @return array
     */
    protected function makeStaticPageBlockData(array $replace = []): array
    {
        return [
            'button_enabled' => true,
            'button_link' => '/aanbod',
            'button_link_label' => 'Open offer',
            'button_target_blank' => false,
            'button_text' => 'Open',
            'description' => 'Page block description',
            'label' => 'Page block label',
            'title' => 'Page block title',
            ...$replace,
        ];
    }

    /**
     * @return string
     */
    protected function getUrlPlatformConfig(): string
    {
        return '/api/v1/platform/config/webshop';
    }

    /**
     * @param Implementation $implementation
     * @throws Exception
     * @return Media[]
     */
    protected function makeCmsBlockAndItemMedia(Implementation $implementation): array
    {
        $page = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_HOME);

        $block = $page->cms_blocks()->create([
            'block_type_key' => BannerCmsBlockConfig::KEY,
            'order' => 0,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);

        $blockValue = $block->values()->create([
            'field_key' => 'image',
            'value' => null,
        ]);

        $item = $block->items()->create([
            'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
            'order' => 0,
        ]);

        $itemValue = $item->values()->create([
            'field_key' => 'media',
            'value' => null,
        ]);

        $blockMedia = $this->makeMedia('implementation_block_media');
        $itemMedia = $this->makeMedia('implementation_block_media');

        $blockValue->syncMedia([$blockMedia->uid], 'implementation_block_media');
        $itemValue->syncMedia([$itemMedia->uid], 'implementation_block_media');

        return [$blockMedia, $itemMedia];
    }

    /**
     * @return void
     */
    private function bindFailingCmsBlockSyncService(): void
    {
        $this->app->instance(
            ImplementationCmsBlockSyncService::class,
            new class (resolve(ImplementationCmsBlockDeleteService::class)) extends ImplementationCmsBlockSyncService {
                public function sync(ImplementationPage $page, ?array $blocks): void
                {
                    throw new RuntimeException('Forced CMS block synchronization failure.');
                }
            },
        );
    }

    /**
     * @return void
     */
    private function bindFailingCmsBlockSyncServiceAfterSync(): void
    {
        $this->app->instance(
            ImplementationCmsBlockSyncService::class,
            new class (resolve(ImplementationCmsBlockDeleteService::class)) extends ImplementationCmsBlockSyncService {
                public function sync(ImplementationPage $page, ?array $blocks): void
                {
                    parent::sync($page, $blocks);

                    throw new RuntimeException('Forced failure after CMS block synchronization.');
                }
            },
        );
    }
}
