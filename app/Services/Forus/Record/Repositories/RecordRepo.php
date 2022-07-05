<?php

namespace App\Services\Forus\Record\Repositories;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Record\Models\Record;
use App\Services\Forus\Record\Models\RecordCategory;
use App\Services\Forus\Record\Models\RecordType;
use App\Services\Forus\Record\Models\RecordValidation;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Database\Eloquent\Builder;

class RecordRepo implements IRecordRepo
{
    /**
     * Create or update records for given identity
     * @param string $identityAddress
     * @param array $records
     * @return void
     */
    public function updateRecords(string $identityAddress, array $records): void
    {
        $recordTypes = RecordType::pluck('id', 'key')->toArray();

        foreach ($records as $key => $value) {
            Record::firstOrCreate([
                'identity_address' => $identityAddress,
                'record_type_id' => $recordTypes[$key]
            ])->update([
                'value' => $value
            ]);
        }
    }

    /**
     * Get list all available record type keys
     * @param bool $withSystem
     * @return array
     */
    public function getRecordTypes(bool $withSystem = true): array
    {
        $query = RecordType::query();

        if (!$withSystem) {
            $query->where('system', 0);
        }

        return $query->get()->map(static function(RecordType $recordType) {
            return array_merge($recordType->only('id', 'key', 'type', 'system'), [
                'name' => $recordType['name'] ?? $recordType['key'],
            ]);
        })->toArray();
    }

    /**
     * Check if record type and value is unique
     * @param string $recordTypeKey
     * @param string $recordValue
     * @param mixed $excludeIdentity
     * @return boolean
     */
    public function isRecordUnique(
        string $recordTypeKey,
        string $recordValue,
        string $excludeIdentity = null
    ): bool {
        /**
         * @var RecordType $recordType
         */
        $recordType = RecordType::query()->where([
            'key' => $recordTypeKey
        ])->first();

        if (!$recordType) {
            abort(403, trans('record.exceptions.unknown_record_type', [
                'type' => $recordTypeKey
            ]));
        }

        $record = Record::query()->where([
            'record_type_id' => $recordType->id,
            'value' => $recordValue
        ]);

        if ($excludeIdentity) {
            $record->where(
                'identity_address', '!=', $excludeIdentity
            );
        }

        return $record->count() == 0;
    }

    /**
     * Check if record type and value is already existing
     * @param string $recordTypeKey
     * @param string $recordValue
     * @param string|null $excludeIdentity
     * @return bool
     */
    public function isRecordExists(
        string $recordTypeKey,
        string $recordValue,
        string $excludeIdentity = null
    ): bool {
        $recordType = RecordType::where('key', $recordTypeKey)->first();

        if (!$recordType) {
            abort(403, trans('record.exceptions.unknown_record_type', [
                'type' => $recordTypeKey
            ]));
        }

        $record = Record::where([
            'record_type_id' => $recordType->id,
            'value' => $recordValue
        ]);

        if ($excludeIdentity) {
            $record->where('identity_address', '!=', $excludeIdentity);
        }

        return $record->exists();
    }


    /**
     * Get identity id by email record
     * @param string $email
     * @return string|null
     */
    public function identityAddressByEmail(string $email): ?string
    {
        return identity_repo()->getAddress($email);
    }

    /**
     * Get identity_address by bsn
     * @param string $bsn
     * @return string|null
     */
    public function identityAddressByBsn(
        string $bsn
    ): ?string {
        $record = Record::query()->where([
            'record_type_id' => $this->getTypeIdByKey('bsn'),
            'value' => $bsn,
        ])->first();

        return $record->identity_address ?? null;
    }

    /**
     * Search identity_address by bsn
     * @param string $search
     * @return array
     */
    public function identityAddressByBsnSearch(string $search): array
    {
        return Record::query()
            ->where('record_type_id', $this->getTypeIdByKey('bsn'))
            ->where('value', 'LIKE', "%$search%")
            ->pluck('identity_address')
            ->toArray();
    }

    /**
     * Get identity id by email record
     * @param string $identityAddress
     * @return string|null
     */
    public function primaryEmailByAddress(string $identityAddress): ?string
    {
        return identity_repo()->getPrimaryEmail($identityAddress);
    }

    /**
     * Change identity primary_email record value
     *
     * @param string $identityAddress
     * @param string $email
     * @return mixed|string|null
     */
    public function setIdentityPrimaryEmailRecord(
        string $identityAddress,
        string $email
    ): void {
        Record::query()->where([
            'record_type_id' => $this->getTypeIdByKey('primary_email'),
            'identity_address' => $identityAddress,
        ])->update([
            'value' => $email
        ]);
    }

