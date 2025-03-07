<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationJsonException::class,
    ];

    /**
     * @var string[]
     */
    protected array $mapModelNames = [
        'Fund' => 'fund',
        'Product' => 'product',
        'FundProvider' => 'provider',
        'Organization' => 'organization',
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * @param $request
     * @param Throwable $e
     * @throws ReflectionException
     * @throws Throwable
     * @return SymfonyResponse
     */
    public function render($request, Throwable $e): SymfonyResponse
    {
        $message = $this->getMessageByInstance($e) ?: $this->getMessageByStatusCode($e) ?: $e->getMessage();

        $reflection = new ReflectionObject($e);
        $reflection->getProperty('message')?->setValue($e, $message);

        return parent::render($request, $e);
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => trans('validation.header'),
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        return Config::get('app.debug') ? parent::convertExceptionToArray($e) : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : trans('exceptions.server_error'),
        ];
    }

    /**
     * @param Throwable $e
     * @throws ReflectionException
     * @return string|null
     */
    protected function getMessageByInstance(Throwable $e): ?string
    {
        if ($e instanceof ModelNotFoundException) {
            $reflection = new ReflectionClass($e->getModel());
            $modelKey = $this->mapModelNames[$reflection->getShortName()] ?? 'default';

            return trans("exceptions.not_found.$modelKey");
        }

        if ($e instanceof AuthorizationException) {
            return trans('exceptions.forbidden');
        }

        if ($e instanceof ValidationException) {
            return trans('exceptions.validation_error');
        }

        return null;
    }

    /**
     * @param Throwable $e
     * @return string|null
     */
    protected function getMessageByStatusCode(Throwable $e): ?string
    {
        if (method_exists($e, 'getStatusCode')) {
            return match ($e->getStatusCode()) {
                403 => trans('exceptions.forbidden'),
                404 => trans('exceptions.not_found.default'),
                422 => trans('exceptions.validation_error'),
                500 => trans('exceptions.server_error'),
                default => null,
            };
        }

        return null;
    }
}
