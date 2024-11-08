<?php

namespace App\Exceptions;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
        $request, Throwable $e
    ): ResponseFactory|Application|Response|SymfonyResponse {
        if ($e instanceof ModelNotFoundException) {
            $reflection = new ReflectionClass($e->getModel());
            $modelKey = $this->mapModelNames[$reflection->getShortName()] ?? 'default';
            $model = trans("exceptions.models.$modelKey");

            $e = new NotFoundHttpException(trans('exceptions.not_found', compact('model')));
        }

        return parent::render($request, $e);
    }
}
