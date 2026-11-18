<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\ClientTypeMiddleware;
use App\Http\Middleware\ClientVersionMiddleware;
use App\Http\Middleware\ImplementationKeyMiddleware;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Services\MeAppApiService\Documentation\Catalogs\DescriptionCatalog;
use App\Services\MeAppApiService\Documentation\Schemas\PaymentInputSchema;
use App\Services\MeAppApiService\Enums\RateLimiterName;
use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Header;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use LogicException;

readonly class MobileOperationTransformer implements OperationTransformer
{
    /**
     * @param OpenApiContext $context
     */
    public function __construct(private OpenApiContext $context)
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
        $operation->setAttribute('mobile_route_name', substr($route->getName(), strlen("mobile.$appKey.")));
        $middleware = resolve(Router::class)->gatherRouteMiddleware($route);
        $forbidden = [];

        $hideBearerAuth = $route->named(
            'mobile.*.v1.status',
            'mobile.*.v1.identity.store',
            'mobile.*.v1.identity.proxy.code',
            'mobile.*.v1.identity.proxy.token',
            'mobile.*.v1.identity.proxy.check-token',
            'mobile.*.v1.identity.proxy.email.redirect',
            'mobile.*.v1.identity.proxy.email.exchange',
            'mobile.*.v1.identity.proxy.confirmation.redirect',
            'mobile.*.v1.identity.proxy.confirmation.exchange',
        );

        if ($operation->security === [] && !$hideBearerAuth) {
            $operation->addSecurity(new SecurityRequirement('http'));
            $operation->addSecurity([]);
        }

        foreach ($operation->parameters as $parameter) {
            if (!$parameter instanceof Parameter || $parameter->in !== 'query' ||
                !$parameter->schema instanceof Schema || !$parameter->schema->type instanceof BooleanType) {
                continue;
            }

            $nullable = $parameter->schema->type->nullable;
            $type = (new IntegerType())->nullable($nullable)->enum($nullable ? [0, 1, null] : [0, 1]);

            if ($route->named('mobile.requester.v1.funds.index') && $parameter->name === 'has_providers') {
                $type->default(1);
            }

            $parameter->setSchema(Schema::fromType($type));
        }

        if (collect($middleware)->contains(
            static fn (string $value): bool => explode(':', $value, 2)[0] === ClientTypeMiddleware::class,
        )) {
            $clientTypes = Config::get("me-app-api.$appKey.client_types");

            $operation->addParameters([
                Parameter::make('Client-Type', 'header')->required(true)
                    ->setSchema(Schema::fromType((new StringType())->enum($clientTypes)))
                    ->example($clientTypes[0] ?? null),
            ]);

            $forbidden[] = 'unknown_client_type';
        }

        if (collect($middleware)->contains(
            static fn (string $value): bool => explode(':', $value, 2)[0] === ClientVersionMiddleware::class,
        )) {
            $operation->addParameters([
                Parameter::make('Client-Version', 'header')->required(true)
                    ->setSchema(Schema::fromType((new IntegerType())->setMin(1)))
                    ->example(1),
            ]);

            $forbidden[] = 'invalid_client_version';
        }

        if (collect($middleware)->contains(
            static fn (string $value): bool => explode(':', $value, 2)[0] === ImplementationKeyMiddleware::class,
        )) {
            $clientKeys = Config::get("me-app-api.$appKey.client_keys");

            $operation->addParameters([
                Parameter::make('Client-Key', 'header')->required(true)
                    ->setSchema(Schema::fromType((new StringType())->enum($clientKeys)))
                    ->example($clientKeys[0] ?? null),
            ]);

            $forbidden[] = 'unknown_implementation_key';
        }

        if (in_array(ApiAuthMiddleware::class, $middleware, true)) {
            $operation->addResponse(Response::make(401)->setContent('application/json', Schema::fromType(
                (new AnyOf())->setItems([
                    (new ObjectType())->addProperty('message', (new StringType())->enum([
                        'session_expired', 'invalid_access_token', 'proxy_identity_pending',
                        'proxy_identity_not_active',
                    ]))->setRequired(['message']),
                    (new ObjectType())->addProperty('error', (new StringType())->const('2fa'))->setRequired(['error']),
                ]),
            )));
        }

        if ($route->named(
            'mobile.*.v1.identity.proxy.email.exchange',
            'mobile.*.v1.identity.proxy.confirmation.exchange',
            'mobile.provider.v1.identity.proxy.authorize.token',
        )) {
            $forbidden[] = DescriptionCatalog::load($appKey)->fragment('token_state');
        }

        if ($forbidden) {
            $operation->addResponse(Response::make(403)->setContent('application/json', Schema::fromType(
                (new ObjectType())->addProperty('message', new StringType())->setRequired(['message']),
            )));
        }

        if ($route->named('mobile.*.v1.identity.proxy.check-token')) {
            $operation->addParameters([
                Parameter::make('Access-Token', 'header')->setSchema(Schema::fromType(new StringType())),
            ]);
        }

        $browserRedirect = $route->named(
            'mobile.*.v1.identity.proxy.email.redirect',
            'mobile.*.v1.identity.proxy.confirmation.redirect',
        );

        if ($browserRedirect) {
            $operation->addResponse(Response::make(422)
                ->setContent('text/html', Schema::fromType(new StringType())));
        }

        if ($route->named('mobile.provider.v1.vouchers.transactions.store')) {
            $schema = $this->context->openApi->components->addSchema(
                StoreVoucherTransactionRequest::class,
                Schema::fromType(new PaymentInputSchema()),
            );

            $operation->requestBodyObject = RequestBodyObject::make()
                ->setContent('application/json', $schema);

            $this->response($operation, 200)->setCode(201);

            $operation->addResponse(Response::make(422)->setContent('application/json', Schema::fromType(
                (new ObjectType())->addProperty('message', new StringType())
                    ->addProperty('errors', (new ObjectType())->additionalProperties(
                        (new ArrayType())->setItems(new StringType()),
                    ))->setRequired(['message']),
            )));

            $operation->addResponse(Response::make(429));
        }

        $rateLimiterMiddleware = array_map(
            static fn (RateLimiterName $name): string => ThrottleRequests::class . ':' . $name->value,
            RateLimiterName::cases(),
        );

        if (array_intersect($middleware, $rateLimiterMiddleware)) {
            $this->addThrottleResponses($operation, html: $browserRedirect);
        }

        $operation->setAttribute('documentation_values', ['causes' => implode(', ', $forbidden)]);
    }

    /**
     * @param Operation $operation
     * @param bool $html
     * @return void
     */
    private function addThrottleResponses(Operation $operation, bool $html = false): void
    {
        $operation->addResponse(Response::make(429)->setContent(
            $html ? 'text/html' : 'application/json',
            Schema::fromType($html ? new StringType() : (new ObjectType())
                ->addProperty('message', new StringType())->setRequired(['message'])),
        ));

        foreach ($operation->responses as $index => $response) {
            $resolved = $response instanceof Reference ? $response->resolve() : $response;
            $code = (int) $resolved->code;

            $response = clone $resolved;

            $headers = [
                'X-RateLimit-Limit', 'X-RateLimit-Remaining',
                ...($code === 429 ? ['Retry-After', 'X-RateLimit-Reset'] : []),
            ];

            foreach ($headers as $name) {
                $response->addHeader($name, new Header(
                    required: true,
                    schema: Schema::fromType(new IntegerType()),
                ));
            }

            $operation->responses[$index] = $response;
        }
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
