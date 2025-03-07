<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $identity_id
 * @property int $organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Identity $identity
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProfileBankAccount[] $profile_bank_accounts
 * @property-read int|null $profile_bank_accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProfileRecord[] $profile_records
 * @property-read int|null $profile_records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereIdentityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Profile extends Model
{
    protected $fillable = [
        'identity_id', 'organization_id',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function profile_records(): HasMany
    {
        return $this->hasMany(ProfileRecord::class)->latest()->latest('id');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function profile_bank_accounts(): HasMany
    {
        return $this->hasMany(ProfileBankAccount::class);
    }

    /**
     * @param array $records
     * @param Employee|null $employee
     * @return void
     */
    public function updateRecords(array $records, ?Employee $employee = null): void
    {
        foreach ($records as $recordKey => $recordValue) {
            $recordType = RecordType::findByKey($recordKey);

            $currentValue = $this->profile_records()->where([
                'record_type_id' => $recordType?->id,
            ])->latest('created_at')->first()?->value ?: '';

            if (trim($currentValue) !== trim($recordValue)) {
                if (empty(trim($recordValue)) && empty(trim($currentValue))) {
                    continue;
                }

                $this->profile_records()->create([
                    'value' => trim($recordValue),
                    'employee_id' => $employee?->id,
                    'record_type_id' => $recordType?->id,
                ]);
            }
        }
    }
}
