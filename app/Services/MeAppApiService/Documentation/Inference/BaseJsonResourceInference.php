<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use App\Http\Resources\BaseJsonResource;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\Event\StaticMethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Infer\Extensions\StaticMethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BaseJsonResourceInference implements StaticMethodReturnTypeExtension, MethodReturnTypeExtension
{
    /**
     * @param ObjectType|string $name
     * @return bool
     */
    public function shouldHandle(ObjectType|string $name): bool
    {
        return is_a(is_string($name) ? $name : $name->name, BaseJsonResource::class, true);
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

        foreach ($dates->items as $item) {
            if (!is_string($item->key) || $item->isOptional || $item->shouldUnpack) {
                return null;
            }

            foreach ([$item->key, $item->key . '_locale'] as $field) {
                $items[$field] = new ArrayItemType_($field, Union::wrap([new StringType(), new NullType()]));
            }
        }

        return $items ? new KeyedArrayType(array_values($items)) : null;
    }

    /**
     * @param StaticMethodCallEvent $event
     * @return Type|null
     */
    public function getStaticMethodReturnType(StaticMethodCallEvent $event): ?Type
    {
        if ($event->name !== 'collection') {
            return null;
        }

        return new Generic(AnonymousResourceCollection::class, [
            $event->getArg('resource', 0), new ArrayType(), new ObjectType($event->callee),
        ]);
    }
}
