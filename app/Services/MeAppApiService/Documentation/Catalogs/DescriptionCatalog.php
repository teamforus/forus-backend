<?php

namespace App\Services\MeAppApiService\Documentation\Catalogs;

use Closure;
use InvalidArgumentException;

class DescriptionCatalog
{
    private array $entries;

    /**
     * @param string $consumer
     * @param array $catalog
     */
    public function __construct(private readonly string $consumer, array $catalog)
    {
        $this->checkKeys($catalog, ['shared', 'requester', 'provider'], 'catalog');

        if (!in_array($consumer, ['requester', 'provider'], true)) {
            $this->fail('catalog', 'Unknown API consumer.');
        }

        foreach ($catalog as $scope => $section) {
            $this->checkKeys($section, [
                'info', 'fragments', 'parameters', 'responses', 'response_headers', 'endpoints', 'schemas',
            ], $scope);

            foreach ($section as $name => $entries) {
                $this->map($entries, "$scope.$name");
            }
        }

        $this->entries = array_replace_recursive($catalog['shared'] ?? [], $catalog[$consumer] ?? []);
        $this->checkKeys($this->section('fragments'), ['token_state'], 'fragments');

        foreach ($this->section('fragments') as $name => $text) {
            $this->render($text, [], "fragments.$name");
        }
    }

    /**
     * @param string $consumer
     * @return self
     */
    public static function load(string $consumer): self
    {
        return new self($consumer, require dirname(__DIR__) . '/descriptions.php');
    }

    /**
     * @param string $section
     * @return array
     */
    public function section(string $section): array
    {
        return $this->map($this->entries[$section] ?? [], $section);
    }

    /**
     * @param mixed $value
     * @param string $path
     * @return array
     */
    public function map(mixed $value, string $path): array
    {
        if (!is_array($value)) {
            $this->fail($path, 'Expected a map.');
        }

        return $value;
    }

    /**
     * @param array $components
     * @param array $values
     * @param array $variants
     * @return array
     */
    public function describeComponents(array $components, array $values, array $variants): array
    {
        $entries = $this->section('schemas');
        $resolved = [];
        $resolving = [];
        $resolve = function (string $name) use (
            &$resolve,
            &$resolved,
            &$resolving,
            $components,
            $entries,
            $values,
            $variants,
        ): mixed {
            if (!array_key_exists($name, $components)) {
                $this->fail("schemas.$name", 'Schema is not present in this API. Validate a full export.');
            }

            if (!array_key_exists($name, $entries)) {
                return $components[$name];
            }

            if (isset($resolved[$name])) {
                return $resolved[$name];
            }

            if (isset($resolving[$name])) {
                $this->fail("schemas.$name", 'Circular nested descriptions. Describe the referenced schema directly.');
            }

            $resolving[$name] = true;
            $resolved[$name] = $this->describeSchema(
                (array) $components[$name],
                $this->map($entries[$name], "schemas.$name"),
                $resolve,
                $values,
                "schemas.$name",
                $variants[$name] ?? [],
            );
            unset($resolving[$name]);

            return $resolved[$name];
        };

        foreach (array_keys($entries) as $name) {
            $resolve($name);
        }

        return array_replace($components, $resolved);
    }

    /**
     * @param string $name
     * @return string
     */
    public function fragment(string $name): string
    {
        return $this->render($this->section('fragments')[$name] ?? null, [], "fragments.$name");
    }

    /**
     * @param mixed $text
     * @param array $values
     * @param string $path
     * @return string
     */
    public function render(mixed $text, array $values, string $path): string
    {
        if (!is_string($text)) {
            $this->fail($path, 'Expected text.');
        }

        return preg_replace_callback('/\{([a-z_][a-z0-9_]*)}/i', function (array $match) use ($values, $path): string {
            if (!array_key_exists($match[1], $values) || !is_scalar($values[$match[1]])) {
                $this->fail($path, "Unknown placeholder {$match[0]}.");
            }

            return (string) $values[$match[1]];
        }, $text);
    }

    /**
     * @param mixed $entries
     * @param array $keys
     * @param string $path
     * @return void
     */
    public function checkKeys(mixed $entries, array $keys, string $path): void
    {
        if (!is_array($entries)) {
            $this->fail($path, 'Expected a map.');
        }

        foreach (array_keys($entries) as $key) {
            if (!in_array($key, $keys, true)) {
                $this->fail("$path.$key", 'Unknown text location.');
            }
        }
    }

