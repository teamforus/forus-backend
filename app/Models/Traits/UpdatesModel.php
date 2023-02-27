<?php

namespace App\Models\Traits;

/**
 * @mixin \Eloquent
 * @noinspection PhpUnused
 */
trait UpdatesModel
{
    /**
     * @param array $attributes
     * @param array $options
     * @return static
     */
    public function updateModel(array $attributes = [], array $options = []): static
    {
        $this->update($attributes, $options);

        return $this;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return static
     */
    public function updateModelValue(string $attribute, mixed $value): static
    {
        return $this->updateModel([
            $attribute => $value,
        ]);
    }
}