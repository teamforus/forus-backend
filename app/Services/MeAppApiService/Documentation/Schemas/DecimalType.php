<?php

namespace App\Services\MeAppApiService\Documentation\Schemas;

use Dedoc\Scramble\Support\Generator\Types\NumberType;

class DecimalType extends NumberType
{
    /**
     * @param int $decimalPlaces
     */
    public function __construct(private readonly int $decimalPlaces)
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    public function serializeAs31(): array
    {
        return [...parent::serializeAs31(), 'multipleOf' => 10 ** -$this->decimalPlaces];
    }
}
