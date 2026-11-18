<?php

namespace App\Services\MeAppApiService\Documentation\Schemas;

use Dedoc\Scramble\Support\Generator\Schema;

class DescribedSchema extends Schema
{
    /**
     * @param Schema $source
     * @param array $schema31
     * @param array $schema32
     */
    public function __construct(Schema $source, private readonly array $schema31, private readonly array $schema32)
    {
        $this->type = $source->type;
    }

    /**
     * @return array
     */
    public function serializeAs31(): array
    {
        return $this->schema31;
    }

    /**
     * @return array
     */
    public function serializeAs32(): array
    {
        return $this->schema32;
    }
}
