<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;

class MobileRequestBodyExtension extends RequestBodyExtension
{
    public const HTTP_METHODS_WITHOUT_REQUEST_BODY = ['get', 'head'];

    /**
     * @param Parameter[] $params
     * @return Parameter[]
     */
    protected function convertDotNamedParamsToFlatQueryParams(array $params): array
    {
        $byName = array_column($params, null, 'name');
        $bounds = [];

        foreach ($params as $parameter) {
            $name = $parameter->name;
            $type = $parameter->schema instanceof Schema ? $parameter->schema->type : null;

            if ($type instanceof ArrayType && !str_contains($name, '.') && isset($byName[$name . '.*']) &&
                !isset($byName[$name . '[]']) && ($type->minItems !== null || $type->maxItems !== null)) {
                $bounds[$name . '[]'] = [$type->minItems, $type->maxItems];
            }
        }

        $parameters = parent::convertDotNamedParamsToFlatQueryParams($params);

        foreach ($parameters as $parameter) {
            if (isset($bounds[$parameter->name]) && $parameter->schema instanceof Schema &&
                $parameter->schema->type instanceof ArrayType) {
                [$min, $max] = $bounds[$parameter->name];

                if ($min !== null) {
                    $parameter->schema->type->setMin($min);
                }

                if ($max !== null) {
                    $parameter->schema->type->setMax($max);
                }
            }
        }

        return $parameters;
    }
}
