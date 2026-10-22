<?php

namespace App\Services\MeAppApiService;

use App\Models\Identity;
use App\Services\MeAppApiService\Documentation\Extractors\DeleteBodyParametersExtractor;
use App\Services\MeAppApiService\Documentation\Extractors\MobileFormRequestExtractor;
use App\Services\MeAppApiService\Documentation\Inference\ArraySpreadInference;
use App\Services\MeAppApiService\Documentation\Inference\BaseJsonResourceInference;
use App\Services\MeAppApiService\Documentation\Inference\EmptyObjectCastInference;
use App\Services\MeAppApiService\Documentation\Inference\TranslatedColumnsInference;
use App\Services\MeAppApiService\Documentation\Responses\ViewResponse;
use App\Services\MeAppApiService\Documentation\Transformers\DeleteRequestBodyExtension;
use App\Services\MeAppApiService\Documentation\Transformers\IdentityStoreRequestSchema;
use App\Services\MeAppApiService\Documentation\Transformers\MobileOperationTransformer;
use App\Services\MeAppApiService\Documentation\Transformers\SchemaGroups;
use Dedoc\Scramble\Configuration\OperationTransformers;
use Dedoc\Scramble\Configuration\ParametersExtractors;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\FormRequestParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        if (!class_exists(Scramble::class)) {
            return;
        }

        Scramble::ignoreDefaultRoutes();
        Gate::define('viewApiDocs', fn (?Identity $identity = null): bool => !$this->app->isProduction());

        Scramble::registerExtension(ArraySpreadInference::class);
        Scramble::registerExtension(BaseJsonResourceInference::class);
        Scramble::registerExtension(EmptyObjectCastInference::class);
        Scramble::registerExtension(TranslatedColumnsInference::class);
        Scramble::registerExtension(ViewResponse::class);

        foreach ([
            'requester' => 'Forus Wallet Mobile App API',
            'provider' => 'Forus Provider Mobile App API',
        ] as $appKey => $title) {
            $namespace = Config::get("me-app-api.$appKey.namespace");

            Scramble::registerApi($appKey, [
                'api_path' => trim($namespace, '/') . '/v1',
                'export_path' => storage_path("app/openapi-$appKey.json"),
                'ui' => ['title' => $title],
                'security_strategy' => [MiddlewareAuthSecurityStrategy::class, ['middleware' => ['api.auth']]],
                'info' => [
                    'version' => '1.0.0',
                    'description' => $title . '.',
                ],
            ])->routes(static fn (Route $route): bool => $route->named("mobile.$appKey.v1.*"))
                ->withParametersExtractors(static function (ParametersExtractors $extractors): void {
                    $extractors->use(array_map(
                        static fn (string $extractor): string => $extractor === FormRequestParametersExtractor::class
                            ? MobileFormRequestExtractor::class
                            : $extractor,
                        [...$extractors->all(), DeleteBodyParametersExtractor::class],
                    ));
                })->withOperationTransformers(static function (OperationTransformers $transformers): void {
                    $transformers->use(array_map(
                        static fn (string $transformer): string => $transformer === RequestBodyExtension::class
                            ? DeleteRequestBodyExtension::class
                            : $transformer,
                        $transformers->all(),
                    ));

                    $transformers->append(MobileOperationTransformer::class);
                })
                ->withDocumentTransformers([
                    IdentityStoreRequestSchema::class, SchemaGroups::class,
                ]);

            if (!$this->app->isProduction()) {
                Scramble::registerUiRoute("docs/mobile/$appKey", $appKey);
                Scramble::registerJsonSpecificationRoute("docs/mobile/$appKey.json", $appKey);
            }
        }
    }
}
