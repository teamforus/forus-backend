<?php

namespace App\Exports\BIExporters;

use App\Exports\FundsExport;
use App\Models\Fund;
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
        $activeFunds = FundQuery::whereActiveFilter($this->organization->funds())
            ->where('type', Fund::TYPE_BUDGET)
            ->get();

        $data = new FundsExport(
            $activeFunds,
            Carbon::createFromFormat('Y', 2000),
            now()->endOfYear(),
        );

        return $data->collection()->toArray();
    }
}
