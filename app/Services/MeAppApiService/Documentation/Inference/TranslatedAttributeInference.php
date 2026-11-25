<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use Astrotomic\Translatable\Translatable;
use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Support\InferExtensions\ModelExtension;
use Dedoc\Scramble\Support\Type\MixedType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\Type\UnknownType;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionException;

readonly class TranslatedAttributeInference implements PropertyTypeExtension
{
    /**
     * @param ModelExtension $modelExtension
     */
    public function __construct(private ModelExtension $modelExtension)
    {
    }

    /**
     * @param ObjectType $type
     * @return bool
     */
    public function shouldHandle(ObjectType $type): bool
    {
        return is_a($type->name, Model::class, true)
            && in_array(Translatable::class, class_uses_recursive($type->name), true);
    }

    /**
     * @param PropertyFetchEvent $event
     * @throws ReflectionException
     * @return Type|null
     */
    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        $defaults = (new ReflectionClass($event->getInstance()->name))->getDefaultProperties();
        $attributes = $defaults['translatedAttributes'] ?? [];

        if (!is_array($attributes) || !in_array($event->getName(), $attributes, true)) {
            return null;
        }

        $model = app($event->getInstance()->name);
        $type = $this->modelExtension->getPropertyType($event)
            ?? $this->modelExtension->getPropertyType(new PropertyFetchEvent(
                new ObjectType($model->getTranslationModelName()),
                $event->getName(),
                $event->scope,
            ));

        if (!$type || $type instanceof UnknownType || $type instanceof MixedType) {
            return null;
        }

        $types = $type instanceof Union ? $type->types : [$type];

        foreach ($types as $item) {
            if ($item instanceof NullType) {
                return $type;
            }
        }

        return new Union([...$types, new NullType()]);
    }
}
