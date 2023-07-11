<?php

namespace App\Exports\BIExporters;

use App\Exports\FundIdentitiesExport;
use App\Models\Fund;
use App\Searches\Sponsor\FundIdentitiesSearch;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundIdentitiesExporter extends BaseBIExporter
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->organization->funds->reduce(function (array $list, Fund $fund) {
            $search = new FundIdentitiesSearch([], $fund);
            $fields = array_keys(FundIdentitiesExport::getExportFieldsRaw());
            $data = new FundIdentitiesExport($search->get(), $fields);

            return array_merge($list, $data->collection()->toArray());
        }, []);
    }
}