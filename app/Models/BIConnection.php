<?php

namespace App\Models;

use App\Exports\EmployeesExport;
use App\Exports\FundIdentitiesExport;
use App\Exports\FundProvidersExport;
use App\Exports\FundsExport;
use App\Exports\ProductReservationsExport;
use App\Exports\ProviderFinancesExport;
use App\Exports\ReimbursementsSponsorExport;
use App\Exports\VoucherExport;
use App\Exports\VoucherTransactionBulksExport;
use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ProviderFinancialResource;
use App\Scopes\Builders\ProductReservationQuery;
use App\Scopes\Builders\VoucherSubQuery;
use App\Searches\EmployeesSearch;
use App\Searches\ProductReservationsSearch;
use App\Searches\Sponsor\FundIdentitiesSearch;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * App\Models\BIConnection
 *
 * @property int $id
 * @property int $organization_id
 * @property string $token
 * @property string $auth_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Services\EventLogService\Models\EventLog> $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereAcceptTokenIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BIConnection extends BaseModel
{
    use HasLogs;

    const EVENT_CREATED = 'created';
    const EVENT_RECREATED = 'recreated';
    const EVENT_UPDATED = 'updated';

    const AUTH_TYPE_HEADER = 'header';
    const AUTH_TYPE_PARAMETER = 'parameter';

    public static array $authTypes = [
        self::AUTH_TYPE_HEADER,
        self::AUTH_TYPE_PARAMETER,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'organization_id', 'token', 'auth_type',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Request $request
     * @return BIConnection|null
     */
    public static function getConnectionFromRequest(Request $request): ?BIConnection
    {
        if ($request->has('api_key')) {
            $token = $request->get('api_key');
            $authType = self::AUTH_TYPE_PARAMETER;
        } else {
            $token = $request->header('X-API-KEY');
            $authType = self::AUTH_TYPE_HEADER;
        }

        return BIConnection::whereToken($token)
            ->where('auth_type', $authType)
            ->has('organization')
            ->first();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDataArray(): array
    {
        $organization = $this->organization;

        if ($organization->is_sponsor) {
            return $this->getDataForSponsor();
        }

        if ($organization->is_provider) {
            return $this->getDataForProvider();
        }

        return [];
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getDataForSponsor(): array
    {
        return [
            'Tegoeden' => $this->getVouchers(),
            'Declaraties' => $this->getReimbursements(),
            'Medewerkers' => $this->getEmployees(),
            'Aanbieders' => $this->getFundProviders(),
            'Aanvragers' => $this->getFundIdentities(),
            'Aanbieder transacties' => $this->getProviderFinances(),
            'Transacties vanuit tegoeden' => $this->getVoucherTransactions(),
            'Bulk transacties vanuit tegoeden' => $this->getVoucherTransactionBulks(),
            'Financieel overzicht uitgaven' => $this->getFunds(false),
            'Financieel overzicht tegoeden' => $this->getFunds(),
        ];
    }

    /**
     * @param bool $detailed
     * @return array
     */
    private function getFunds(bool $detailed = true): array
    {
        $activeFundsQuery = $this->organization->funds()->where([
            'type' => Fund::TYPE_BUDGET,
        ])->where('state', '=', Fund::STATE_ACTIVE);

        $data = new FundsExport($activeFundsQuery->get(), $detailed, false);

        return $data->collection()->toArray();
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getVouchers(): array
    {
        $formRequest = new BaseFormRequest(['type' => 'all', 'source' => 'all']);
        $fields = VoucherExport::getExportFieldsRaw();

        $query = Voucher::searchSponsorQuery($formRequest, $this->organization);
        $query = VoucherSubQuery::appendFirstUseFields($query);

        $vouchers = $query->with([
            'transactions', 'voucher_relation', 'product', 'fund',
            'token_without_confirmation', 'identity.primary_email', 'identity.record_bsn',
            'product_vouchers', 'top_up_transactions',
        ])->get();

        $array = Voucher::exportOnlyDataArray($vouchers, array_keys($fields));

        return $this->transformKeys($array, $fields);
    }

    /**
     * @return array
     */
    private function getVoucherTransactionBulks(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = array_keys(VoucherTransactionBulksExport::getExportFieldsRaw());
        $data = new VoucherTransactionBulksExport($formRequest, $this->organization, $fields);

        return $data->collection()->toArray();
    }

    /**
     * @return array
     */
    private function getVoucherTransactions(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = array_keys(VoucherTransactionsSponsorExport::getExportFieldsRaw());
        $data = new VoucherTransactionsSponsorExport($formRequest, $this->organization, $fields);

        return $data->collection()->toArray();
    }

    /**
     * @return array
     */
    private function getEmployees(): array
    {
        $search = new EmployeesSearch([], $this->organization->employees()->getQuery());
        $data = new EmployeesExport($search->query());

        return $data->collection()->toArray();
    }

    /**
     * @return array
     */
    private function getFundIdentities(): array
    {
        return $this->organization->funds->reduce(function (array $list, Fund $fund) {
            $search = new FundIdentitiesSearch([], $fund);
            $fields = array_keys(FundIdentitiesExport::getExportFieldsRaw());
            $data = new FundIdentitiesExport($search->get(), $fields);

            return array_merge($list, $data->collection()->toArray());
        }, []);
    }

    /**
     * @return array
     */
    private function getFundProviders(): array
    {
        $formRequest = new BaseFormRequest();
        $data = new FundProvidersExport($formRequest, $this->organization);

        return $data->collection()->toArray();
    }

    /**
     * @return array
     */
    private function getProviderFinances(): array
    {
        $providers = Organization::searchProviderOrganizations($this->organization, [])
            ->with(ProviderFinancialResource::$load)->get();

        $data = new ProviderFinancesExport($this->organization, $providers);

        return $data->collection()->toArray();
    }

    /**
     * @return array
     */
    private function getReimbursements(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = ReimbursementsSponsorExport::getExportFieldsRaw();
        $data = new ReimbursementsSponsorExport($formRequest, $this->organization, array_keys($fields));

        return $this->transformKeys($data->collection()->toArray(), $fields);
    }

    /**
     * @return array
     */
    private function getDataForProvider(): array
    {
        return [
//            'reservations' => $this->getReservations(),
        ];
    }

    /**
     * @return array
     */
    private function getReservations(): array
    {
        $search = new ProductReservationsSearch([], ProductReservationQuery::whereProviderFilter(
            ProductReservation::query(), $this->organization->id
        ));
        $fields = array_keys(ProductReservationsExport::getExportFieldsRaw());

        $data = new ProductReservationsExport($search->get(), $fields);

        return $data->collection()->toArray();
    }

    /**
     * @param array $data
     * @param array $fields
     * @return array
     */
    private function transformKeys(array $data, array $fields): array
    {
        return array_map(function ($row) use ($fields) {
            return array_reduce(array_keys($row), fn($obj, $key) => array_merge($obj, [
                $fields[$key] => (string) $row[$key],
            ]), []);
        }, $data);
    }
}