    /**
     * Get bsn by identity_address
     * @param string $identityAddress
     * @return string|null
     */
    public function bsnByAddress(string $identityAddress): ?string
    {
        $record = Record::query()->where([
            'record_type_id' => $this->getTypeIdByKey('bsn'),
            'identity_address' => $identityAddress,
        ])->first();

        return $record ? $record->value : null;
    }

    /**
     * Get type id by key
     * @param string $key
     * @return int|null
     */
    public function getTypeIdByKey(string $key): ?int
    {
        $recordType = RecordType::where('key', $key)->first();
        return $recordType ? $recordType->id : null;
    }

    /**
     * Add new record category to identity
     * @param string $identityAddress
     * @param string $name
     * @param int $order
     * @return array|null
     */
    public function categoryCreate(
        string $identityAddress,
        string $name,
        int $order = 0
    ): ?array {
        $recordCategory = RecordCategory::create([
            'identity_address' => $identityAddress,
            'name' => $name,
            'order' => $order,
        ]);

        return $this->categoryGet($identityAddress, $recordCategory->id);
    }

    /**
     * Get identity record categories
     * @param string $identityAddress
     * @return array
     */
    public function categoriesList(string $identityAddress): array
    {
        return RecordCategory::where([
            'identity_address' => $identityAddress
        ])->select([
            'id', 'name', 'order'
        ])->orderBy('order')->get()->toArray();
    }

    /**
     * Get identity record category
     * @param string $identityAddress
     * @param mixed $recordCategoryId
     * @return array|null
     */
    public function categoryGet(string $identityAddress, $recordCategoryId): ?array
    {
        $record =  RecordCategory::query()->where([
            'id' => $recordCategoryId,
            'identity_address' => $identityAddress
        ])->select([
            'id', 'name', 'order'
        ])->first();

        return !$record ? null : $record->toArray();
    }

    /**
     * Update identity record category
     * @param string $identityAddress
     * @param string $categoryId
     * @param string|null $name
     * @param int|null $order
     * @return bool
     */
    public function categoryUpdate(
        string $identityAddress,
        string $categoryId,
        string $name = null,
        int $order = null
    ): bool {
        $record =  RecordCategory::where([
            'id' => $categoryId,
            'identity_address' => $identityAddress
        ])->first();

        if (!$record) {
            return false;
        }

        return !!$record->update(collect(compact(
            'name', 'order'
        ))->filter(function($val) {
            return !is_null($val);
        })->toArray());
    }

    /**
     * Sort categories
     * @param string $identityAddress
     * @param array $orders
     * @return void
     */
    public function categoriesSort(string $identityAddress, array $orders): void
    {
        foreach ($orders as $categoryId => $order) {
            $this->categoryUpdate($identityAddress, $categoryId, null, $order);
        }
    }

    /**
     * Delete category
     * @param string $identityAddress
     * @param mixed $recordCategoryId
     * @return bool
     * @throws \Exception
     */
    public function categoryDelete(string $identityAddress, $recordCategoryId): bool
    {
        /** @var RecordCategory $recordCategory */
        $recordCategory = RecordCategory::query()->where([
            'id' => $recordCategoryId,
            'identity_address' => $identityAddress
        ])->first();

        if (!$recordCategory) {
            return false;
        }

        $recordCategory->records()->update([
            'record_category_id' => null
        ]);


        return (bool) $recordCategory->delete();
    }


