<?php

namespace App\Services\MeAppApiService\Documentation\Schemas;

use Dedoc\Scramble\Support\Generator\Schema;

class DescribedSchema extends Schema
{
    /**
     * @param Schema $source
     * @param array $schema
     */
    public function __construct(Schema $source, private readonly array $schema)
    {
        $this->type = $source->type;
    }

    /**
     * @return array
     */
    public function serializeAs31(): array
    {
        return $this->schema;
    }
}
