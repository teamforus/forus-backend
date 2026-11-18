<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Models\Permission;
use App\Services\MeAppApiService\Documentation\Catalogs\DescriptionCatalog;
use App\Services\MeAppApiService\Documentation\Schemas\DescribedSchema;
use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\Exceptions\OpenApiReferenceTargetNotFoundException;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\MediaType;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Illuminate\Support\Facades\Config;

class DocumentationText implements DocumentTransformer
{
    private DescriptionCatalog $catalog;
    private array $components31;
    private array $components32;
    private array $targets = [];

    /**
     * @param OpenApi $document
     * @param OpenApiContext $context
     * @return void
     */
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $this->catalog = DescriptionCatalog::load($context->config->name);
        $this->targets = [];

        $values = [
            'scan_permission' => Permission::SCAN_VOUCHERS,
            'maximum_extra_cash' => number_format(Config::get('forus.transactions.max_amount_extra_cash'), 2, '.', ''),
        ];

        $info = $this->catalog->section('info');
        $this->catalog->checkKeys($info, ['title', 'description'], 'info');

        foreach ($info as $key => $text) {
            $document->info->{$key} = $this->catalog->render($text, $values, "info.$key");
        }

        $schemas = [];
        $variants = [];

        foreach ($document->components->schemas as $class => $schema) {
            $name = $document->components->uniqueSchemaName($class);
            $schemas[$name] = $class;
            $variants[$name] = $schema->type->getAttribute('description_variants', []);
        }

        $this->refreshComponents($document);
        $this->components31 = $this->catalog->describeComponents($this->components31, $values, $variants);
        $this->components32 = $this->catalog->describeComponents($this->components32, $values, $variants);

        foreach (array_keys($this->catalog->section('schemas')) as $name) {
            $class = $schemas[$name];
            $document->components->schemas[$class] = new DescribedSchema(
                $document->components->schemas[$class],
                $this->components31[$name],
                $this->components32[$name],
            );
        }

        $endpoints = $this->catalog->section('endpoints');
        $appliedEndpoints = [];

        foreach ($endpoints as $name => $entry) {
            $this->catalog->checkKeys($entry, [
                'summary', 'description', 'parameters', 'request', 'responses',
            ], "endpoints.$name");
        }

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                $name = $operation->getAttribute('mobile_route_name');
                $operationValues = [...$values, ...$operation->getAttribute('documentation_values', [])];

                $this->applyOperation($operation, [
                    'parameters' => $this->catalog->section('parameters'),
                    'responses' => $this->catalog->section('responses'),
                    'response_headers' => $this->catalog->section('response_headers'),
                ], $operationValues, 'defaults', false);

