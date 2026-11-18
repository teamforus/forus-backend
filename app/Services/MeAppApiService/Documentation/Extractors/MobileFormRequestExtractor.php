<?php

namespace App\Services\MeAppApiService\Documentation\Extractors;

use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Services\MeAppApiService\Http\Requests\Shared\Identity\IdentityAuthorizationEmailRedirectRequest;
use Dedoc\Scramble\Diagnostics\DiagnosticsCollector;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\FormRequestParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\RulesToParameters;
use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\PrettyPrinter;
use ReflectionNamedType;

class MobileFormRequestExtractor extends FormRequestParametersExtractor
{
    /**
     * @param PrettyPrinter $printer
     * @param TypeTransformer $typeTransformer
     * @param DiagnosticsCollector $diagnostics
     */
    public function __construct(
        PrettyPrinter $printer,
        private readonly TypeTransformer $typeTransformer,
        DiagnosticsCollector $diagnostics,
    ) {
        parent::__construct($printer, $typeTransformer, $diagnostics);
    }

    /**
     * @param RouteInfo $routeInfo
     * @param array $parameterExtractionResults
     * @return array
     */
    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        foreach ($routeInfo->reflectionAction()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            if ($type->getName() === IdentityAuthorizationEmailRedirectRequest::class) {
                $request = new IdentityAuthorizationEmailRedirectRequest();
                $request->setRouteResolver(static fn () => $routeInfo->route);

                return [
                    ...$parameterExtractionResults,
                    new ParametersExtractionResult(
                        parameters: (new RulesToParameters($request->rules(), [], $this->typeTransformer, 'query'))
                            ->mergeDotNotatedKeys(false)->handle(),
                        sourceClass: IdentityAuthorizationEmailRedirectRequest::class,
                    ),
                ];
            }

            if ($type->getName() === StoreVoucherTransactionRequest::class) {
                return $parameterExtractionResults;
            }
        }

        return parent::handle($routeInfo, $parameterExtractionResults);
    }
}
