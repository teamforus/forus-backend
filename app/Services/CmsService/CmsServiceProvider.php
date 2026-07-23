<?php

namespace App\Services\CmsService;

use App\Services\CmsService\ImplementationBlocks\Commands\CleanupStaleBlockValuesCommand;
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
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/ImplementationBlocks/migrations');

        Relation::morphMap([
            'implementation_cms_block_value' => ImplementationCmsBlockValue::class,
            'implementation_cms_block_item_value' => ImplementationCmsBlockItemValue::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        ImplementationCmsBlockService::setBlockConfigs([
            new InfoCmsBlockConfig(),
            new TextCmsBlockConfig(),
            new BannerCmsBlockConfig(),
            new CalloutCmsBlockConfig(),
            new FaqCmsBlockConfig(),
            new LinkPanelsCmsBlockConfig(),
            new ProvidersMapCmsBlockConfig(),
            new ProductCategoriesCmsBlockConfig(),
            new ProductShowcaseCmsBlockConfig(),
            new ProviderSignUpCmsBlockConfig(),
        ]);

        $this->app->singleton(ImplementationCmsBlockService::class);

        $this->commands([
            CleanupStaleBlockValuesCommand::class,
        ]);
    }
}
