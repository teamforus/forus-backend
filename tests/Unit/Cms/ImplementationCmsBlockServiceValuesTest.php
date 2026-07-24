<?php

namespace Tests\Unit\Cms;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Uid\UuidV4;
use Tests\TestCase;
use Tests\Traits\MakesCmsMedia;
use Tests\Traits\MakesTestOrganizations;

class ImplementationCmsBlockServiceValuesTest extends TestCase
{
    use DatabaseTransactions;
    use MakesCmsMedia;
    use MakesTestOrganizations;

    /**
     * @throws Exception
     * @return void
     */
    public function testResolvesConfiguredValuesMarkdownMediaAndTranslations(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $implementation = $this->makeTestImplementation($organization);
        $block = $this->createCmsBlock($implementation, InfoCmsBlockConfig::KEY);

        $title = $block->values()->create([
            'field_key' => 'section_title',
            'value' => 'Section title',
        ]);
        $description = $block->values()->create([
            'field_key' => 'section_description',
            'value' => '**Section description**',
        ]);
        $backgroundColor = $block->values()->create([
            'field_key' => 'section_background_color',
            'value' => '#123456',
        ]);
        $block->values()->create([
            'field_key' => 'blocks_per_row',
            'value' => '2',
        ]);

        $item = $block->items()->create([
            'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
            'order' => 0,
        ]);
        $media = $this->makeMedia('implementation_block_media', $identity);
        $mediaValue = $item->values()->create([
            'field_key' => 'media',
            'value' => $media->uid,
        ]);
        $itemTitle = $item->values()->create([
            'field_key' => 'title',
            'value' => 'Post title',
        ]);
        $itemDescription = $item->values()->create([
            'field_key' => 'description',
            'value' => '**Post description**',
        ]);
        $item->values()->create([
            'field_key' => 'button_enabled',
            'value' => '1',
        ]);

        $mediaValue->syncMedia([$media->uid], 'implementation_block_media');
        $this->seedTranslation($title, 'Section title', 'Translated section title');
        $this->seedTranslation($description, '**Section description**', 'Translated section description');
        $this->seedTranslation($backgroundColor, '#123456', '#abcdef');
        $this->seedTranslation($itemTitle, 'Post title', 'Translated post title');
        $this->seedTranslation($itemDescription, '**Post description**', 'Translated post description');
        $this->setWebshopRequest($implementation);

        $block->load([
            'values.translation_values',
            'items.values.translation_values',
            'items.values.medias.presets',
        ]);

        $cmsBlockService = resolve(ImplementationCmsBlockService::class);
        $item = $block->items->first();

        $this->assertSame([
            'section_title' => 'Translated section title',
            'section_description' => 'Translated section description',
            'section_background_color' => '#123456',
            'section_spacing' => null,
            'blocks_per_row' => '2',
        ], $cmsBlockService->resolveBlockValues($block));
        $this->assertSame([
            'section_description' => '<p>Translated section description</p>' . "\n",
        ], $cmsBlockService->resolveBlockValuesHtml($block));
        $this->assertSame([
            'media' => $media->uid,
            'label' => null,
            'title' => 'Translated post title',
            'description' => 'Translated post description',
            'button_enabled' => '1',
            'button_text' => null,
            'button_link' => null,
            'button_link_label' => null,
            'button_target_blank' => null,
        ], $cmsBlockService->resolveItemValues($item));
        $this->assertSame([
            'description' => '<p>Translated post description</p>' . "\n",
        ], $cmsBlockService->resolveItemValuesHtml($item));
        $this->assertSame($media->id, $cmsBlockService->resolveItemMedia($item)['media']?->id);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testResolvesConfiguredBlockMedia(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $implementation = $this->makeTestImplementation($organization);
        $block = $this->createCmsBlock($implementation, BannerCmsBlockConfig::KEY);
        $media = $this->makeMedia('implementation_block_media', $identity);
        $imageValue = $block->values()->create([
            'field_key' => 'image',
            'value' => $media->uid,
        ]);

        $imageValue->syncMedia([$media->uid], 'implementation_block_media');
        $block->load('values.medias.presets');

        $cmsBlockService = resolve(ImplementationCmsBlockService::class);

        $this->assertSame($media->uid, $cmsBlockService->resolveBlockValues($block)['image']);
        $this->assertSame($media->id, $cmsBlockService->resolveBlockMedia($block)['image']?->id);
    }

    /**
     * @param ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value
     * @param string $from
     * @param string $to
     * @return void
     */
    protected function seedTranslation(
        ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value,
        string $from,
        string $to,
    ): void {
        $value->translation_values()->create([
            'key' => 'value',
            'from' => $from,
            'from_length' => mb_strlen($from),
            'to' => $to,
            'to_length' => mb_strlen($to),
            'locale' => 'en-US',
        ]);
    }

    /**
     * @param Implementation $implementation
     * @return void
     */
    protected function setWebshopRequest(Implementation $implementation): void
    {
        Lang::setLocale('en');

        $request = BaseFormRequest::create('/');
        $request->headers->set('Client-Key', $implementation->key);
        $request->headers->set('Client-Type', Implementation::FRONTEND_WEBSHOP);
        $this->app->instance('request', $request);
    }

    /**
     * @param Implementation $implementation
     * @param string $blockTypeKey
     * @return ImplementationCmsBlock
     */
    protected function createCmsBlock(
        Implementation $implementation,
        string $blockTypeKey,
    ): ImplementationCmsBlock {
        $page = $implementation->pages()->create([
            'page_type' => ImplementationPage::TYPE_HOME,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);

        return $page->cms_blocks()->create([
            'block_type_key' => $blockTypeKey,
            'order' => 0,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);
    }

    /**
     * @param Organization $organization
     * @return Implementation
     */
    protected function makeTestImplementation(Organization $organization): Implementation
    {
        return $organization->implementations()->create([
            'key' => UuidV4::v4(),
            'name' => fake()->text(10),
        ]);
    }
}
