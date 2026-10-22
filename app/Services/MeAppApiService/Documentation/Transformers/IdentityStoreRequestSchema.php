<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Http\Requests\Api\IdentityStoreRequest;
use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;

class IdentityStoreRequestSchema implements DocumentTransformer
{
    /**
     * @param OpenApi $document
     * @param OpenApiContext $context
     * @return void
     */
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        if ($schema = $document->components->schemas[IdentityStoreRequest::class] ?? null) {
            unset($schema->type->properties['target']);
        }
    }
}
