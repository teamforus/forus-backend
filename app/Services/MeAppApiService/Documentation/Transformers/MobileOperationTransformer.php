<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\ClientTypeMiddleware;
use App\Http\Middleware\ClientVersionMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\ImplementationKeyMiddleware;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Models\Implementation;
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
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
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
        $middleware = resolve(Router::class)->gatherRouteMiddleware($route);
        $forbidden = [];

        $summaries = [
            'identity.proxy.token' => 'Create a QR login request.',
            'identity.proxy.code' => 'Create a PIN login request.',
            'identity.proxy.authorize.token' => 'Approve a QR login request.',
            'identity.proxy.short-token' => 'Create a temporary login token.',
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

        if ($route->named('mobile.requester.v1.vouchers.index')) {
            $descriptions = [
                'product_id' => 'Must reference an existing product when non-null.',
                'implementation_id' => 'Must reference an existing implementation when non-null.',
                'implementation_key' => 'Must reference an existing implementation key when non-null.',
            ];

            foreach ($operation->parameters as $parameter) {
                if ($parameter->in === 'query' && isset($descriptions[$parameter->name])) {
                    $parameter->description($descriptions[$parameter->name]);
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

        if (in_array(ClientTypeMiddleware::class, $middleware, true)) {
            $operation->addParameters([
                $this->header(
                    'Client-Type',
                    'Client type. Defaults to the configured client type. '
                    . 'Examples are not exhaustive. Endpoint permissions still apply.',
                    [
                        Implementation::ME_APP_REQUESTER_ANDROID, Implementation::ME_APP_REQUESTER_IOS,
                        Implementation::ME_APP_PROVIDER_ANDROID, Implementation::ME_APP_PROVIDER_IOS,
                    ],
                ),
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

        if (in_array(ImplementationKeyMiddleware::class, $middleware, true)) {
            $operation->addParameters([
                $this->header(
                    'Client-Key',
                    'Configured implementation key. Defaults to `general`.',
                    [Implementation::KEY_GENERAL],
                ),
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

        if ($route->named('mobile.*.v1.identity.proxy.short-token')) {
            $operation->security = null;
            $forbidden[] = 'missing authenticated identity';
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
            $operation->description .= "\n\nBudget amount fields are included only when you can spend "
                . 'the voucher directly. '
                . 'With Me-app Client-Version ≥ 1, product or reservation permissions can grant access '
                . 'without those fields. '
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
