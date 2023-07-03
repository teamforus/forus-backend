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
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BIConnection
{
    const AUTH_TYPE_HEADER = 'header';
    const AUTH_TYPE_DISABLED = 'disabled';
    const AUTH_TYPE_PARAMETER = 'parameter';

    const AUTH_TYPE_HEADER_NAME = 'X-API-KEY';
    const AUTH_TYPE_PARAMETER_NAME = 'api_key';

    const AUTH_TYPES = [
        self::AUTH_TYPE_HEADER,
        self::AUTH_TYPE_DISABLED,
        self::AUTH_TYPE_PARAMETER,
    ];

    /**
     * @param Organization $organization
     */
    public function __construct(
        protected Organization $organization,
    ){}

    /**
     * @param Request $request
     * @return BIConnection|null
     */
    public static function getBIConnectionFromRequest(Request $request): ?static
    {
        $organization = Organization::where(function(Builder $builder) use ($request) {
            $builder->where(fn ($q) => $q->where([
                'bi_connection_auth_type' => self::AUTH_TYPE_PARAMETER,
                'bi_connection_token' => $request->get(self::AUTH_TYPE_PARAMETER_NAME),
            ]));

            $builder->orWhere(fn ($q) => $q->where([
                'bi_connection_auth_type' => self::AUTH_TYPE_HEADER,
                'bi_connection_token' => $request->header(self::AUTH_TYPE_HEADER_NAME),
            ]));
        })->first();

        if ($organization) {
            return new static($organization);
        }

        return null;
    }

    /**
     * @return string
     */
    public static function makeToken(): string
    {
        return token_generator()->generate(64);
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
        $exporters = $this->getDataForSponsor();

        return array_reduce($exporters, fn($list, BaseBIExporter $exporter) => array_merge($list, [
            $exporter->getName() => $exporter->toArray(),
        ]), []);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getDataForSponsor(): array
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
