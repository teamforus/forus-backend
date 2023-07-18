<?php

namespace App\Exports\BIExporters;

use App\Exports\FundsExport;
use App\Models\Fund;
use App\Scopes\Builders\FundQuery;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundsDetailedExporter extends BaseBIExporter
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        $activeFunds = FundQuery::whereActiveFilter($this->organization->funds())
            ->where('type', Fund::TYPE_BUDGET)
            ->get();

        $data = new FundsExport($activeFunds, true, false);

        return $data->collection()->toArray();
    }
}