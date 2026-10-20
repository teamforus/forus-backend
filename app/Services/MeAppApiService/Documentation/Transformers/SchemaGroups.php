<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;

class SchemaGroups implements DocumentTransformer
{
    /**
     * @param OpenApi $document
     * @param OpenApiContext $context
     * @return void
     */
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $classes = [];

        foreach ($context->references->schemas->items as $class => $references) {
            $classes[$context->references->schemas->uniqueName($class)] = $class;
        }

        foreach ($document->components->schemas as $name => $schema) {
            $class = $classes[$name] ?? $name;

            $group = match (true) {
                is_a($class, FormRequest::class, true) => 'Requests',
                is_a($class, JsonResource::class, true) => 'Resources',
                default => null,
            };

            if ($group) {
                $schema->type->setExtensionProperty('tags', [$group]);
            }
        }
    }
}
