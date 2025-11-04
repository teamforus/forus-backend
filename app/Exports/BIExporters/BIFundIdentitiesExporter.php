<?php

namespace App\Exports\BIExporters;

use App\Exports\FundIdentitiesExport;
use App\Models\Fund;
use App\Searches\Sponsor\FundIdentitiesSearch;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundIdentitiesExporter extends BaseBIExporter
{
    protected string $key = 'fund_identities';
    protected string $name = 'Aanvragers';

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->organization->funds->reduce(function (array $list, Fund $fund) {
            $search = new FundIdentitiesSearch([], $fund);
            $export = new FundIdentitiesExport($search->query(), FundIdentitiesExport::getExportFieldsRaw());

            return array_merge($list, $export->collection()->toArray());
        }, []);
    }
}
