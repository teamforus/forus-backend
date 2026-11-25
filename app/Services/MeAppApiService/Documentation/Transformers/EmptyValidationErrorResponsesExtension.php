<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Http\Requests\BaseFormRequest;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\OperationExtensions\ErrorResponsesExtension;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class EmptyValidationErrorResponsesExtension extends ErrorResponsesExtension
{
    /**
     * @param Operation $operation
     * @param RouteInfo $routeInfo
     * @return void
     */
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $methodType = $routeInfo->getActionType();
        $existingExceptions = $methodType?->exceptions ?? [];

        parent::handle($operation, $routeInfo);

        if (!$methodType) {
            return;
        }

        $requestType = collect($methodType->arguments)
            ->first(static fn (Type $type): bool => $type->isInstanceOf(FormRequest::class));

        $requestType = $requestType instanceof TemplateType ? $requestType->is : $requestType;

        if (!$requestType instanceof ObjectType || $requestType->name !== BaseFormRequest::class) {
            return;
        }

        $methodType->exceptions = array_values(array_filter(
            $methodType->exceptions,
            static fn (Type $exception): bool => !$exception->isInstanceOf(ValidationException::class) ||
                in_array($exception, $existingExceptions, true),
        ));
    }
}
