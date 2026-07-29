<?php

namespace Tests\Feature\Cms\Concerns;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;

trait InteractsWithImplementationCmsBlocks
{
    /**
     * @param array $replace
     * @return array
     */
    protected function makeCmsPageData(array $replace = []): array
    {
        return [
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'external_url' => null,
            'page_type' => ImplementationPage::TYPE_HOME,
            'description' => 'Page description',
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
            'cms_blocks' => $this->makeCmsInfoBlocksPayload(),
            ...$replace,
        ];
    }

    /**
     * @return array
     */
    protected function makeCmsInfoBlocksPayload(): array
    {
        return [[
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
            'values' => [
                'section_title' => 'Section title',
                'section_description' => 'Section description',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'blocks_per_row' => 3,
            ],
            'items' => [[
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'label' => 'First label',
                    'title' => 'First post',
                    'description' => 'First description',
                    'button_enabled' => true,
                    'button_text' => 'Open',
                    'button_link' => 'https://example.com/aanbod',
                    'button_link_label' => 'Open offer',
                    'button_target_blank' => false,
                ],
            ], [
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'title' => 'Second post',
                    'description' => 'Second description',
                    'button_enabled' => false,
                ],
            ]],
        ]];
    }

    /**
     * @param string $mediaUid
     * @return array
     */
    protected function makeCmsBannerBlocksPayload(string $mediaUid): array
    {
        return [[
            'block_type_key' => BannerCmsBlockConfig::KEY,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
            'values' => [
                'section_title' => 'Banner title',
                'section_description' => 'Banner description',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'image' => $mediaUid,
                'layout' => BannerCmsBlockConfig::LAYOUT_IMAGE_LEFT,
                'text_background_color' => '#f6f5ef',
                'text_color' => '#4E4D40',
                'label_enabled' => true,
                'label' => 'Uitgelicht',
                'label_background_color' => '#315EFD',
                'label_text_color' => '#ffffff',
                'url' => 'https://example.com',
                'link_label' => 'Bekijk de actie',
                'target_blank' => false,
                'button_enabled' => true,
                'link_area' => BannerCmsBlockConfig::LINK_AREA_BANNER,
                'button_label' => 'Bekijk',
                'button_color' => '#4E4D40',
                'button_text_color' => '#ffffff',
            ],
            'items' => [],
        ]];
    }

    /**
     * @param Implementation $implementation
     * @param string|null $pageType
     * @return string
     */
    protected function getUrlPageCmsBlockConfigs(Implementation $implementation, ?string $pageType = null): string
    {
        $url = sprintf(
            $this->apiUrl . '/pages/cms-block-configs',
            $implementation->organization_id,
            $implementation->id,
        );

        return $pageType ? $url . '?page_type=' . $pageType : $url;
    }

    /**
     * @param Implementation $implementation
     * @return string
     */
    protected function getUrlPageCmsBlocksValidate(Implementation $implementation): string
    {
        return sprintf(
            $this->apiUrl . '/pages/validate-cms-blocks',
            $implementation->organization_id,
            $implementation->id,
        );
    }
}
