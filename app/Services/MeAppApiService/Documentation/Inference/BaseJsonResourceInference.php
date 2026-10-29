<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use App\Http\Resources\BaseJsonResource;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\Event\StaticMethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Infer\Extensions\StaticMethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralBooleanType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseJsonResourceInference implements
    StaticMethodReturnTypeExtension,
    MethodReturnTypeExtension,
    PropertyTypeExtension
{
    /**
     * @param ObjectType|string $name
     * @return bool
     */
    public function shouldHandle(ObjectType|string $name): bool
    {
        $class = is_string($name) ? $name : $name->name;

        return is_a($class, BaseJsonResource::class, true) || is_a($class, ResourceCollection::class, true);
    }

    /**
     * @param PropertyFetchEvent $event
     * @return Type|null
     */
    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        if ($event->name !== 'resource' || !$event->instance instanceof Generic ||
            !$event->instance->isInstanceOf(ResourceCollection::class)) {
            return null;
        }

        return $event->instance->templateTypes[0] ?? null;
    }

    /**
     * @param MethodCallEvent $event
     * @return Type|null
     */
    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        if ($event->name !== 'makeTimestamps') {
            return null;
        }

        $dates = $event->getArg('dates', 0);

        if (!$dates instanceof KeyedArrayType) {
            return null;
        }

        $items = [];
        $dateOnly = $event->getArg('dateOnly', 1);

        foreach ($dates->items as $item) {
            if (!is_string($item->key) || $item->isOptional || $item->shouldUnpack) {
                return null;
            }

            $date = new StringType();

            if ($dateOnly instanceof LiteralBooleanType && $dateOnly->value) {
                $date->setAttribute('format', 'date');
            }

            $items[$item->key] = new ArrayItemType_($item->key, new Union([$date, new NullType()]));

            $items[$item->key . '_locale'] = new ArrayItemType_($item->key . '_locale', new Union([
                new StringType(), new NullType(),
            ]));
        }

        return $items ? new KeyedArrayType(array_values($items)) : null;
    }

    /**
     * @param StaticMethodCallEvent $event
     * @return Type|null
     */
    public function getStaticMethodReturnType(StaticMethodCallEvent $event): ?Type
    {
        if (!is_a($event->callee, BaseJsonResource::class, true)) {
            return null;
        }

        if ($event->name === 'queryCollection') {
            $model = (new ObjectType($event->callee))->getPropertyType('resource', $event->scope);

            return new Generic(AnonymousResourceCollection::class, [
                new Generic(LengthAwarePaginator::class, [new IntegerType(), $model]),
                new ArrayType(),
                new ObjectType($event->callee),
            ]);
        }

        if ($event->name !== 'collection') {
            return null;
        }

        return new Generic(AnonymousResourceCollection::class, [
            $event->getArg('resource', 0), new ArrayType(), new ObjectType($event->callee),
        ]);
    }
}
