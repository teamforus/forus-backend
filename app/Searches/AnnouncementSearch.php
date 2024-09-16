<?php


namespace App\Searches;


use App\Models\BankConnection;
use App\Models\Implementation;
use App\Models\Announcement;
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

        if ($clientType !== Implementation::FRONTEND_WEBSHOP) {
            $clientType = [$clientType, 'dashboards'];
        }

        $builder
            ->where(function(Builder $builder) use ($isWebshop) {
                if ($isWebshop) {
                    $builder->whereNull('implementation_id');
                    $builder->orWhere('implementation_id', $this->getFilter('implementation_id'));
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

        if (!$this->hasFilter('identity_address') || !$this->hasFilter('organization_id')) {
            $builder->where(function (Builder $builder) {
                $builder->whereNull('announceable_id');
                $builder->whereNull('announceable_type');
                $builder->whereNull('organization_id');
                $builder->whereNull('role_id');
            });
        } else {
            $identity_address = $this->getFilter('identity_address');
            $organization_id = $this->getFilter('organization_id');

            $builder->where(function (Builder $builder) use ($identity_address, $organization_id) {
                $this->whereBankConnection($builder);

                $builder->orWhere(function (Builder $builder) use ($identity_address, $organization_id) {
                    if ($identity_address) {
                        $builder->where(function (Builder $builder) use ($identity_address, $organization_id) {
                            $builder->whereHas('role', function (Builder $builder) use ($identity_address) {
                                $builder->whereRelation('employees', 'identity_address', $identity_address);
                            });

                            if ($organization_id) {
                                $builder->where(function (Builder $builder) use ($organization_id) {
                                    $builder->whereNull('organization_id');
                                    $builder->orWhere('organization_id', $organization_id);
                                });
                            }
                        });
                    }

                    if ($organization_id) {
                        $builder->orWhere(function (Builder $builder) use ($organization_id, $identity_address) {
                            $builder->where('organization_id', $organization_id);

                            if ($identity_address) {
                                $builder->where(function (Builder $builder) use ($identity_address) {
                                    $builder->whereNull('role_id');
                                    $builder->orWhereHas('role', function (Builder $builder) use ($identity_address) {
                                        $builder->whereRelation('employees', 'identity_address', $identity_address);
                                    });
                                });
                            }
                        });
                    }
                });
            });
        }

        return $builder->orderBy('created_at');
    }

    /**
     * @param Builder|Announcement $builder
     * @return Builder|Announcement
     */
    protected function whereBankConnection(Builder|Announcement $builder): Builder|Announcement
    {
        return $builder->whereHasMorph('announceable', BankConnection::class, function (Builder $builder) {
            $builder->whereHas('organization', function (Builder $builder) {
                return OrganizationQuery::whereHasPermissions(
                    $builder->where('id', $this->getFilter('organization_id')),
                    $this->getFilter('identity_address'),
                    $this->entityPermissionsMap['bank_connection'],
                );
            });

            $builder->where('state', BankConnection::STATE_ACTIVE);
        });
    }
}