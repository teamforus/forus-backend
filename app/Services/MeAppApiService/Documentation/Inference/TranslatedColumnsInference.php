<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;

class TranslatedColumnsInference implements MethodReturnTypeExtension
{
    /**
     * @param ObjectType $type
     * @return bool
     */
    public function shouldHandle(ObjectType $type): bool
    {
        return in_array(HasOnDemandTranslations::class, class_uses_recursive($type->name), true);
    }

    /**
     * @param MethodCallEvent $event
     * @return Type|null
     */
    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        if ($event->name !== 'translateColumns') {
            return null;
        }

        $columns = $event->getArg('columns', 0);

        if (!$columns instanceof KeyedArrayType) {
            return null;
        }

        $items = [];

        foreach ($columns->items as $item) {
            if (!is_string($item->key) || $item->shouldUnpack) {
                return null;
            }

            $types = $item->value instanceof Union ? $item->value->types : [$item->value];
            $translatedTypes = [];

            foreach ($types as $type) {
                if (!$type instanceof StringType && !$type instanceof NullType) {
                    return null;
                }

                $translatedTypes[] = $type instanceof NullType ? new NullType() : new StringType();
            }

            $translated = clone $item;
            $translated->value = Union::wrap($translatedTypes);
            $items[] = $translated;
        }

        $result = clone $columns;
        $result->items = $items;

        return $result;
    }
}
