<?php

namespace App\Services\BIConnectionService;

use App\Exports\BIExporters\BIEmployeesExporter;
use App\Exports\BIExporters\BIFundIdentitiesExporter;
use App\Exports\BIExporters\BIFundProviderFinancesExporter;
use App\Exports\BIExporters\BIFundProvidersExporter;
use App\Exports\BIExporters\BIFundsDetailedExporter;
use App\Exports\BIExporters\BIFundsExporter;
use App\Exports\BIExporters\BIReimbursementsExporter;
use App\Exports\BIExporters\BIVouchersExporter;
use App\Exports\BIExporters\BIVoucherTransactionBulksExporter;
use App\Exports\BIExporters\BIVoucherTransactionsExporter;
use App\Models\Organization;
use App\Scopes\Builders\BIConnectionQuery;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BIConnectionService
{
    /**
     * @param Organization $organization
     */
    public function __construct(
        protected Organization $organization,
    ){}

    /**
     * @param Request $request
     * @return BIConnectionService|null
     */
    public static function getBIConnectionFromRequest(Request $request): ?static
    {
        $organization = Organization::whereHas('bi_connection', function(Builder $builder) use ($request) {
            $builder->where(fn (Builder $q) => BIConnectionQuery::whereValidToken(
                $q, $request, BIConnection::AUTH_TYPE_PARAMETER
            ));

            $builder->orWhere(fn (Builder $q) => BIConnectionQuery::whereValidToken(
                $q, $request, BIConnection::AUTH_TYPE_HEADER
            ));
        })->first();

        if ($organization) {
            return new static($organization);
        }

        return null;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDataArray(): array
    {
        $exporters = $this->getData();
        $dataTypes = $this->getOrganization()->bi_connection->data_types;

        return array_reduce(
            $exporters,
            fn ($list, BaseBIExporter $exporter) => in_array($exporter->getKey(), $dataTypes)
                ? array_merge($list, [$exporter->getName() => $exporter->toArray()])
                : $list,
            []
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getData(): array
    {
        return [
            new BIVouchersExporter('Tegoeden', $this->organization),
            new BIReimbursementsExporter('Declaraties', $this->organization),
            new BIEmployeesExporter('Medewerkers', $this->organization),
            new BIFundsExporter('Financieel overzicht uitgaven', $this->organization),
            new BIFundsDetailedExporter('Financieel overzicht tegoeden', $this->organization),
            new BIFundProvidersExporter('Aanbieders', $this->organization),
            new BIFundIdentitiesExporter('Aanvragers', $this->organization),
            new BIFundProviderFinancesExporter('Aanbieder transacties', $this->organization),
            new BIVoucherTransactionsExporter('Transacties vanuit tegoeden', $this->organization),
            new BIVoucherTransactionBulksExporter('Bulk transacties vanuit tegoeden', $this->organization),
        ];
    }
}
