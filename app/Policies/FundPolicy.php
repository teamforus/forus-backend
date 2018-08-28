<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundCriterion;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundPolicy
{
    use HandlesAuthorization;

    protected $identityRepo;
    protected $recordRepo;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->identityRepo = app()->make('forus.services.identity');
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function index($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function show($identity_address, Fund $fund) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function store($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @return bool
     */
    public function update($identity_address, Fund $fund) {
        return strcmp(
            $fund->organization->identity_address,
            $identity_address
            ) == 0;
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @return bool
     */
    public function apply($identity_address, Fund $fund) {
        if (empty($identity_address) && ($fund->state != 'active')) {
            return false;
        }

        // The same identity can't apply twice to the same fund
        if ($fund->vouchers()->where(
            'identity_address', $identity_address
        )->count()) {
            return false;
        }

        $self = $this;
        $trustedIdentities = $fund->organization->validators->pluck(
            'identity_address'
        );

        $invalidCriteria = $fund->criteria->filter(function(
            $criterion
        ) use ($self, $identity_address, $trustedIdentities) {
            /** @var FundCriterion $criterion */
            $recordsOfType = collect($this->recordRepo->recordsList(
                $identity_address, $criterion->record_type_key
            ));

            $validRecordsOfType = $recordsOfType->where(
                'value', $criterion['operator'], $criterion['value']
            )->filter(function(
                $record
            ) use ($trustedIdentities) {
                return collect($record['validations'])->whereIn(
                    'identity_address',
                    $trustedIdentities
                )->count() > 0;
            });

            // $trustedIdentities
            return $validRecordsOfType->count() == 0;
        });

        if ($invalidCriteria->count() > 0) {
            return false;
        }

        return true;
    }
}
