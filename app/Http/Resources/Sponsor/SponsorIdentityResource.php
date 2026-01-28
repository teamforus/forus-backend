<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\Identity;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileBankAccount;
use App\Models\ProfileRecord;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherQuery;
use App\Services\Forus\Session\Models\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property-read bool $detailed
 * @property-read Identity $resource
 * @property-read Organization $organization
 */
class SponsorIdentityResource extends BaseJsonResource
{
    public const array LOAD = [
        'emails',
        'vouchers.transactions',
        'record_bsn',
        'primary_email',
        'reimbursements.voucher.fund',
        'creator_employee.identity.primary_email',
        'profiles.profile_bank_accounts',
        'profiles.profile_records.record_type.translations',
        'profiles.profile_records.employee.identity.primary_email',
    ];

    public bool $detailed = false;

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [
            ...parent::load($append),
            $append ? "$append.sessions" : 'sessions' => function (Builder|Relation|Session $query) {
                $query->withTrashed();
            },
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $identity = $this->resource;
        $sessions = $identity->sessions;
        $profile = $identity->profiles?->firstWhere('organization_id', $this->organization?->id);

        return [
            ...$identity->only([
                'id', 'email', 'address',
            ]),
            'email_verified' => $identity->emails
                ->where('verified', true)
                ->where('primary', false)
                ->pluck('email'),
            'profile' => $profile?->only([
                'id', 'identity_id', 'organization_id',
            ]),
            ...((!$identity->creator_organization_id || ($identity->creator_organization_id === $this->organization->id)) ? [
                'type' => $identity->type,
                'type_locale' => $identity->type_locale,
                'employee_id' => $identity->creator_employee_id,
                'employee_email' => $identity->creator_employee?->identity?->email,
            ] : []),
            ...$this->getVoucherStats($identity),
            ...$this->detailed ? [
                'bsn' => $this->organization?->bsn_enabled ? $identity->bsn : null,
                'bank_accounts' => static::getBankAccounts($identity, $this->organization, $profile),
                'records' => static::getProfileRecords($profile, true),
                ...static::makeTimestampsStatic([
                    'created_at' => $identity->created_at,
                    'last_login_at' => $sessions->sortByDesc('created_at')->first()?->created_at,
                    'last_activity_at' => $sessions->sortByDesc('last_activity_at')->first()?->last_activity_at,
                ]),
            ] : [],
        ];
    }

    /**
     * @param Identity $identity
     * @param Organization|null $organization
     * @param Profile|null $profile
     * @return array
     */
    public static function getBankAccounts(
        Identity $identity,
        ?Organization $organization,
        ?Profile $profile,
    ): array {
        $fundRequests = $organization ? FundRequest::query()
            ->with('records')
            ->where('identity_id', $identity->id)
            ->where('state', FundRequest::STATE_APPROVED)
            ->whereRelation('fund', 'organization_id', $organization->id)
            ->whereHas('fund.vouchers', function (Builder $builder) use ($identity) {
                $builder->where('identity_id', $identity->id);
                VoucherQuery::whereNotExpiredAndActive($builder);
            })
            ->orderByDesc('created_at')
            ->get() : collect();

        $reimbursements = $identity->reimbursements->filter(function ($item) use ($organization) {
            return $item?->voucher?->fund?->organization_id === $organization?->id;
        });

        $payoutTransactions = $identity->vouchers->filter(function ($item) use ($organization) {
            return $item?->isTypePayout() && $item?->fund?->organization_id === $organization?->id;
        })->reduce(function (Collection $list, Voucher $voucher) {
            return $list->merge($voucher->transactions);
        }, collect());

        return [
            ...$profile?->profile_bank_accounts->map(fn (ProfileBankAccount $profileBankAccount) => [
                'id' => $profileBankAccount->id,
                'iban' => $profileBankAccount->iban,
                'name' => $profileBankAccount->name,
                'created_by' => 'manual',
                'created_by_locale' => 'Manual',
                'type' => 'profile_bank_account',
                'type_id' => $profileBankAccount->id,
                ...static::makeTimestampsStatic([
                    'created_at' => $profileBankAccount->created_at,
                    'updated_at' => $profileBankAccount->updated_at,
                ]),
            ])->toArray() ?: [],
            ...$reimbursements->map(fn ($reimbursement) => [
                'id' => null,
                'iban' => $reimbursement->iban,
                'name' => $reimbursement->iban_name,
                'created_by' => 'reimbursement',
                'created_by_locale' => 'Declaratie',
                'type' => 'reimbursement',
                'type_id' => $reimbursement->id,
                ...static::makeTimestampsStatic([
                    'created_at' => $reimbursement->created_at,
                    'updated_at' => $reimbursement->updated_at,
                ]),
            ]),
            ...collect($payoutTransactions)->map(fn (VoucherTransaction $transaction) => [
                'id' => null,
                'iban' => $transaction->target_iban,
                'name' => $transaction->target_name,
                'created_by' => 'payout',
                'created_by_locale' => 'Uitbetalingen',
                'type' => 'payout',
                'type_id' => $transaction->id,
                ...static::makeTimestampsStatic([
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ]),
            ]),
            ...$fundRequests->filter(fn (FundRequest $fundRequest) => (
                $fundRequest->getIban(false) && $fundRequest->getIbanName(false)
            ))->map(fn (FundRequest $fundRequest) => [
                'id' => null,
                'iban' => $fundRequest->getIban(false),
                'name' => $fundRequest->getIbanName(false),
                'created_by' => 'fund_request',
                'created_by_locale' => 'Aanvraag',
                'type' => 'fund_request',
                'type_id' => $fundRequest->id,
                ...static::makeTimestampsStatic([
                    'created_at' => $fundRequest->created_at,
                    'updated_at' => $fundRequest->updated_at,
                ]),
            ]),
        ];
    }

    /**
     * @param Profile|null $profile
     * @param bool $forSponsor
     * @return array
     */
    public static function getProfileRecords(?Profile $profile, bool $forSponsor = false): array
    {
        /** @var Collection $groups */
        $groups = $profile?->profile_records?->map(fn (ProfileRecord $record) => [
            ...$record->only([
                'id', 'value', 'value_locale',
            ]),
            ...self::makeTimestampsStatic([
                'created_at' => $record->created_at,
            ]),
            'key' => $record->record_type?->key,
            'name' => $record->record_type?->name,
            'timestamp' => $record->created_at?->timestamp,
            ...$forSponsor ? [
                'employee' => $record->employee ? [
                    'id' => $record->employee?->id,
                    'email' => $record->employee?->identity?->email,
                ] : null,
            ] : [
                'sponsor' => (bool) $record->employee_id,
                'sponsor_name' => $profile->organization?->name,
            ],

        ])?->groupBy('key');

        return $groups
            ?->map(fn (Collection $group) => $group->sortByDesc('timestamp')->values())
            ?->toArray() ?: [];
    }

    /**
     * Use IdentityQuery's addVouchersCountFields to load these columns.
     *
     * @param Identity $identity
     * @return array
     */
    protected function getVoucherStats(Identity $identity): array
    {
        return [
            ...$identity->only([
                'count_vouchers', 'count_vouchers_active', 'count_vouchers_active_with_balance',
            ]),
        ];
    }
}
