<?php

namespace Tests\Feature\Exports\Stubs;

use App\Models\Organization;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class StubBIExporter extends BaseBIExporter
{
    protected string $key = 'stub';
    protected string $name = 'Stub';

    public function __construct(Organization $organization, bool $makeExportRowsUnique)
    {
        parent::__construct($organization);

        $this->makeExportRowsUnique = $makeExportRowsUnique;
    }

    public function toArray(): array
    {
        return [];
    }

    public function transformRows(array $headings, array $rows): array
    {
        return $this->transformRowsWithHeadings($headings, $rows);
    }
}
