<?php

namespace App\Services\MeAppApiService\Documentation\Extractors;

use App\Http\Requests\Api\Platform\FirestoreTokens\StoreFirestoreTokenRequest;
use App\Http\Requests\Api\Platform\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers\IndexProductVouchersRequest;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Permission;
use App\Models\VoucherTransaction;
use Dedoc\Scramble\Diagnostics\DiagnosticsCollector;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\FormRequestParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\RulesToParameters;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Validation\Rule;
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
        private TypeTransformer $typeTransformer,
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

            if ($type->getName() === StoreFirestoreTokenRequest::class) {
                return [
                    ...$parameterExtractionResults,
                    new ParametersExtractionResult(
                        parameters: [
                            Parameter::make('key', 'body')
                                ->required(true)
                                ->description(
                                    'Configured app key required to request a Firebase token.',
                                )
                                ->setSchema(Schema::fromType(new StringType())),
                        ],
                        schemaName: StoreFirestoreTokenRequest::class,
                        sourceClass: StoreFirestoreTokenRequest::class,
                    ),
                ];
            }

            if ($type->getName() === StoreVoucherTransactionRequest::class) {
                return $parameterExtractionResults;
            }

            if (in_array($type->getName(), [
                IndexProductVouchersRequest::class, IndexProductsRequest::class, IndexTransactionsRequest::class,
            ], true)) {
                return [...$parameterExtractionResults, $this->providerListParameters($type->getName())];
            }
        }

        return parent::handle($routeInfo, $parameterExtractionResults);
    }

    /**
     * @param class-string<BaseFormRequest> $requestClass
     * @return ParametersExtractionResult
     */
    private function providerListParameters(string $requestClass): ParametersExtractionResult
    {
        $rules = match ($requestClass) {
            IndexProductVouchersRequest::class => [
                'per_page' => 'numeric|between:1,100',
            ],
            IndexProductsRequest::class => [
                'reservable' => 'nullable|boolean',
                'per_page' => 'numeric|between:1,100',
            ],
            IndexTransactionsRequest::class => [
                'q' => 'nullable|string',
                'state' => Rule::in(VoucherTransaction::STATES),
                'fund_state' => Rule::in(Fund::STATES),
                'from' => 'date:Y-m-d',
                'to' => 'date:Y-m-d',
                'amount_min' => 'numeric|min:0',
                'amount_max' => 'numeric|min:0',
                'per_page' => (new BaseFormRequest())->perPageRule(),
            ],
        };

        return new ParametersExtractionResult([
            Parameter::make('organization_id', 'query')
                ->description(
                    'Optional organization ID. Requires `' . Permission::SCAN_VOUCHERS
                    . '` permission for that organization.',
                )
                ->setSchema(Schema::fromType((new IntegerType())->nullable(true))),
            ...(new RulesToParameters($rules, [], $this->typeTransformer, 'query'))
                ->mergeDotNotatedKeys(false)->handle(),
        ], sourceClass: $requestClass);
    }
}
