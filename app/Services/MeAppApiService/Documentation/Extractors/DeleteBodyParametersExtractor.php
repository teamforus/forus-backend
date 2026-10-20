<?php

namespace App\Services\MeAppApiService\Documentation\Extractors;

use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;

class DeleteBodyParametersExtractor implements ParameterExtractor
{
    /**
     * @param RouteInfo $routeInfo
     * @param ParametersExtractionResult[] $parameterExtractionResults
     * @return ParametersExtractionResult[]
     */
    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        if (strtolower($routeInfo->method) !== 'delete') {
            return $parameterExtractionResults;
        }

        foreach ($parameterExtractionResults as $result) {
            foreach ($result->parameters as $parameter) {
                if ($parameter->in !== 'query' || $parameter->getAttribute('nonBody')) {
                    continue;
                }

                $inferred = $routeInfo->requestParametersFromCalls->data[$parameter->name] ?? null;

                $explicitQuery = $parameter->getAttribute('isInQuery')
                    || ($parameter->schema instanceof Schema
                        && $parameter->schema->type->getAttribute('isInQuery'))
                    || $inferred?->type->getAttribute('isInQuery');

                if (!$explicitQuery) {
                    $parameter->in = 'body';
                }
            }
        }

        return $parameterExtractionResults;
    }
}
