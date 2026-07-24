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
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use Tests\TestCase;

class ImplementationCmsBlockServiceTest extends TestCase
{
    /**
     * @return void
     */
    public function testServiceResolvesRegisteredConfigsFromContainer(): void
    {
        resolve(ImplementationCmsBlockService::class);
        $infoConfig = ImplementationCmsBlockService::getBlockConfig(InfoCmsBlockConfig::KEY);
        $textConfig = ImplementationCmsBlockService::getBlockConfig(TextCmsBlockConfig::KEY);
        $bannerConfig = ImplementationCmsBlockService::getBlockConfig(BannerCmsBlockConfig::KEY);
        $calloutConfig = ImplementationCmsBlockService::getBlockConfig(CalloutCmsBlockConfig::KEY);
        $faqConfig = ImplementationCmsBlockService::getBlockConfig(FaqCmsBlockConfig::KEY);
        $linkPanelsConfig = ImplementationCmsBlockService::getBlockConfig(LinkPanelsCmsBlockConfig::KEY);
        $providersMapConfig = ImplementationCmsBlockService::getBlockConfig(ProvidersMapCmsBlockConfig::KEY);
        $productCategoriesConfig = ImplementationCmsBlockService::getBlockConfig(ProductCategoriesCmsBlockConfig::KEY);
        $productShowcaseConfig = ImplementationCmsBlockService::getBlockConfig(ProductShowcaseCmsBlockConfig::KEY);
        $providerSignUpConfig = ImplementationCmsBlockService::getBlockConfig(ProviderSignUpCmsBlockConfig::KEY);

        $this->assertInstanceOf(InfoCmsBlockConfig::class, $infoConfig);
        $this->assertInstanceOf(TextCmsBlockConfig::class, $textConfig);
        $this->assertInstanceOf(BannerCmsBlockConfig::class, $bannerConfig);
        $this->assertInstanceOf(CalloutCmsBlockConfig::class, $calloutConfig);
        $this->assertInstanceOf(FaqCmsBlockConfig::class, $faqConfig);
        $this->assertInstanceOf(LinkPanelsCmsBlockConfig::class, $linkPanelsConfig);
        $this->assertInstanceOf(ProvidersMapCmsBlockConfig::class, $providersMapConfig);
        $this->assertInstanceOf(ProductCategoriesCmsBlockConfig::class, $productCategoriesConfig);
        $this->assertInstanceOf(ProductShowcaseCmsBlockConfig::class, $productShowcaseConfig);
        $this->assertInstanceOf(ProviderSignUpCmsBlockConfig::class, $providerSignUpConfig);
        $this->assertSame('Info', $infoConfig->name());
        $this->assertSame('Tekst', $textConfig->name());
        $this->assertSame('Banner', $bannerConfig->name());
        $this->assertSame('Aandachtsblok', $calloutConfig->name());
        $this->assertSame('Veelgestelde vragen', $faqConfig->name());
        $this->assertSame('Linkpanelen', $linkPanelsConfig->name());
        $this->assertSame('Aanbiederskaart', $providersMapConfig->name());
        $this->assertSame('Aanbod categorieën', $productCategoriesConfig->name());
        $this->assertSame('Aanbod', $productShowcaseConfig->name());
        $this->assertSame('Aanmelden als aanbieder', $providerSignUpConfig->name());
    }

