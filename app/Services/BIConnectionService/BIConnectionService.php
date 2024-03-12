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
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Throwable;

class BIConnectionService
{
    /**
     * @param Organization $organization
     */
    protected function __construct(protected Organization $organization) {}

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
        return static::getBIConnection(
            $request->ip(),
            $request->header(BIConnection::AUTH_TYPE_HEADER_NAME),
        );
    }

    /**
     * @param string $ip
     * @param string|null $token
     * @return BIConnectionService|null
     */
    public static function getBIConnection(
        string $ip,
        ?string $token,
    ): ?static {
        $organization = Organization::whereHas('bi_connection', function(Builder $builder) use (
            $ip, $token
        ) {
            $builder->where(fn (Builder $q) => static::whereEnabledAndValidToken($q, $token, $ip));
        })->first();

        if ($organization) {
            return static::create($organization);
        }

        return null;
    }

    /**
     * @param BIConnection|Builder|Relation $query
     * @param string|null $access_token
     * @param string|null $ip
     * @return Builder|Relation
     */
    protected static function whereEnabledAndValidToken(
        BIConnection|Builder|Relation $query,
        ?string $access_token,
        ?string $ip,
    ): Builder|Relation {
        $query->where('enabled', true);
        $query->whereJsonContains('ips', $ip);
        $query->where('expire_at', '>', now());

        return $query->where(compact('access_token'));
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
     * @throws Throwable
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
     * @return array
     * @throws Throwable
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
        ];
    }
}