                if (isset($endpoints[$name])) {
                    $this->applyOperation($operation, $endpoints[$name], $operationValues, "endpoints.$name", true);
                    $appliedEndpoints[$name] = true;
                }
            }
        }

        foreach (array_diff_key($endpoints, $appliedEndpoints) as $name => $entry) {
            $this->catalog->fail("endpoints.$name", 'Route is not present in this document. Validate a full export.');
        }

        foreach ($this->catalog->section('parameters') as $location => $parameters) {
            foreach ($parameters as $name => $text) {
                $this->requireDefaultTarget("parameters.$location.$name");
            }
        }

        foreach (['responses', 'response_headers'] as $section) {
            foreach ($this->catalog->section($section) as $name => $text) {
                $this->requireDefaultTarget("$section.$name");
            }
        }
    }

    /**
     * @param string $path
     * @return void
     */
    private function requireDefaultTarget(string $path): void
    {
        if (!isset($this->targets[$path])) {
            $this->catalog->fail($path, 'Default text matches no generated target. Validate a full export.');
        }
    }

    /**
     * @param OpenApi $document
     * @return void
     */
    private function refreshComponents(OpenApi $document): void
    {
        $this->components31 = $document->components->serializeAs31()['schemas'] ?? [];
        $this->components32 = $document->components->serializeAs32()['schemas'] ?? [];
    }

    /**
     * @param Operation $operation
     * @param array $entry
     * @param array $values
     * @param string $path
     * @param bool $strict
     * @return void
     */
    private function applyOperation(Operation $operation, array $entry, array $values, string $path, bool $strict): void
    {
        foreach (['parameters', 'responses', 'response_headers', 'request'] as $key) {
            if (array_key_exists($key, $entry)) {
                $this->catalog->map($entry[$key], "$path.$key");
            }
        }

        foreach (['summary', 'description'] as $key) {
            if (array_key_exists($key, $entry)) {
                $operation->{$key} = $this->catalog->render($entry[$key], $values, "$path.$key");
            }
        }

        $this->applyParameters(
            $operation,
            $this->catalog->map($entry['parameters'] ?? [], "$path.parameters"),
            $values,
            "$path.parameters",
            $strict,
        );

        if (isset($entry['request'])) {
            if (!$operation->requestBodyObject) {
                $this->catalog->fail("$path.request", 'Operation has no request body.');
            }

            $body = $operation->requestBodyObject;
            $body = clone ($body instanceof Reference ? $body->resolve() : $body);
            $this->applyContent($body, $entry['request'], $values, "$path.request");
            $operation->requestBodyObject = $body;
        }

        $responses = [];

        foreach ($operation->responses as $index => $response) {
            try {
                $resolved = $response instanceof Reference ? $response->resolve() : $response;
            } catch (OpenApiReferenceTargetNotFoundException) {
                // Scramble also skips references removed by its response cleanup transformer.
                continue;
            }

            $responses[(string) $resolved->code] = [$index, $resolved];
            $this->targets["responses.$resolved->code"] = true;

            foreach (array_keys($resolved->headers) as $name) {
                $this->targets["response_headers.$name"] = true;
            }
        }

        $headers = $this->catalog->map($entry['response_headers'] ?? [], "$path.response_headers");

        foreach ($responses as $code => [$index, $response]) {
            if (!array_intersect_key($response->headers, $headers)) {
                continue;
            }

            $response = clone $response;
            $this->applyHeaders($response, $headers, $values, "$path.responses.$code", false);
            $operation->responses[$index] = $response;
            $responses[$code] = [$index, $response];
        }

        foreach ($this->catalog->map($entry['responses'] ?? [], "$path.responses") as $code => $text) {
            if (!isset($responses[$code])) {
                if ($strict) {
                    $this->catalog->fail("$path.responses.$code", 'Response status does not exist.');
                }

                continue;
            }

            [$index, $response] = $responses[$code];
            $response = clone $response;
            $this->applyContent($response, $text, $values, "$path.responses.$code");
            $operation->responses[$index] = $response;
        }
    }

    /**
     * @param Operation $operation
     * @param array $entries
     * @param array $values
     * @param string $path
     * @param bool $strict
     * @return void
     */
    private function applyParameters(
        Operation $operation,
        array $entries,
        array $values,
        string $path,
        bool $strict,
    ): void {
        $this->catalog->checkKeys($entries, ['query', 'header', 'path', 'cookie'], $path);

        foreach ($entries as $location => $parameters) {
            foreach ($this->catalog->map($parameters, "$path.$location") as $name => $text) {
                $found = false;

                foreach ($operation->parameters as $index => $parameter) {
                    $parameter = $parameter instanceof Reference ? $parameter->resolve() : $parameter;

                    if ($parameter->in !== $location || $parameter->name !== $name) {
                        continue;
                    }

                    $found = true;
                    $this->targets["parameters.$location.$name"] = true;
                    $parameter = clone $parameter;
                    $parameter->description = $this->catalog->render($text, $values, "$path.$location.$name");
                    $operation->parameters[$index] = $parameter;
                }

                if (!$found && $strict) {
                    $this->catalog->fail("$path.$location.$name", 'Parameter does not exist.');
                }
            }
        }
    }

    /**
     * @param Response|RequestBodyObject $target
     * @param mixed $entry
     * @param array $values
     * @param string $path
     * @return void
     */
    private function applyContent(Response|RequestBodyObject $target, mixed $entry, array $values, string $path): void
    {
        $this->catalog->checkKeys($entry, [
            'description', 'fields', ...($target instanceof Response ? ['headers'] : []),
        ], $path);

        if (array_key_exists('description', $entry)) {
            $target->description = $this->catalog->render($entry['description'], $values, "$path.description");
        }

        if (array_key_exists('fields', $entry)) {
            $this->applyBodyFields($target, $this->catalog->map($entry['fields'], "$path.fields"), $values, $path);
        }

        if ($target instanceof Response && array_key_exists('headers', $entry)) {
            $this->applyHeaders($target, $this->catalog->map($entry['headers'], "$path.headers"), $values, $path, true);
        }
    }

    /**
     * @param Response $response
     * @param array $entries
     * @param array $values
     * @param string $path
     * @param bool $strict
     * @return void
     */
    private function applyHeaders(Response $response, array $entries, array $values, string $path, bool $strict): void
    {
        foreach ($entries as $name => $text) {
            if (!isset($response->headers[$name])) {
                if ($strict) {
                    $this->catalog->fail("$path.headers.$name", 'Response header does not exist.');
                }

                continue;
            }

            $header = $response->headers[$name];
            $header = clone ($header instanceof Reference ? $header->resolve() : $header);
            $header->description = $this->catalog->render($text, $values, "$path.headers.$name");
            $response->headers[$name] = $header;
        }
    }

    /**
     * @param Response|RequestBodyObject $target
     * @param array $fields
     * @param array $values
     * @param string $path
     * @return void
     */
    private function applyBodyFields(Response|RequestBodyObject $target, array $fields, array $values, string $path): void
    {
        $missing31 = $missing32 = $fields;

        foreach ($target->content as $mediaType => $content) {
            if ($content instanceof Reference && $content->referenceType === 'mediaTypes') {
                $content = $content->resolve();
            }

            $content = $content instanceof MediaType ? clone $content : new MediaType(schema: $content);
            $source = $content->schema;

            if (!$source) {
                continue;
            }

            $source = $source instanceof Reference ? Schema::fromType($source) : $source;
            $schema31 = (array) $source->serializeAs31();
            $schema32 = (array) $source->serializeAs32();
            $matched = false;

            foreach ($fields as $field => $text) {
                $fieldPath = "$path.fields.$field";

                if ($this->catalog->describeField($schema31, $field, $text, $this->components31, $values, $fieldPath)) {
                    unset($missing31[$field]);
                    $matched = true;
                }

                if ($this->catalog->describeField($schema32, $field, $text, $this->components32, $values, $fieldPath)) {
                    unset($missing32[$field]);
                    $matched = true;
                }
            }

            if ($matched) {
                $content->schema = new DescribedSchema($source, $schema31, $schema32);
                $target->addContent($mediaType, $content);
            }
        }

        foreach (array_keys($missing31 + $missing32) as $field) {
            $this->catalog->fail("$path.fields.$field", 'Field does not exist in any body content schema.');
        }
    }
}
