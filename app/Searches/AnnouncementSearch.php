<?php


namespace App\Searches;


use App\Models\Announcement;
use App\Models\BankConnection;
use App\Models\Implementation;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementSearch extends BaseSearch
{
    /**
     * @var array|string[]
     */
    protected array $entityPermissionsMap = [
        'bank_connection' => [
            'view_finances', 'manage_bank_connections',
        ],
    ];

    /**
     * @param array $filters
     * @param Builder|Announcement|null $builder
     */
    public function __construct(array $filters, Builder|Announcement $builder = null)
    {
        parent::__construct($filters, $builder ?: Announcement::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        /** @var Builder|Announcement $builder */
        $builder = parent::query();

        $clientType = $this->getFilter('client_type');
        $isWebshop = $clientType === Implementation::FRONTEND_WEBSHOP;

        $organizationId = $this->getFilter('organization_id');
        $identityAddress = $this->getFilter('identity_address');
        $implementationId = $this->getFilter('implementation_id');

        if ($clientType !== Implementation::FRONTEND_WEBSHOP) {
            $clientType = [$clientType, 'dashboards'];
        }

        $builder
            ->where(function(Builder $builder) use ($isWebshop, $implementationId) {
                if ($isWebshop) {
                    $builder->whereNull('implementation_id');
                    $builder->orWhere('implementation_id', $implementationId);
                }
            })
            ->where('active', true)
            ->whereIn('scope', (array) $clientType)
            ->where(function (Builder $builder) {
                $builder->whereNull('expire_at');
                $builder->orWhere('expire_at', '>=', now()->startOfDay());
            })
            ->where(function (Builder $builder) {
                $builder->whereNull('start_at');
                $builder->orWhere('start_at', '<=', now()->startOfDay());
            });

        if (!$identityAddress || !$organizationId) {
            $builder->where(function (Builder $builder) {
                $builder->whereNull('announceable_id');
                $builder->whereNull('announceable_type');
                $builder->whereNull('organization_id');
                $builder->whereNull('role_id');
            });
        } else {
            $builder->where(function (Builder $builder) use ($identityAddress, $organizationId) {
                $builder->where(fn(Builder $builder) => $this->whereBankConnection(
                    $builder,
                    $identityAddress,
                    $organizationId,
                ));

                $builder->orWhere(fn(Builder $builder) => $this->whereOrganizationOrRole(
                    $builder,
                    $identityAddress,
                    $organizationId,
                ));
            });
        }

        return $builder->orderBy('created_at');
    }

    /**
     * @param Builder|Announcement $builder
     * @param string|null $identityAddress
     * @param int|null $organizationId
     * @return Builder|Announcement
     */
    protected function whereBankConnection(
        Builder|Announcement $builder,
        ?string $identityAddress = null,
        ?int $organizationId = null,
    ): Builder|Announcement {
        return $builder->whereHasMorph('announceable', BankConnection::class, function (
            Builder $builder,
        ) use ($identityAddress, $organizationId) {
            $builder->whereHas('organization', function (
                Builder $builder,
            ) use ($identityAddress, $organizationId) {
                return OrganizationQuery::whereHasPermissions(
                    $builder->where('id', $organizationId),
                    $identityAddress,
                    $this->entityPermissionsMap['bank_connection'],
                );
            });

            $builder->where('state', BankConnection::STATE_ACTIVE);
        });
    }

    /**
     * @param Builder|Announcement $builder
     * @param string|null $identityAddress
     * @param int|null $organizationId
     * @return Builder|Announcement
     */
    protected function whereOrganizationOrRole(
        Builder|Announcement $builder,
        ?string $identityAddress = null,
        ?int $organizationId = null,
    ): Builder|Announcement {
        return $builder->where(function (Builder $builder) use ($identityAddress, $organizationId) {
            if ($identityAddress) {
                $builder->where(function (Builder $builder) use ($identityAddress, $organizationId) {
                    $builder->whereHas('role', function (Builder $builder) use ($identityAddress) {
                        $builder->whereRelation('employees', 'identity_address', $identityAddress);
                    });

                    if ($organizationId) {
                        $builder->where(function (Builder $builder) use ($organizationId) {
                            $builder->whereNull('organization_id');
                            $builder->orWhere('organization_id', $organizationId);
                        });
                    }
                });
            }

            if ($organizationId) {
                $builder->orWhere(function (Builder $builder) use ($organizationId, $identityAddress) {
                    $builder->where('organization_id', $organizationId);

                    if ($identityAddress) {
                        $builder->where(function (Builder $builder) use ($identityAddress) {
                            $builder->whereNull('role_id');
                            $builder->orWhereHas('role', function (Builder $builder) use ($identityAddress) {
                                $builder->whereRelation('employees', 'identity_address', $identityAddress);
                            });
                        });
                    }
                });
            }
        });
    }
}