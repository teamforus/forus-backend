<?php

namespace App\Services\BIConnectionService\Exporters;

use App\Models\Organization;

abstract class BaseBIExporter
{
    protected string $key;
    protected string $name;

    /**
     * @param Organization $organization
     */
    public function __construct(protected Organization $organization) {}

    /**
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param array $data
     * @param array $fields
     * @return array
     */
    protected function transformKeys(array $data, array $fields): array
    {
        return array_map(function ($row) use ($fields) {
            return array_reduce(array_keys($row), fn($obj, $key) => array_merge($obj, [
                $fields[$key] => (string) $row[$key],
            ]), []);
        }, $data);
    }
}