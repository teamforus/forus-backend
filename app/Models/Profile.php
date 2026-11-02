<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Profile.
 *
 * @property int $id
 * @property int $identity_id
 * @property int $organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Household[] $households
 * @property-read int|null $households_count
 * @property-read \App\Models\Identity $identity
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProfileBankAccount[] $profile_bank_accounts
 * @property-read int|null $profile_bank_accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProfileRecord[] $profile_records
 * @property-read int|null $profile_records_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProfileRelation[] $profile_relations
 * @property-read int|null $profile_relations_count
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
        'identity_id', 'organization_id', 'employee_id',
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
     * @return BelongsToMany
     */
    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class, HouseholdProfile::class);
    }

    /**
     * @return HasMany
     */
    public function profile_relations(): HasMany
    {
        return $this->hasMany(ProfileRelation::class);
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
     * @param string|null $source
     * @return Collection<string, ProfileRecord>
     */
    public function updateRecords(array $records, ?Employee $employee = null, ?string $source = null): Collection
    {
        return DB::transaction(function () use ($records, $employee, $source): Collection {
            $profile = self::query()->lockForUpdate()->findOrFail($this->id);
            $created = new Collection();

            foreach ($records as $recordKey => $recordValue) {
                $recordType = RecordType::findByKey($recordKey);
                $currentValue = $profile->profile_records()->where('record_type_id', $recordType?->id)
                    ->lockForUpdate()->first()?->value ?? '';

                if (trim($currentValue) === trim($recordValue ?? '')) {
                    continue;
                }

                $created->put($recordKey, $profile->profile_records()->create([
                    'value' => trim($recordValue ?? ''),
                    'employee_id' => $employee?->id,
                    'source' => $source,
                    'record_type_id' => $recordType?->id,
                ]));
            }

            return $created;
        });
    }
}
