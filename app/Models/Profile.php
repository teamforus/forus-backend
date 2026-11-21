<?php

namespace App\Models;

use App\Modules\RequesterImport\Models\RequesterImportRow;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Throwable;

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
     * @return HasMany
     */
    public function requester_import_rows(): HasMany
    {
        return $this->hasMany(RequesterImportRow::class);
    }

    /**
     * @return Collection<string, ProfileRecord>
     */
    public function getCurrentRecordsForVoucherAward(): Collection
    {
        $this->loadMissing('profile_records.record_type');

        return $this->profile_records
            ->whereNull('deleted_at')
            ->unique('record_type_id')
            ->where('source', ProfileRecord::SOURCE_REQUESTER_IMPORT)
            ->keyBy(fn (ProfileRecord $record) => $record->record_type->key);
    }

    /**
     * @param array $records
     * @param Employee|null $employee
     * @param string|null $source
     * @throws Throwable
     * @return Collection<string, ProfileRecord>
     */
    public function updateRecords(array $records, ?Employee $employee = null, ?string $source = null): Collection
    {
        return DB::transaction(function () use ($records, $employee, $source): Collection {
            $profile = self::query()->lockForUpdate()->findOrFail($this->id);
            $created = new Collection();

            if ($records === []) {
                return $created;
            }

            $recordTypeIds = RecordType::query()->whereIn('key', array_keys($records))->pluck('id', 'key');

            $recordsQuery = $profile->profile_records()->whereIn('record_type_id', $recordTypeIds->values());

            if ($source === ProfileRecord::SOURCE_REQUESTER_IMPORT) {
                $recordsQuery->whereNull('deleted_at');
            }

            $currentRecords = $recordsQuery->lockForUpdate()->get()->unique('record_type_id')->keyBy('record_type_id');

            foreach ($records as $recordKey => $recordValue) {
                $recordTypeId = $recordTypeIds->get($recordKey);
                $currentRecord = $currentRecords->get($recordTypeId);
                $recordValue = trim($recordValue ?? '');

                if (!self::shouldUpdateRecord($currentRecord, $recordValue, $source)) {
                    continue;
                }

                $record = $profile->profile_records()->create([
                    'value' => $recordValue,
                    'employee_id' => $employee?->id,
                    'source' => $source,
                    'record_type_id' => $recordTypeId,
                ]);

                $created->put($recordKey, $record);
                $currentRecords->put($recordTypeId, $record);
            }

            return $created;
        });
    }

    /**
     * @param ProfileRecord|null $currentRecord
     * @param string|null $value
     * @param string|null $source
     * @return bool
     */
    public static function shouldUpdateRecord(?ProfileRecord $currentRecord, ?string $value, ?string $source): bool
    {
        $valueUnchanged = trim($currentRecord?->value ?? '') === trim($value ?? '');

        $shouldSetRequesterImportSource =
            $source === ProfileRecord::SOURCE_REQUESTER_IMPORT &&
            $currentRecord !== null &&
            $currentRecord->source !== ProfileRecord::SOURCE_REQUESTER_IMPORT;

        return !$valueUnchanged || $shouldSetRequesterImportSource;
    }
}