    /**
     * @param array $schema
     * @param array $entries
     * @param array|Closure $components
     * @param array $values
     * @param string $path
     * @param array $variants
     * @return array
     */
    public function describeSchema(
        array $schema,
        array $entries,
        array|Closure $components,
        array $values,
        string $path,
        array $variants = [],
    ): array {
        $this->checkKeys($entries, ['title', 'description', 'fields', 'variants'], $path);

        foreach (['fields', 'variants'] as $key) {
            if (array_key_exists($key, $entries)) {
                $this->map($entries[$key], "$path.$key");
            }
        }

        foreach (['title', 'description'] as $key) {
            if (array_key_exists($key, $entries)) {
                $schema[$key] = $this->render($entries[$key], $values, "$path.$key");
            }
        }

        foreach ($this->map($entries['fields'] ?? [], "$path.fields") as $field => $text) {
            $fieldPath = "$path.fields.$field";
            if (!$this->describeField($schema, $field, $text, $components, $values, $fieldPath)) {
                $this->fail($fieldPath, 'Field does not exist in the generated schema.');
            }
        }

        foreach ($this->map($entries['variants'] ?? [], "$path.variants") as $name => $text) {
            $index = $variants[$name] ?? null;

            if ($index === null || !isset($schema['anyOf'][$index])) {
                $this->fail("$path.variants.$name", 'Unknown schema variant.');
            }

            $schema['anyOf'][$index] = $this->describeSchema(
                $schema['anyOf'][$index],
                $this->map($text, "$path.variants.$name"),
                $components,
                $values,
                "$path.variants.$name",
            );
        }

        return $schema;
    }

    /**
     * @param array $schema
     * @param string $field
     * @param mixed $text
     * @param array|Closure $components
     * @param array $values
     * @param string $path
     * @return bool
     */
    public function describeField(
        array &$schema,
        string $field,
        mixed $text,
        array|Closure $components,
        array $values,
        string $path,
    ): bool {
        if (str_starts_with($field, 'anyOf[].')) {
            $matched = false;

            foreach ($schema['anyOf'] ?? [] as $index => $branch) {
                if ($this->describeField($branch, substr($field, 8), $text, $components, $values, $path)) {
                    $schema['anyOf'][$index] = $branch;
                    $matched = true;
                }
            }

            return $matched;
        }

        $segments = explode('.', str_replace('[]', '.[]', $field));

        return !in_array('', $segments, true) &&
            $this->writeField($schema, $segments, $this->render($text, $values, $path), $components, $path);
    }

    /**
     * @param string $path
     * @param string $message
     * @return never
     */
    public function fail(string $path, string $message): never
    {
        throw new InvalidArgumentException("Mobile documentation [$this->consumer] $path: $message");
    }

    /**
     * @param array $schema
     * @param array $segments
     * @param string $text
     * @param array|Closure $components
     * @param string $path
     * @param array $visited
     * @return bool
     */
    private function writeField(
        array &$schema,
        array $segments,
        string $text,
        array|Closure $components,
        string $path,
        array $visited = [],
    ): bool {
        if (!$segments) {
            $schema['description'] = $text;

            return true;
        }

        if (isset($schema['$ref'])) {
            $reference = $schema['$ref'];
            $visit = $reference . ':' . implode('.', $segments);
            $prefix = '#/components/schemas/';
            $name = str_replace(['~1', '~0'], ['/', '~'], substr($reference, strlen($prefix)));

            if (!str_starts_with($reference, $prefix) || isset($visited[$visit])) {
                $this->fail($path, 'Cannot resolve the schema reference.');
            }

            $visited[$visit] = true;
            $target = $components instanceof Closure ? $components($name) : ($components[$name] ?? null);

            if ($target === null) {
                $this->fail($path, 'Cannot resolve the schema reference.');
            }

            $copy = $schema;
            unset($copy['$ref']);
            $copy = array_replace((array) $target, $copy);

            if (!$this->writeField($copy, $segments, $text, $components, $path, $visited)) {
                return false;
            }

            $schema = $copy;

            return true;
        }

        $segment = array_shift($segments);

        if (isset($schema['properties'][$segment]) && is_array($schema['properties'][$segment])) {
            return $this->writeField($schema['properties'][$segment], $segments, $text, $components, $path, $visited);
        }

        $child = match ($segment) {
            '[]' => 'items',
            '*' => 'additionalProperties',
            default => null,
        };

        if ($child && isset($schema[$child]) && is_array($schema[$child])) {
            return $this->writeField($schema[$child], $segments, $text, $components, $path, $visited);
        }

        foreach (['anyOf', 'oneOf', 'allOf'] as $composition) {
            $matches = [];

            foreach ($schema[$composition] ?? [] as $index => $branch) {
                if (is_array($branch) &&
                    $this->writeField($branch, [$segment, ...$segments], $text, $components, $path, $visited)) {
                    $matches[$index] = $branch;
                }
            }

            if (count($matches) > 1) {
                $this->fail($path, 'Field matches multiple schema branches. Use a named variant.');
            }

            if ($matches) {
                $schema[$composition][array_key_first($matches)] = reset($matches);

                return true;
            }
        }

        return false;
    }
}
