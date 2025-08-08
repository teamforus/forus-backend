<?php

namespace App\Exports\BIExporters;

use App\Exports\FundsExport;
use App\Scopes\Builders\FundQuery;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use Illuminate\Support\Carbon;

class BIFundsExporter extends BaseBIExporter
{
    protected string $key = 'funds';
    protected string $name = 'Financieel overzicht uitgaven';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $activeFunds = FundQuery::whereActiveFilter($this->organization->funds())
            ->where('external', false)
            ->get();

        $data = new FundsExport(
            $activeFunds,
            Carbon::createFromFormat('Y', 2000),
            now()->endOfYear(),
            FundsExport::getExportFieldsRaw(),
        );

        return $data->collection()->toArray();
    }
}