    /**
     * Get identity records
     * @param string $identityAddress
     * @param null $type
     * @param null $categoryId
     * @param bool $deleted
     * @param ?int $trustedDays
     * @return array
     */
    public function recordsList(
        string $identityAddress,
        $type = null,
        $categoryId = null,
        bool $deleted = false,
        ?int $trustedDays = null
    ): ?array {
        // Todo: validation state
        /** @var Builder $query */
        $query = $deleted ? Record::onlyTrashed() : Record::query();
        $query->where('identity_address', $identityAddress)->with('record_type');

        if ($type) {
            if ($recordType = RecordType::query()->where('key', $type)->first()) {
                $query->where('record_type_id', $recordType->id);
            } else {
                return null;
            }
        }

        if ($categoryId) {
            $query->where('record_category_id', $categoryId);
        }

        if ($trustedDays) {
            $query->whereHas('validations', static function(Builder $builder) use ($trustedDays) {
                $builder->where(static function(Builder $builder) use ($trustedDays) {
                    $builder->whereNotNull('prevalidation_id');
                    $builder->whereHas('prevalidation', static function(Builder $builder) use ($trustedDays) {
                        $builder->where('validated_at', '>=', now()->subDays($trustedDays));
                    });
                });

                $builder->orWhere(static function(Builder $builder) use ($trustedDays) {
                    $builder->whereNull('prevalidation_id');
                    $builder->where('created_at', '>=', now()->subDays($trustedDays));
                });
            });
        }

        return $query->orderBy('order')->get()->map(function(Record $record) use ($trustedDays) {
            $builder = $record->validations()->where([
                'state' => 'approved'
            ])->select([
                'id', 'state', 'identity_address', 'created_at', 'updated_at',
                'organization_id', 'prevalidation_id',
            ]);

            if ($trustedDays) {
                $builder->where(function(Builder $builder) use ($trustedDays) {
                    $builder->where(static function(Builder $builder) use ($trustedDays) {
                        $builder->whereNotNull('prevalidation_id');
                        $builder->whereHas('prevalidation', static function(Builder $builder) use ($trustedDays) {
                            $builder->where('validated_at', '>=', now()->subDays($trustedDays));
                        });
                    });

                    $builder->orWhere(static function(Builder $builder) use ($trustedDays) {
                        $builder->whereNull('prevalidation_id');
                        $builder->where('created_at', '>=', now()->subDays($trustedDays));
                    });
                });
            }

            $validations = $builder->get()->load('organization')->map(function(RecordValidation $validation) {
                $validation->setAttribute('validation_date_timestamp', $validation->validation_date->timestamp);
                $validation->setAttribute('email', $validation->organization ? null : $this->primaryEmailByAddress(
                    $validation->identity_address
                ));

                return $validation;
            })->sortByDesc(static function(RecordValidation $validation) {
                return $validation->validation_date->timestamp;
            })->toArray();

            return [
                'id' => $record->id,
                'value' => $record->value,
                'order' => $record->order,
                'key' => $record->record_type->key,
                'name' => $record->record_type->name,
                'deleted' => !is_null($record->deleted_at),
                'record_category_id' => $record->record_category_id,
                'validations' => $validations,
            ];
        })->toArray();
    }

    /**
     * Get identity record
     * @param string $identityAddress
     * @param int $recordId
     * @param bool $withTrashed
     * @return array
     */
    public function recordGet(
        string $identityAddress,
        int $recordId,
        bool $withTrashed = false
    ): ?array {
        /** @var Record $record */
        $query = $withTrashed ? Record::withTrashed() : Record::query();
        $record = $query->where([
            'id' => $recordId,
            'identity_address' => $identityAddress,
        ])->first();

        if (empty($record)) {
            return null;
        }

        $validations = $record->validations()->where([
            'state' => 'approved'
        ])->select([
            'state', 'identity_address', 'created_at', 'updated_at',
            'organization_id'
        ])->distinct()->orderBy(
            'updated_at', 'DESC'
        )->get()->load('organization')->map(function(RecordValidation $validation) {
            return $validation->setAttribute(
                'email',
                $validation->organization ? null :$this->primaryEmailByAddress(
                    $validation->identity_address
                )
            );
        })->toArray();

        // Todo: validation state
        return [
            'id' => $record->id,
            'key' => $record->record_type->key,
            'value' => $record->value,
            'name' => $record->record_type->name,
            'order' => $record->order,
            'deleted' => !is_null($record->deleted_at),
            'record_category_id' => $record->record_category_id,
            'validations' => $validations
        ];
    }

    /**
     * @param string $identityAddress
     * @param string $typeKey
     * @param string $value
     * @param null $recordCategoryId
     * @param null $order
     * @return array|null
     */
    public function recordCreate(
        string $identityAddress,
        string $typeKey,
        string $value,
        $recordCategoryId = null,
        $order = null
    ): ?array {
        $typeId = $this->getTypeIdByKey($typeKey);

        if (!$typeId) {
            abort(403, trans('record.exceptions.unknown_record_type', [
                'type' => $typeKey
            ]));
        }

        if ($typeKey === 'primary_email' && Record::query()->where([
                'identity_address' => $identityAddress,
                'record_type_id' => $this->getTypeIdByKey('primary_email'),
            ])->count() > 0) {
            abort(403,'record.exceptions.primary_email_already_exists');
        }

        if ($typeKey === 'bsn') {
            abort(403,'record.exceptions.bsn_record_cant_be_created');
        }

        /** @var Record $record */
        $record = Record::create([
            'identity_address' => $identityAddress,
            'order' => $order ?: 0,
            'value' => $value,
            'record_type_id' => $typeId,
            'record_category_id' => $recordCategoryId,
        ]);

        return $this->recordGet($identityAddress, $record->id);
    }

