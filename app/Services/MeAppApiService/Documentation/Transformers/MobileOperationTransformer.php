<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\ClientTypeMiddleware;
use App\Http\Middleware\ClientVersionMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\ImplementationKeyMiddleware;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Services\MeAppApiService\Documentation\Schemas\PaymentInputSchema;
use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use LogicException;

class MobileOperationTransformer implements OperationTransformer
{
    /**
     * @param OpenApiContext $context
     */
    public function __construct(private readonly OpenApiContext $context)
    {
    }

    /**
     * @param Operation $operation
     * @param RouteInfo $routeInfo
     * @return void
     */
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        resolve(Kernel::class);

        $route = $routeInfo->route;
        $appKey = $route->named('mobile.requester.*') ? 'requester' : 'provider';
        $middleware = resolve(Router::class)->gatherRouteMiddleware($route);
        $forbidden = [];

        if ($operation->security === []) {
            $operation->addSecurity(new SecurityRequirement('http'));
            $operation->addSecurity([]);
        }

        $summaries = [
            'identity.proxy.token' => 'Create a QR login request.',
            'identity.proxy.code' => 'Create a PIN login request.',
            'identity.proxy.authorize.token' => 'Approve a QR login request.',
            'identity.proxy.check-token' => 'Check login status.',
            'firestore-tokens.store' => 'Create a Firebase authentication token.',
        ];

        foreach ($summaries as $name => $summary) {
            if ($route->named('mobile.*.v1.' . $name)) {
                $operation->summary($summary);
                break;
            }
        }

        if ($route->named('mobile.provider.v1.transactions.index')) {
            $operation->addParameters([
                Parameter::make('order_by', 'query')->setSchema(Schema::fromType(new StringType())),
                Parameter::make('order_dir', 'query')->setSchema(Schema::fromType(new StringType())),
            ]);
        }

        if ($route->named('mobile.requester.v1.vouchers.index', 'mobile.requester.v1.vouchers.show')) {
            $descriptions = [
                'product_id' => 'Existing product ID for query_product context. Does not filter vouchers.',
                'implementation_id' => 'Must reference an existing implementation when non-null.',
                'implementation_key' => 'Must reference an existing implementation key when non-null.',
            ];

            foreach ($operation->parameters as $parameter) {
                if ($parameter->in === 'query' && isset($descriptions[$parameter->name])) {
                    $parameter->description($descriptions[$parameter->name]);
                }
            }
        }

        if ($route->named('mobile.*.v1.product-categories.index')) {
            foreach ($operation->parameters as $parameter) {
                if ($parameter->in !== 'query') {
                    continue;
                }

                if ($parameter->name === 'parent_id') {
                    $parameter->description('Omit or leave empty for root categories, '
                        . 'or provide an existing category ID for children.');
                } elseif ($parameter->name === 'used') {
                    $parameter->setSchema(Schema::fromType((new IntegerType())->enum([0, 1])))
                        ->description('Use 1 for categories with available products, including in descendants, '
                            . 'or 0 to include unused categories.');
                }
            }
        }

