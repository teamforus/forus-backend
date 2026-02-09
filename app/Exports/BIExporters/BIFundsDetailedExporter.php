<?php

namespace App\Exports\BIExporters;

use App\Exports\FundsExportDetailed;
use App\Scopes\Builders\FundQuery;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use Illuminate\Support\Carbon;

class BIFundsDetailedExporter extends BaseBIExporter
{
    protected string $key = 'funds_detailed';
    protected string $name = 'Financieel overzicht tegoeden';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $activeFundsBuilder = FundQuery::whereActiveFilter($this->organization->funds())
            ->where('external', false);

        $export = new FundsExportDetailed(
            $activeFundsBuilder,
            FundsExportDetailed::getExportFieldsRaw($this->organization->hasPayoutFunds()),
            Carbon::createFromFormat('Y', 2000),
            now()->endOfYear(),
        );

        return $export->collection()->toArray();
    }
}
