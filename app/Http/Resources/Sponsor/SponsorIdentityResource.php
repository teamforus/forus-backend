<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileBankAccount;
use App\Models\ProfileRecord;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
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
    public bool $detailed = false;

    public const array LOAD = [
        'emails',
        'vouchers',
        'record_bsn',
        'primary_email',
        'reimbursements.voucher.fund',
        'profiles.profile_bank_accounts',
        'profiles.profile_records.record_type.translations',
        'profiles.profile_records.employee.identity.primary_email',
    ];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [
            ...parent::load($append),
            $append ? "$append.sessions" : 'sessions' => function (Builder|Relation|Session $query) {
                $query->orderBy('last_activity_at', 'desc')->limit(1);
            },
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $identity = $this->resource;
        $profile = $identity->profiles?->firstWhere('organization_id', $this->organization?->id);

        return [
            ...$identity->only([
                'id', 'email', 'address',
            ]),
            'email_verified' => $identity->emails
                ->where('verified', true)
                ->where('primary', false)
                ->pluck('email'),
            ...$this->getVoucherStats($identity),
            ...$this->detailed ? [
                'bsn' => $this->organization?->bsn_enabled ? $identity->bsn : null,
                'bank_accounts' => static::getBankAccounts($identity, $this->organization, $profile),
                'records' => static::getProfileRecords($profile, true),
                ...static::makeTimestampsStatic([
                    'created_at' => $identity->created_at,
                    'last_activity_at' => array_first($identity->sessions)?->last_activity_at,
                ])
            ] : [],
        ];
    }

    /**
     * Use IdentityQuery's addVouchersCountFields to load these columns
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
        $reimbursements = $identity->reimbursements->filter(function ($item) use ($organization) {
            return $item?->voucher?->fund?->organization_id === $organization?->id;
        });

        $payoutTransactions = $identity->vouchers->filter(function ($item) use ($organization) {
            return $item?->isTypePayout() && $item?->fund?->organization_id === $organization?->id;
        })->reduce(function (Collection $list, Voucher $voucher) {
            return $list->merge($voucher->transactions);
        }, collect());

        return [
            ...$profile?->profile_bank_accounts->map(fn(ProfileBankAccount $profileBankAccount) => [
                'id' => $profileBankAccount->id,
                'iban' => $profileBankAccount->iban,
                'name' => $profileBankAccount->name,
                'created_by' => 'manual',
                'created_by_locale' => 'Manual',
                ...static::makeTimestampsStatic([
                    'created_at' => $profileBankAccount->created_at,
                    'updated_at' => $profileBankAccount->updated_at,
                ])
            ])->toArray() ?: [],
            ...$reimbursements->map(fn($reimbursement) => [
                'iban' => $reimbursement->iban,
                'name' => $reimbursement->iban_name,
                'created_by' => 'reimbursement',
                'created_by_locale' => 'Declaratie',
                ...static::makeTimestampsStatic([
                    'created_at' => $reimbursement->created_at,
                    'updated_at' => $reimbursement->updated_at,
                ])
            ]),
            ...collect($payoutTransactions)->map(fn(VoucherTransaction $transaction) => [
                'iban' => $transaction->target_iban,
                'name' => $transaction->target_name,
                'created_by' => 'payout',
                'created_by_locale' => 'Uitbetalingen',
                ...static::makeTimestampsStatic([
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ])
            ])
        ];
    }

    /**
     * @param Profile|null $profile
     * @param bool $forSponsor
     * @return array
     */
    public static function getProfileRecords(?Profile $profile, bool $forSponsor = false): array
    {
        $groups = $profile?->profile_records?->map(fn(ProfileRecord $record) => [
            ...$record->only([
                'id', 'value',
            ]),
            ...self::makeTimestampsStatic([
                'created_at' => $record->created_at,
            ]),
            ...($record->record_type->key === 'birth_date' && $record->value ? [
                'value_locale' => format_date_locale($record->value),
            ] : [
                'value_locale' => $record->value,
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
            ?->map(fn(Collection $group) => $group->sortByDesc('timestamp'))
            ?->toArray() ?: [];
    }
}