        if ($route->named('mobile.requester.v1.products.index')) {
            $descriptions = [
                'product_category_ids[]' => 'Match selected categories and their descendants.',
                'fund_ids[]' => 'Filter products by selected funds.',
                'organization_id' => 'Filter by provider organization ID.',
                'from' => 'Minimum calculated product price.',
                'to' => 'Maximum calculated product price.',
                'postcode' => 'Used with distance to filter by provider office location.',
                'distance' => 'Distance in kilometres, used with postcode.',
                'bookmarked' => 'Use 1 for your bookmarked products. Guests receive an empty list.',
                'order_by' => 'Sort field. Defaults to created_at.',
                'order_dir' => 'Sort direction. Defaults to desc.',
                'page' => 'Page number. Defaults to 1.',
            ];

            foreach ($operation->parameters as $parameter) {
                if ($parameter->in !== 'query') {
                    continue;
                }

                if (isset($descriptions[$parameter->name])) {
                    $parameter->description($descriptions[$parameter->name]);
                }

                if (in_array($parameter->name, ['product_category_ids[]', 'fund_ids[]'], true)
                    && $parameter->schema instanceof Schema && $parameter->schema->type instanceof ArrayType) {
                    $parameter->schema->type->setMax(50);
                }

                if (in_array($parameter->name, [
                    'qr', 'reservation', 'extra_payment', 'regular', 'discount_fixed', 'discount_percentage',
                    'free', 'informational', 'bookmarked',
                ], true)) {
                    $parameter->setSchema(Schema::fromType(
                        (new IntegerType())->nullable(true)->enum([0, 1, null]),
                    ));

                    if ($parameter->name !== 'bookmarked') {
                        $parameter->description('Use 1 to enable this filter.');
                    }
                }
            }

            $schema = $this->response($operation, 200)->getContent('application/json');
            $meta = $schema instanceof Schema && $schema->type instanceof ObjectType
                ? $schema->type->getProperty('meta')
                : null;

            if (!$meta instanceof ObjectType) {
                throw new LogicException('Expected pagination metadata for the product list.');
            }

            $meta->addProperty('price_max', new IntegerType())->addRequired(['price_max']);
        }

        if ($route->named('mobile.requester.v1.funds.index')) {
            foreach ($operation->parameters as $parameter) {
                if ($parameter->in === 'query' && $parameter->name === 'has_providers') {
                    $parameter->setSchema(Schema::fromType((new IntegerType())->enum([0, 1])->default(1)))
                        ->description('Use 1 for funds with approved providers or 0 for funds without them.');
                }
            }
        }

        $booleanQueryParameters = match (true) {
            $route->named('mobile.requester.v1.vouchers.index') => ['archived', 'allow_reimbursements'],
            $route->named('mobile.provider.v1.vouchers.products.index') => ['reservable'],
            default => [],
        };

        foreach ($operation->parameters as $parameter) {
            if ($parameter->in === 'query' && in_array($parameter->name, $booleanQueryParameters, true)) {
                $parameter->setSchema(Schema::fromType((new IntegerType())->enum([0, 1])))
                    ->description('Use 1 for true and 0 for false. Omit when unused.');
            }
        }

        if ($route->named(
            'mobile.*.v1.identity.proxy.email.redirect',
            'mobile.*.v1.identity.proxy.confirmation.redirect',
        )) {
            $operation->parameters = array_values(array_filter(
                $operation->parameters,
                static fn (Parameter $parameter): bool => $parameter->in !== 'query' || $parameter->name !== 'target',
            ));
        }

        if (collect($middleware)->contains(
            static fn (string $value): bool => explode(':', $value, 2)[0] === ClientTypeMiddleware::class,
        )) {
            $clientTypes = Config::get("me-app-api.$appKey.client_types");

            $operation->addParameters([
                Parameter::make('Client-Type', 'header')->required(true)
                    ->description('Required client type allowed for this API. Endpoint permissions still apply.')
                    ->setSchema(Schema::fromType((new StringType())->enum($clientTypes)))
                    ->example($clientTypes[0] ?? null),
            ]);

            $forbidden[] = 'unknown_client_type';
        }

        if (in_array(ClientVersionMiddleware::class, $middleware, true)) {
            $operation->addParameters([
                $this->header(
                    'Client-Version',
                    'Optional integer version. Me-app version 1 or later supports viewing vouchers '
                    . 'through product or reservation permissions.',
                    ['1'],
                ),
            ]);
        }

        if (collect($middleware)->contains(
            static fn (string $value): bool => explode(':', $value, 2)[0] === ImplementationKeyMiddleware::class,
        )) {
            $clientKeys = Config::get("me-app-api.$appKey.client_keys");

            $operation->addParameters([
                Parameter::make('Client-Key', 'header')->required(true)
                    ->description('Required implementation key allowed for this API.')
                    ->setSchema(Schema::fromType((new StringType())->enum($clientKeys)))
                    ->example($clientKeys[0] ?? null),
            ]);

            $forbidden[] = 'unknown_implementation_key';
        }

