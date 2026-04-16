<?php

namespace App\Services\BIConnectionService\Exporters;

use App\Exports\Base\BaseExport;
use App\Models\Organization;

abstract class BaseBIExporter
{
    protected string $key;
    protected string $name;
    protected bool $makeExportRowsUnique = true;

    /**
     * @param Organization $organization
     */
    public function __construct(protected Organization $organization)
    {
    }

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
     * @param BaseExport $export
     * @return array
     */
    protected function transformExportRows(BaseExport $export): array
    {
        return $this->transformRowsWithHeadings($export->headings(), $export->collection()->toArray());
    }

    /**
     * @param array $headings
     * @param array $rows
     * @return array
     */
    protected function transformRowsWithHeadings(array $headings, array $rows): array
    {
        if (!$this->makeExportRowsUnique) {
            return array_map(fn (array $row) => array_combine($headings, $row), $rows);
        }

        $uniqueHeadings = $this->makeUniqueHeadings($headings);

        return array_map(fn (array $row) => array_combine($uniqueHeadings, $row), $rows);
    }

    /**
     * @param array $headings
     * @return array
     */
    protected function makeUniqueHeadings(array $headings): array
    {
        $usedHeadings = [];

        return array_map(function (string $heading) use (&$usedHeadings) {
            $suffix = 1;
            $uniqueHeading = $heading;

            while (array_key_exists($uniqueHeading, $usedHeadings)) {
                $suffix++;
                $uniqueHeading = "$heading ($suffix)";
            }

            $usedHeadings[$uniqueHeading] = true;

            return $uniqueHeading;
        }, $headings);
    }
}
