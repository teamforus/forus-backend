<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
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
     * @param $request
     * @param Throwable $e
     * @return ResponseFactory|Application|Response|SymfonyResponse
     * @throws Throwable
     */
    public function render(
        $request, 
        Throwable $e,
    ): ResponseFactory|Application|Response|SymfonyResponse {
        $message = $e->getMessage();

        $message = $this->getMessageByInstance($e) ?: $message;
        $message = $this->getMessageByStatusCode($e) ?: $message;

        $this->setMessage($e, $message);

        return parent::render($request, $e);
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        return config('app.debug') ? parent::convertExceptionToArray($e) : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : trans('exceptions.server_error'),
        ];
    }

    /**
     * @param Throwable $e
     * @return string|null
     * @throws \ReflectionException
     */
    protected function getMessageByInstance(Throwable $e): ?string
    {
        $message = null;
        if ($e instanceof ModelNotFoundException) {
            $reflection = new ReflectionClass($e->getModel());
            $modelKey = $this->mapModelNames[$reflection->getShortName()] ?? 'default';
            $message = trans("exceptions.not_found.$modelKey");
        }

        if ($e instanceof AuthorizationException) {
            $message = trans('exceptions.forbidden');
        }

        if ($e instanceof ValidationException) {
            $message = trans('exceptions.validation_error');
        }

        return $message;
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

    /**
     * @param Throwable $e
     * @param string $message
     * @return void
     * @throws \ReflectionException
     */
    protected function setMessage(Throwable $e, string $message): void
    {
        $reflectionObject = new \ReflectionObject($e);
        $reflectionObjectProp = $reflectionObject->getProperty('message');
        $reflectionObjectProp->setValue($e, $message);
    }
}