    /**
     * @return void
     */
    public function testBlockConfigsAllowedPageTypesMatchConfiguredPages(): void
    {
        $config = new InfoCmsBlockConfig();
        $textConfig = new TextCmsBlockConfig();
        $bannerConfig = new BannerCmsBlockConfig();
        $calloutConfig = new CalloutCmsBlockConfig();
        $faqConfig = new FaqCmsBlockConfig();
        $linkPanelsConfig = new LinkPanelsCmsBlockConfig();
        $providersMapConfig = new ProvidersMapCmsBlockConfig();
        $productCategoriesConfig = new ProductCategoriesCmsBlockConfig();
        $productShowcaseConfig = new ProductShowcaseCmsBlockConfig();
        $providerSignUpConfig = new ProviderSignUpCmsBlockConfig();

        foreach (ImplementationPage::PAGE_TYPES as $pageType) {
            $this->assertArrayHasKey('generic_cms_blocks', $pageType);
        }

        $expectedPageTypes = array_values(array_map(
            fn (array $pageType) => $pageType['key'],
            array_filter(
                ImplementationPage::PAGE_TYPES,
                fn (array $pageType) => $pageType['generic_cms_blocks'],
            ),
        ));

        $this->assertSame($expectedPageTypes, $config->allowedPageTypes());
        $this->assertSame($expectedPageTypes, $textConfig->allowedPageTypes());
        $this->assertSame($expectedPageTypes, $calloutConfig->allowedPageTypes());
        $this->assertSame($expectedPageTypes, $faqConfig->allowedPageTypes());
        $this->assertSame($expectedPageTypes, $linkPanelsConfig->allowedPageTypes());
        $this->assertSame([ImplementationPage::TYPE_HOME], $providersMapConfig->allowedPageTypes());
        $this->assertSame([ImplementationPage::TYPE_HOME], $productCategoriesConfig->allowedPageTypes());
        $this->assertSame([ImplementationPage::TYPE_HOME], $productShowcaseConfig->allowedPageTypes());
        $this->assertSame([ImplementationPage::TYPE_PROVIDER], $providerSignUpConfig->allowedPageTypes());
        $this->assertSame([
            ImplementationPage::TYPE_HOME,
            ImplementationPage::TYPE_EXPLANATION,
            ImplementationPage::TYPE_PRIVACY,
            ImplementationPage::TYPE_ACCESSIBILITY,
            ImplementationPage::TYPE_TERMS_AND_CONDITIONS,
            ImplementationPage::TYPE_PROVIDER,
        ], $bannerConfig->allowedPageTypes());
    }

    /**
     * @return void
     */
    public function testServiceFiltersConfigsByPageType(): void
    {
        resolve(ImplementationCmsBlockService::class);

        $this->assertSame(
            [
                InfoCmsBlockConfig::KEY,
                TextCmsBlockConfig::KEY,
                BannerCmsBlockConfig::KEY,
                CalloutCmsBlockConfig::KEY,
                FaqCmsBlockConfig::KEY,
                LinkPanelsCmsBlockConfig::KEY,
                ProvidersMapCmsBlockConfig::KEY,
                ProductCategoriesCmsBlockConfig::KEY,
                ProductShowcaseCmsBlockConfig::KEY,
            ],
            array_map(
                fn (CmsBlockConfig $config) => $config->key(),
                ImplementationCmsBlockService::getBlockConfigsForPageType(ImplementationPage::TYPE_HOME),
            ),
        );
        $this->assertSame(
            [],
            ImplementationCmsBlockService::getBlockConfigsForPageType(ImplementationPage::TYPE_FUNDS),
        );
        $this->assertSame(
            [
                InfoCmsBlockConfig::KEY,
                TextCmsBlockConfig::KEY,
                BannerCmsBlockConfig::KEY,
                CalloutCmsBlockConfig::KEY,
                FaqCmsBlockConfig::KEY,
                LinkPanelsCmsBlockConfig::KEY,
                ProviderSignUpCmsBlockConfig::KEY,
            ],
            array_map(
                fn (CmsBlockConfig $config) => $config->key(),
                ImplementationCmsBlockService::getBlockConfigsForPageType(ImplementationPage::TYPE_PROVIDER),
            ),
        );
        $this->assertSame(
            [],
            ImplementationCmsBlockService::getBlockConfigsForPageType(ImplementationPage::TYPE_PRODUCTS),
        );
    }
}
