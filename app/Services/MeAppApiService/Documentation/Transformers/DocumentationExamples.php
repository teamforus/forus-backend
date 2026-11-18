<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Services\MeAppApiService\Documentation\Catalogs\ExampleCatalog;
use App\Services\MeAppApiService\Documentation\Schemas\DescribedSchema;
use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\Exceptions\OpenApiReferenceTargetNotFoundException;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Example;
use Dedoc\Scramble\Support\Generator\MediaType;
use Dedoc\Scramble\Support\Generator\MissingValue;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;

class DocumentationExamples implements DocumentTransformer
{
    private ExampleCatalog $catalog;

    /**
     * @param OpenApi $document
     * @param OpenApiContext $context
     * @return void
     */
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $this->catalog = ExampleCatalog::load($context->config->name);
        $schemas = [];

        foreach ($document->components->schemas as $class => $schema) {
            $schemas[$document->components->uniqueSchemaName($class)] = $class;
        }

        foreach ($this->catalog->section('schemas') as $name => $entry) {
            if (!isset($schemas[$name])) {
                $this->catalog->fail("schemas.$name", 'Schema is not present in this API. Validate a full export.');
            }

            $class = $schemas[$name];
            $schema = $document->components->schemas[$class];
            $variants = $schema->type->getAttribute('description_variants', []);

            $document->components->schemas[$class] = new DescribedSchema(
                $schema,
                $this->applySchema((array) $schema->serializeAs31(), $entry, $variants, "schemas.$name"),
                $this->applySchema((array) $schema->serializeAs32(), $entry, $variants, "schemas.$name"),
            );
        }

        $endpoints = $this->catalog->section('endpoints');
        $applied = [];

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                $name = $operation->getAttribute('mobile_route_name');

                if (isset($endpoints[$name])) {
                    $this->applyOperation($operation, $endpoints[$name], "endpoints.$name");
                    $applied[$name] = true;
                }
            }
        }

        foreach (array_diff_key($endpoints, $applied) as $name => $entry) {
            $this->catalog->fail("endpoints.$name", 'Route is not present in this document. Validate a full export.');
        }
    }

    /**
     * @param Operation $operation
     * @param array $entry
     * @param string $path
     * @return void
     */
    private function applyOperation(Operation $operation, array $entry, string $path): void
    {
        if (array_key_exists('request', $entry)) {
            $body = $operation->requestBodyObject;

            if (!$body) {
                $this->catalog->fail("$path.request", 'Operation has no request body.');
            }

            $body = clone ($body instanceof Reference ? $body->resolve() : $body);
            $this->applyContent($body, $entry['request'], "$path.request");
            $operation->requestBodyObject = $body;
        }

        $responses = [];

        foreach ($operation->responses as $index => $response) {
            try {
                $response = $response instanceof Reference ? $response->resolve() : $response;
            } catch (OpenApiReferenceTargetNotFoundException) {
                // Scramble also skips references removed by its response cleanup transformer.
                continue;
            }

            $responses[(string) ($response->code ?: 'default')] = [$index, $response];
        }

        foreach ($entry['responses'] ?? [] as $code => $mediaTypes) {
            if (!isset($responses[$code])) {
                $this->catalog->fail("$path.responses.$code", 'Response status does not exist.');
            }

            [$index, $response] = $responses[$code];
            $response = clone $response;
            $this->applyContent($response, $mediaTypes, "$path.responses.$code");
            $operation->responses[$index] = $response;
        }
    }

    /**
     * @param RequestBodyObject|Response $target
     * @param array $entries
     * @param string $path
     * @return void
     */
    private function applyContent(RequestBodyObject|Response $target, array $entries, string $path): void
    {
        foreach ($entries as $mediaType => $examples) {
            if (!array_key_exists($mediaType, $target->content)) {
                $this->catalog->fail("$path.$mediaType", 'Media type does not exist.');
            }

            $content = $target->content[$mediaType];

            if ($content instanceof Reference && $content->referenceType === 'mediaTypes') {
                $content = $content->resolve();
            }

            $content = $content instanceof MediaType ? clone $content : new MediaType(schema: $content);
            $content->setExample(new MissingValue());
            $content->setExamples(array_map(
                static fn (mixed $value): Example => new Example(value: $value),
                $examples,
            ));
            $target->addContent($mediaType, $content);
        }
    }

    /**
     * @param array $schema
     * @param array $entry
     * @param array $variants
     * @param string $path
     * @return array
     */
    private function applySchema(array $schema, array $entry, array $variants, string $path): array
    {
        if (array_key_exists('examples', $entry)) {
            unset($schema['example'], $schema['examples'], $schema['x-examples']);

            if ($entry['examples']) {
                $schema['x-examples'] = array_map(
                    static fn (mixed $value): array => ['value' => $value],
                    $entry['examples'],
                );
            }
        }

        foreach ($entry['variants'] ?? [] as $name => $examples) {
            $index = $variants[$name] ?? null;

            if ($index === null || !isset($schema['anyOf'][$index])) {
                $this->catalog->fail("$path.variants.$name", 'Unknown schema variant.');
            }

            unset(
                $schema['anyOf'][$index]['example'],
                $schema['anyOf'][$index]['examples'],
                $schema['anyOf'][$index]['x-examples'],
            );

            if ($examples) {
                $schema['anyOf'][$index]['examples'] = array_values($examples);
            }
        }

        return $schema;
    }
}