        if (in_array(DomainMiddleware::class, $middleware, true)) {
            $forbidden[] = 'invalid_host_name when domain verification is enabled';
        }

        if (in_array(ApiAuthMiddleware::class, $middleware, true)) {
            $operation->addResponse(Response::make(401)->setDescription(
                'Session is invalid, expired, or inactive, or requires two-factor confirmation.',
            )->setContent('application/json', Schema::fromType((new AnyOf())->setItems([
                (new ObjectType())->addProperty('message', (new StringType())->enum([
                    'session_expired', 'invalid_access_token', 'proxy_identity_pending', 'proxy_identity_not_active',
                ]))->setRequired(['message']),
                (new ObjectType())->addProperty('error', (new StringType())->const('2fa'))->setRequired(['error']),
            ]))));
        }

        if ($forbidden) {
            $operation->addResponse(Response::make(403)->setDescription(
                'Access denied. Possible causes: ' . implode(', ', $forbidden) . '.',
            )->setContent('application/json', Schema::fromType(
                (new ObjectType())->addProperty('message', (new StringType())->nullable(true))
                    ->setRequired(['message']),
            )));
        }

        if ($route->named('mobile.*.v1.identity.proxy.check-token')) {
            $operation->addParameters([
                $this->header(
                    'Access-Token',
                    'Login token to check, separate from the bearer token. '
                    . 'Missing or unknown tokens return `invalid`.',
                ),
            ]);
        }

        if ($route->named('mobile.*.v1.firestore-tokens.store')) {
            $operation->addResponse(Response::make(429)->setDescription('Rate limit: 30 requests per minute.'));
        }

        if ($route->named('mobile.*.v1.identity.show')) {
            $this->response($operation, 200)->setDescription(
                'Your identity. Email is null when unavailable.',
            );
        }

        if ($route->named('mobile.provider.v1.vouchers.show')) {
            $operation->description .= "\n\nBudget amount fields are null unless you can spend "
                . 'the voucher directly. '
                . 'With Me-app Client-Version ≥ 1, product or reservation permissions can grant access '
                . 'with null amount fields. '
                . 'Product vouchers include product details.';
        }

        if ($route->named('mobile.provider.v1.vouchers.transactions.store')) {
            $schema = $this->context->openApi->components->addSchema(
                StoreVoucherTransactionRequest::class,
                Schema::fromType(new PaymentInputSchema()),
            );

            $operation->requestBodyObject = RequestBodyObject::make()
                ->description(
                    'Input requirements depend on the voucher type. Product vouchers accept an empty body. '
                    . 'Permissions and available balance are checked by the server.',
                )
                ->setContent('application/json', $schema);

            $this->response($operation, 200)->setCode(201);

            $operation->addResponse(Response::make(422)->setDescription(
                'Invalid input. Sending both `amount` and `product_id`, even as null, returns `message` only. '
                . 'Other validation failures also include `errors`.',
            )->setContent('application/json', Schema::fromType(
                (new ObjectType())->addProperty('message', new StringType())
                    ->addProperty('errors', (new ObjectType())->additionalProperties(
                        (new ArrayType())->setItems(new StringType()),
                    ))->setRequired(['message']),
            )));

            $operation->addResponse(Response::make(429)->setDescription(
                'Too many simultaneous payment requests for this voucher. Try again.',
            ));
        }
    }

    /**
     * @param string $name
     * @param string $description
     * @param array $examples
     * @return Parameter
     */
    private function header(string $name, string $description, array $examples = []): Parameter
    {
        return Parameter::make($name, 'header')->setSchema(Schema::fromType(
            (new StringType())->setDescription($description)->examples($examples),
        ));
    }

    /**
     * @param Operation $operation
     * @param int $code
     * @return Response
     */
    private function response(Operation $operation, int $code): Response
    {
        foreach ($operation->responses as $response) {
            $resolved = $response instanceof Reference ? $response->resolve() : $response;

            if ((int) $resolved->code === $code) {
                return $resolved;
            }
        }

        throw new LogicException("Expected inferred $code response for $operation->operationId.");
    }
}
