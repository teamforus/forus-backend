<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use Dedoc\Scramble\Infer\Extensions\Event\ReferenceResolutionEvent;
use Dedoc\Scramble\Infer\Extensions\TypeResolverExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;

class ArraySpreadInference implements TypeResolverExtension
{
    /**
     * @param ReferenceResolutionEvent $event
     * @return Type|null
     */
    public function resolve(ReferenceResolutionEvent $event): ?Type
    {
        $item = $event->type;

        if (!$item instanceof ArrayItemType_ || !$item->shouldUnpack || !$item->value instanceof Union) {
            return null;
        }

        $branches = [];

        foreach ($item->value->types as $type) {
            if (!$type instanceof KeyedArrayType || $type->isList) {
                return null;
            }

            $fields = [];

            foreach ($type->items as $field) {
                if (!is_string($field->key) || $field->isOptional || $field->shouldUnpack ||
                    ($field->keyType && (!$field->keyType instanceof LiteralStringType ||
                        $field->keyType->value !== $field->key))) {
                    return null;
                }

                $fields[$field->key] = $field;
            }

            $branches[] = $fields;
        }

        if (count($branches) < 2) {
            return null;
        }

        $first = $branches[0];

        foreach ($branches as $branch) {
            if (array_diff_key($first, $branch) || array_diff_key($branch, $first)) {
                return null;
            }
        }

        $fields = [];

        foreach ($first as $key => $field) {
            $merged = clone $field;

            $merged->value = Union::wrap(array_map(
                static fn (array $branch): Type => $branch[$key]->value,
                $branches,
            ));

            $fields[] = $merged;
        }

        $resolved = clone $item;
        $resolved->value = (new KeyedArrayType($fields, isList: false))->mergeAttributes($item->value->attributes());

        return $resolved;
    }
}
