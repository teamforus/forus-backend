<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use Dedoc\Scramble\Infer\Extensions\Event\FunctionCallEvent;
use Dedoc\Scramble\Infer\Extensions\FunctionReturnTypeExtension;
use Dedoc\Scramble\Support\Type\GenericClassStringType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

class ContainerResolveInference implements FunctionReturnTypeExtension
{
    /**
     * @param string $name
     * @return bool
     */
    public function shouldHandle(string $name): bool
    {
        return ltrim($name, '\\') === 'resolve';
    }

    /**
     * @param FunctionCallEvent $event
     * @return Type|null
     */
    public function getFunctionReturnType(FunctionCallEvent $event): ?Type
    {
        $name = $event->getArg('name', 0);

        if ($name instanceof GenericClassStringType && $name->type instanceof ObjectType) {
            return $name->type;
        }

        if (!$name instanceof LiteralStringType || (!class_exists($name->value) && !interface_exists($name->value))) {
            return null;
        }

        return new ObjectType($name->value);
    }
}
