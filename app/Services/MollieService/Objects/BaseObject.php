<?php

namespace App\Services\MollieService\Objects;


class BaseObject
{
    /**
     * @var array
     */
    public array $data;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }

        $this->data = $attributes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
