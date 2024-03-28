<?php

namespace App\Exports\BIExporters;

use App\Exports\EmployeesExport;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIEmployeesExporter extends BaseBIExporter
{
    protected string $key = 'employees';
    protected string $name = 'Medewerkers';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $query = $this->organization->employees();
        $export = new EmployeesExport($query);

        return $export->collection()->toArray();
    }
}