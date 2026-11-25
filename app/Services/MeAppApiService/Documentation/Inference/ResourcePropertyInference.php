<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Support\Type\MixedType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use Dedoc\Scramble\Support\Type\UnknownType;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class ResourcePropertyInference implements PropertyTypeExtension
{
    private bool $resolving = false;

    /**
     * @param ObjectType $type
     * @return bool
     */
    public function shouldHandle(ObjectType $type): bool
    {
        return !$this->resolving && is_a($type->name, JsonResource::class, true);
    }

    /**
     * @param PropertyFetchEvent $event
     * @throws ReflectionException
     * @return Type|null
     */
    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        $class = new ReflectionClass($event->instance->name);

        if (!$class->hasProperty($event->name)) {
            return null;
        }

        $property = $class->getProperty($event->name);
        $type = $property->getType();

        if (
            $property->isStatic() ||
            !$type instanceof ReflectionNamedType ||
            !in_array($type->getName(), ['int', 'float', 'string', 'bool'], true)
        ) {
            return null;
        }

        $this->resolving = true;

        try {
            $inferred = $event->instance->getPropertyType($event->name, $event->scope);
        } finally {
            $this->resolving = false;
        }

        return $inferred instanceof UnknownType || $inferred instanceof MixedType || $inferred instanceof TemplateType
            ? TypeHelper::createTypeFromReflectionType($type)
            : null;
    }
}
