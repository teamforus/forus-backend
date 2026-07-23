<?php

namespace Tests\Unit\Cms;

use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\CalloutCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\FaqCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\LinkPanelsCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProductCategoriesCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProductShowcaseCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProviderSignUpCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProvidersMapCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\TextCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use App\Services\CmsService\ImplementationBlocks\Validation\ImplementationCmsBlockRuleSet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\MakesCmsMedia;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

abstract class CmsBlockTestCase extends TestCase
{
    use DatabaseTransactions;
    use MakesCmsMedia;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @param string $pageType
     * @return ImplementationPage
     */
    protected function makeCmsPageAsOwner(string $pageType = ImplementationPage::TYPE_HOME): ImplementationPage
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $implementation = $this->makeTestImplementation($organization);

        $page = $implementation->pages()->create([
            'page_type' => $pageType,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);

        $proxy = $this->makeIdentityProxy($identity);
        request()->headers->set('Authorization', "Bearer $proxy->access_token");

        return $page;
    }

    /**
     * @param string|null $mediaUid
     * @return array
     */
    protected function makeValidCmsInfoBlocksPayload(?string $mediaUid = null): array
    {
        return [[
            'block_type_key' => InfoCmsBlockConfig::KEY,
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
                    'media' => $mediaUid,
                    'label' => 'Label',
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
                    'label' => null,
                    'title' => 'Second post',
                    'description' => 'Second description',
                    'button_enabled' => false,
                ],
            ]],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsTextBlocksPayload(): array
    {
        return [[
            'block_type_key' => TextCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Text section title',
                'section_description' => 'Text section description',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
            ],
            'items' => [],
        ]];
    }

    /**
     * @param string $mediaUid
     * @return array
     */
    protected function makeValidCmsBannerBlocksPayload(string $mediaUid): array
    {
        return [[
            'block_type_key' => BannerCmsBlockConfig::KEY,
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
     * @param string|null $mediaUid
     * @return array
     */
    protected function makeValidCmsCalloutBlocksPayload(?string $mediaUid = null): array
    {
        return [[
            'block_type_key' => CalloutCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Callout title',
                'section_description' => 'Callout description',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'image' => $mediaUid,
                'label' => 'Uitgelicht',
                'button_enabled' => true,
                'button_text' => 'Bekijk',
                'button_link' => 'https://example.com',
                'button_target_blank' => false,
                'content_alignment' => CalloutCmsBlockConfig::CONTENT_ALIGNMENT_LEFT,
            ],
            'items' => [],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsProvidersMapBlocksPayload(): array
    {
        return [[
            'block_type_key' => ProvidersMapCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Bekijk onze kaart',
                'section_description' => 'Vind locaties en voorzieningen bij u in de buurt.',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'button_text' => 'Toon kaart',
            ],
            'items' => [],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsProviderSignUpBlocksPayload(): array
    {
        return [[
            'block_type_key' => ProviderSignUpCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Aanmelden als aanbieder',
                'section_description' => 'Meld uw organisatie aan als aanbieder.',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'image' => null,
                'button_text' => 'Aanmelden',
                'login_enabled' => true,
                'login_text' => 'Heeft u al een account?',
                'login_link_text' => 'Log dan in',
            ],
            'items' => [],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsProductCategoriesBlocksPayload(): array
    {
        return [[
            'block_type_key' => ProductCategoriesCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Wat lijkt jou leuk om te doen?',
                'section_description' => 'Klik op een thema en je ziet meteen uit welk aanbod je kan kiezen.',
                'section_background_color' => null,
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'section_background_type' => ProductCategoriesCmsBlockConfig::BACKGROUND_TYPE_SHAPE,
                'section_background_shape_color' => '#315EFD',
            ],
            'items' => [],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsProductShowcaseBlocksPayload(): array
    {
        return [[
            'block_type_key' => ProductShowcaseCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Aanbod',
                'section_description' => 'Bekijk ons aanbod.',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'product_count' => ProductShowcaseCmsBlockConfig::PRODUCT_COUNT_SIX,
                'button_text' => 'Bekijk meer',
            ],
            'items' => [],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsLinkPanelsBlocksPayload(): array
    {
        return [[
            'block_type_key' => LinkPanelsCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Handige links',
                'section_description' => 'Bekijk de meest gebruikte regelingen.',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
                'columns' => LinkPanelsCmsBlockConfig::COLUMNS_TWO,
            ],
            'items' => [[
                'item_type_key' => LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL,
                'values' => [
                    'title' => 'Gemeente Nijmegen',
                    'description' => 'Informatie over lokale regelingen.',
                    'links' => '- [Meedoenregeling](https://example.com/fondsen/26)',
                    'button_text' => 'Meer informatie',
                    'button_link' => 'https://example.com/fondsen',
                    'button_target_blank' => false,
                ],
            ], [
                'item_type_key' => LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL,
                'values' => [
                    'title' => 'Stichting Leergeld',
                    'description' => 'Hulp voor kinderen en gezinnen.',
                    'links' => '- [Hulp voor uw kind](https://example.com/hulp)',
                    'button_text' => null,
                    'button_link' => null,
                    'button_target_blank' => null,
                ],
            ]],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsFaqBlocksPayload(): array
    {
        return [[
            'block_type_key' => FaqCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Veelgestelde vragen',
                'section_description' => 'Antwoorden op veelgestelde vragen.',
                'section_background_color' => '#ffffff',
                'section_spacing' => CmsBlockConfig::SECTION_SPACING_DEFAULT,
            ],
            'items' => [[
                'item_type_key' => FaqCmsBlockConfig::ITEM_TYPE_ITEM,
                'values' => [
                    'type' => FaqCmsBlockConfig::TYPE_TITLE,
                    'title' => 'Algemeen',
                    'subtitle' => 'Vragen over de regeling.',
                    'description' => null,
                ],
            ], [
                'item_type_key' => FaqCmsBlockConfig::ITEM_TYPE_ITEM,
                'values' => [
                    'type' => FaqCmsBlockConfig::TYPE_QUESTION,
                    'title' => 'Hoe werkt het?',
                    'subtitle' => null,
                    'description' => 'U kiest een aanbod en betaalt met uw tegoed.',
                ],
            ], [
                'item_type_key' => FaqCmsBlockConfig::ITEM_TYPE_ITEM,
                'values' => [
                    'type' => FaqCmsBlockConfig::TYPE_QUESTION,
                    'title' => 'Waar kan ik mijn tegoed gebruiken?',
                    'subtitle' => null,
                    'description' => 'Bij aangesloten aanbieders.',
                ],
            ]],
        ]];
    }

    /**
     * @param ImplementationPage $page
     * @param string $blockTypeKey
     * @return ImplementationCmsBlock
     */
    protected function createCmsBlock(
        ImplementationPage $page,
        string $blockTypeKey = InfoCmsBlockConfig::KEY,
    ): ImplementationCmsBlock {
        return $page->cms_blocks()->create([
            'block_type_key' => $blockTypeKey,
            'order' => 0,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);
    }

    /**
     * @param ImplementationCmsBlock $block
     * @return ImplementationCmsBlockItem
     */
    protected function createCmsItem(ImplementationCmsBlock $block): ImplementationCmsBlockItem
    {
        return $block->items()->create([
            'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
            'order' => 0,
        ]);
    }

    /**
     * @param ImplementationPage|null $page
     * @param string|null $pageType
     * @param array|null $blocks
     * @return void
     */
    protected function assertBlocksValid(
        ?ImplementationPage $page,
        ?string $pageType,
        ?array $blocks,
    ): void {
        $validator = Validator::make([
            'cms_blocks' => $blocks,
        ], ImplementationCmsBlockRuleSet::rules($page, $pageType, $blocks));

        $this->assertSame([], $validator->errors()->toArray());
    }

    /**
     * @param ImplementationPage|null $page
     * @param string|null $pageType
     * @param array|null $blocks
     * @throws ValidationException
     * @return void
     */
    protected function validateBlocks(
        ?ImplementationPage $page,
        ?string $pageType,
        ?array $blocks,
    ): void {
        $validator = Validator::make([
            'cms_blocks' => $blocks,
        ], ImplementationCmsBlockRuleSet::rules($page, $pageType, $blocks));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @param callable $callback
     * @param string[] $keys
     * @return void
     */
    protected function assertValidationErrors(callable $callback, array $keys): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $exception->errors());
            }

            return;
        }

        $this->fail('Expected validation exception was not thrown.');
    }

    /**
     * @param CmsBlockConfig $config
     * @param string $descriptionType
     * @param int $descriptionMax
     * @param string|null $descriptionControl
     * @return void
     */
    protected function assertSectionFields(
        CmsBlockConfig $config,
        string $descriptionType = CmsBlockConfig::TYPE_MARKDOWN,
        int $descriptionMax = 1000,
        ?string $descriptionControl = null,
    ): void {
        $sectionTitle = $config->field('section_title');
        $sectionDescription = $config->field('section_description');
        $sectionBackgroundColor = $config->field('section_background_color');
        $sectionSpacing = $config->field('section_spacing');

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionTitle['type']);
        $this->assertFalse($sectionTitle['required']);
        $this->assertSame(100, $sectionTitle['max']);
        $this->assertTrue($sectionTitle['translatable']);

        $this->assertSame($descriptionType, $sectionDescription['type']);
        $this->assertFalse($sectionDescription['required']);
        $this->assertSame($descriptionMax, $sectionDescription['max']);
        $this->assertTrue($sectionDescription['translatable']);

        if ($descriptionControl) {
            $this->assertSame($descriptionControl, $sectionDescription['control']);
        } else {
            $this->assertArrayNotHasKey('control', $sectionDescription);
        }

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $sectionBackgroundColor['type']);
        $this->assertFalse($sectionBackgroundColor['required']);
        $this->assertFalse($sectionBackgroundColor['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionSpacing['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $sectionSpacing['control']);
        $this->assertFalse($sectionSpacing['required']);
        $this->assertSame(CmsBlockConfig::SECTION_SPACING_DEFAULT, $sectionSpacing['default']);
        $this->assertSame([
            CmsBlockConfig::SECTION_SPACING_DEFAULT,
            CmsBlockConfig::SECTION_SPACING_NONE,
            CmsBlockConfig::SECTION_SPACING_NO_TOP,
            CmsBlockConfig::SECTION_SPACING_NO_BOTTOM,
        ], array_column($sectionSpacing['options'], 'value'));
        $this->assertFalse($sectionSpacing['translatable']);
    }
}