    /**
     * Set bsn record
     * @param string $identityAddress
     * @param string $bsnValue
     * @return null|array
     */
    public function setBsnRecord(string $identityAddress, string $bsnValue): ?array
    {
        $recordType = $this->getTypeIdByKey('bsn');

        if (Record::where([
            'identity_address' => $identityAddress,
            'record_type_id' => $recordType,
        ])->exists()) {
            abort(403,'record.exceptions.bsn_record_cant_be_changed');
        }

        /** @var Record $record */
        $record = Record::create([
            'identity_address' => $identityAddress,
            'order' => 0,
            'value' => $bsnValue,
            'record_type_id' => $recordType,
            'record_category_id' => null,
        ]);

        return $this->recordGet($identityAddress, $record->id);
    }

    /**
     * Update record
     * @param string $identityAddress
     * @param mixed $recordId
     * @param mixed|null $recordCategoryId
     * @param integer|null $order
     * @return bool
     */
    public function recordUpdate(
        string $identityAddress,
        $recordId,
        $recordCategoryId = null,
        $order = null
    ): bool {
        $update = collect();
        $update->put('record_category_id', $recordCategoryId);

        if (is_numeric($order)) {
            $update->put('order', $order);
        }

        return !!Record::query()->where([
            'id' => $recordId,
            'identity_address' => $identityAddress
        ])->update($update->toArray());
    }

    /**
     * Delete record
     * @param string $identityAddress
     * @param mixed $recordId
     * @return bool
     * @throws \Exception
     */
    public function recordDelete(string $identityAddress, $recordId): bool
    {
        if (empty($record = Record::query()->where([
            'id' => $recordId,
            'identity_address' => $identityAddress
        ])->first())) {
            return false;
        }

        if ($record['record_type_id'] ==
            $this->getTypeIdByKey('primary_email')) {
            abort(403,'record.exceptions.cant_delete_primary_email', [
                'record_type_name' => $record->record_type->name
            ]);
        }

        return !!$record->delete();
    }

    /**
     * Sort records
     * @param string $identityAddress
     * @param array $orders
     * @return void
     */
    public function recordsSort(
        string $identityAddress,
        array $orders
    ) {
        $self = $this;

        collect($orders)->each(function(
            $recordId, $order
        ) use ($identityAddress, $self) {
            $self->recordUpdate($identityAddress, $recordId, null, $order);
        });
    }

    /**
     * Make record validation qr-code data
     * @param string $identityAddress
     * @param mixed $recordId
     * @return array|bool
     */
    public function makeValidationRequest(
        string $identityAddress,
        int $recordId
    ) {
        /** @var Record $record */
        $record =  Record::query()->where([
            'id' => $recordId,
            'identity_address' => $identityAddress
        ])->first();

        if (!$record) {
            return false;
        }

        return $record->validations()->create([
            'uuid' => token_generator()->generate(64),
            'identity_address' => null,
            'state' => 'pending'
        ])->only([
            'uuid'
        ]);
    }

    /**
     * Approve validation request
     *
     * @param string $identityAddress
     * @param string $validationUuid
     * @param int|null $organization_id
     * @param int|null $prevalidation_id
     * @return bool
     */
    public function approveValidationRequest(
        string $identityAddress,
        string $validationUuid,
        int $organization_id = null,
        int $prevalidation_id = null
    ): bool {
        $validation = RecordValidation::whereUuid($validationUuid)->first();
        $identity = Identity::whereAddress($identityAddress)->first();

        if (!$identity || !$validation || $validation->identity_address) {
            return false;
        }

        return (bool) $validation->update([
            'identity_address' => $identity->address,
            'prevalidation_id' => $prevalidation_id,
            'organization_id' => $organization_id,
            'state' => 'approved'
        ]);
    }

    /**
     * Decline validation request
     * @param string $identityAddress
     * @param string $validationUuid
     * @return bool
     */
    public function declineValidationRequest(
        string $identityAddress,
        string $validationUuid
    ): bool {
        $validation = RecordValidation::whereUuid($validationUuid)->first();
        $identity = Identity::whereAddress($identityAddress)->first();

        if (!$identity || $validation->identity_address) {
            return false;
        }

        return (bool)$validation->update([
            'identity_address' => $identity->address,
            'state' => 'declined'
        ]);
    }

    /**
     * Show validation request
     *
     * @param string $validationUuid
     * @return array|bool
     */
    public function showValidationRequest(
        string $validationUuid
    ) {
        /** @var RecordValidation $validation */
        $validation = RecordValidation::query()->where(
            'uuid', $validationUuid
        )->first();

        if (!$validation) {
            return false;
        }

        return array_merge($validation->only([
            'state', 'identity_address', 'uuid'
        ]), $validation->record->only([
            'value'
        ]), $validation->record->record_type->only([
            'key', 'name'
        ]));
    }
}