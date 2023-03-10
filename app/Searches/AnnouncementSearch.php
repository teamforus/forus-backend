<?php


namespace App\Searches;


use App\Http\Requests\BaseFormRequest;
use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementSearch extends BaseSearch
{
    protected BaseFormRequest $request;
    protected ?Employee $employee;
    protected ?Organization $organization;

    /**
     * @var array|string[]
     */
    protected array $entityPermissionsMap = [
        'bank_connection' => [
            'view_finances', 'manage_bank_connections',
        ],
    ];

    /**
     * @param BaseFormRequest $request
     * @param array $filters
     * @param Organization|null $organization
     */
    public function __construct(
        BaseFormRequest $request,
        array $filters,
        ?Organization $organization = null
    ) {
        parent::__construct($filters, Announcement::query());

        $this->request = $request;
        $this->organization = $organization;
        $this->employee = $organization && $request->isAuthenticated() ? $request->employee($organization) : null;
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        $clientType = $this->request->client_type();
        $implementation = $this->request->implementation();

        if ($clientType !== $implementation::FRONTEND_WEBSHOP) {
            $clientType = [$clientType, 'dashboards'];
        }

        $builder
            ->where(function(Builder $builder) use ($clientType, $implementation) {
                if ($clientType === $implementation::FRONTEND_WEBSHOP) {
                    $builder->whereNull('implementation_id');
                    $builder->orWhere('implementation_id', $implementation->id);
                }
            })
            ->where('active', true)
            ->whereIn('scope', (array) $clientType)
            ->where(function (Builder $builder) {
                $builder->whereNull('expire_at');
                $builder->orWhere('expire_at', '>=', now()->startOfDay());
            });

        $builder->where(function (Builder $builder) {
            $builder->whereDoesntHave('announcementable');
            $this->whereBankConnection($builder);
        });

        return $builder->orderByDesc('created_at');
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function whereBankConnection(Builder $builder): Builder
    {
        if (!$this->employee || !$this->organization) {
            return $builder;
        }

        $ids = $this->organization->identityCan(
            $this->employee->identity, $this->entityPermissionsMap['bank_connection']
        ) ? [$this->organization->id] : [];

        return $builder->orWhereHasMorph(
            'announcementable',
            BankConnection::class,
            function (Builder $builder) use ($ids) {
                $builder->whereIn('organization_id', $ids)
                    ->where('state', BankConnection::STATE_ACTIVE);
            }
        );
    }
}