<?php

namespace App\Services\BIConnectionService;

use App\Exports\BIExporters\BIEmployeesExporter;
use App\Exports\BIExporters\BIFundIdentitiesExporter;
use App\Exports\BIExporters\BIFundProviderFinancesExporter;
use App\Exports\BIExporters\BIFundProvidersExporter;
use App\Exports\BIExporters\BIFundRequestsExporter;
use App\Exports\BIExporters\BIFundsDetailedExporter;
use App\Exports\BIExporters\BIFundsExporter;
use App\Exports\BIExporters\BIReimbursementsExporter;
use App\Exports\BIExporters\BIVouchersExporter;
use App\Exports\BIExporters\BIVoucherTransactionBulksExporter;
use App\Exports\BIExporters\BIVoucherTransactionsExporter;
use App\Models\Organization;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

class BIConnectionService
{
    /**
     * @param Organization $organization
     */
    protected function __construct(protected Organization $organization)
    {
    }

    /**
     * @param Organization $organization
     * @return static
     */
    public static function create(Organization $organization): static
    {
        return new static($organization);
    }

    /**
     * @param Request $request
     * @return BIConnectionService|null
     */
    public static function getBIConnectionFromRequest(Request $request): ?static
    {
        $organization = Organization::whereHas('bi_connection', function (Builder $builder) use ($request) {
            $accessToken = $request->header(BIConnection::AUTH_TYPE_HEADER_NAME);

            $builder->where('enabled', true);
            $builder->where('access_token', is_string($accessToken) ? $accessToken : null);
            $builder->where('expire_at', '>', now());
            $builder->where(function (Builder $builder) use ($request) {
                $builder->whereJsonContains('ips', $request->ip());
                $builder->orWhere(function (Builder $builder) {
                    $builder->whereNull('ips');
                    $builder->orWhereJsonLength('ips', 0);
                });
            });
        })->first();

        return $organization ? static::create($organization) : null;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @throws Throwable
     * @return array
     */
    public function getDataArray(): array
    {
        $exporters = $this->getData();
        $dataTypes = $this->getOrganization()->bi_connection->data_types;

        return array_reduce($exporters, function ($list, BaseBIExporter $exporter) use ($dataTypes) {
            return in_array($exporter->getKey(), $dataTypes) ? array_merge($list, [
                $exporter->getName() => $exporter->toArray(),
            ]) : $list;
        }, []);
    }

    /**
     * @throws Throwable
     */
    public function getDataTypes(): array
    {
        return array_reduce($this->getData(), function ($list, BaseBIExporter $exporter) {
            return [...$list, [
                'key' => $exporter->getKey(),
                'name' => $exporter->getName(),
            ]];
        }, []);
    }

    /**
     * @throws Throwable
     * @return array
     */
    private function getData(): array
    {
        return [
            new BIVouchersExporter($this->organization),
            new BIReimbursementsExporter($this->organization),
            new BIEmployeesExporter($this->organization),
            new BIFundsExporter($this->organization),
            new BIFundsDetailedExporter($this->organization),
            new BIFundProvidersExporter($this->organization),
            new BIFundIdentitiesExporter($this->organization),
            new BIFundProviderFinancesExporter($this->organization),
            new BIVoucherTransactionsExporter($this->organization),
            new BIVoucherTransactionBulksExporter($this->organization),
            new BIFundRequestsExporter($this->organization),
        ];
    }
}
