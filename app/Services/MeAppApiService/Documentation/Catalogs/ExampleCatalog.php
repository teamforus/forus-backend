<?php

namespace App\Services\MeAppApiService\Documentation\Catalogs;

use InvalidArgumentException;
use JsonException;

class ExampleCatalog
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

        foreach ($catalog as $scope => $entries) {
            $this->validateScope($entries, $scope);
        }

        $this->entries = $catalog['shared'] ?? [];

        foreach ($catalog[$consumer]['endpoints'] ?? [] as $name => $entry) {
            $target = &$this->entries['endpoints'][$name];
            $target ??= [];

            if (array_key_exists('request', $entry)) {
                $target['request'] = array_replace($target['request'] ?? [], $entry['request']);
            }

            foreach ($entry['responses'] ?? [] as $status => $mediaTypes) {
                $target['responses'][$status] = array_replace($target['responses'][$status] ?? [], $mediaTypes);
            }
        }

        unset($target);

        foreach ($catalog[$consumer]['schemas'] ?? [] as $name => $entry) {
            if (array_key_exists('examples', $entry)) {
                $this->entries['schemas'][$name]['examples'] = $entry['examples'];
            }

            $this->entries['schemas'][$name]['variants'] = array_replace(
                $this->entries['schemas'][$name]['variants'] ?? [],
                $entry['variants'] ?? [],
            );
        }
    }

    /**
     * @param string $consumer
     * @return self
     */
    public static function load(string $consumer): self
    {
        return new self($consumer, require dirname(__DIR__) . '/examples.php');
    }

    /**
     * @param string $section
     * @return array
     */
    public function section(string $section): array
    {
        return $this->entries[$section] ?? [];
    }

    /**
     * @param string $path
     * @param string $message
     * @return never
     */
    public function fail(string $path, string $message): never
    {
        throw new InvalidArgumentException("Mobile examples [$this->consumer] $path: $message");
    }

    /**
     * @param mixed $entries
     * @param string $path
     * @return void
     */
    private function validateScope(mixed $entries, string $path): void
    {
        $this->checkKeys($entries, ['endpoints', 'schemas'], $path);

        foreach ($entries as $section => $items) {
            $this->map($items, "$path.$section");
        }

        foreach ($entries['endpoints'] ?? [] as $name => $entry) {
            $entryPath = "$path.endpoints.$name";
            $this->checkKeys($entry, ['request', 'responses'], $entryPath);

            if (array_key_exists('request', $entry)) {
                $this->validateMediaTypes($entry['request'], "$entryPath.request");
            }

            if (array_key_exists('responses', $entry)) {
                foreach ($this->map($entry['responses'], "$entryPath.responses") as $status => $mediaTypes) {
                    $this->validateMediaTypes($mediaTypes, "$entryPath.responses.$status");
                }
            }
        }

        foreach ($entries['schemas'] ?? [] as $name => $entry) {
            $entryPath = "$path.schemas.$name";
            $this->checkKeys($entry, ['examples', 'variants'], $entryPath);

            if (array_key_exists('examples', $entry)) {
                $this->validateExamples($entry['examples'], "$entryPath.examples");
            }

            if (array_key_exists('variants', $entry)) {
                foreach ($this->map($entry['variants'], "$entryPath.variants") as $variant => $examples) {
                    $this->validateExamples($examples, "$entryPath.variants.$variant");
                }
            }
        }
    }

    /**
     * @param mixed $entries
     * @param string $path
     * @return void
     */
    private function validateMediaTypes(mixed $entries, string $path): void
    {
        foreach ($this->map($entries, $path) as $mediaType => $examples) {
            $this->validateExamples($examples, "$path.$mediaType");
        }
    }

    /**
     * @param mixed $examples
     * @param string $path
     * @return void
     */
    private function validateExamples(mixed $examples, string $path): void
    {
        foreach ($this->map($examples, $path) as $name => $value) {
            if (!is_string($name) || trim($name) === '') {
                $this->fail($path, 'Examples must have nonempty string names.');
            }

            try {
                json_encode($value, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $this->fail("$path.$name", 'Example is not JSON serializable: ' . $exception->getMessage());
            }
        }
    }

    /**
     * @param mixed $entries
     * @param array $allowed
     * @param string $path
     * @return void
     */
    private function checkKeys(mixed $entries, array $allowed, string $path): void
    {
        foreach (array_keys($this->map($entries, $path)) as $key) {
            if (!in_array($key, $allowed, true)) {
                $this->fail("$path.$key", 'Unknown catalog key.');
            }
        }
    }

    /**
     * @param mixed $value
     * @param string $path
     * @return array
     */
    private function map(mixed $value, string $path): array
    {
        if (!is_array($value)) {
            $this->fail($path, 'Expected a map.');
        }

        return $value;
    }
}
