<?php

namespace App\Services\Forus\Record\Repositories;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Record\Models\Record;
use App\Services\Forus\Record\Models\RecordCategory;
use App\Services\Forus\Record\Models\RecordType;
use App\Services\Forus\Record\Models\RecordValidation;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;

class RecordRepo implements IRecordRepo
{
    /**
     * Create or update records for given identity
     * @param string $identityAddress
     * @param array $records
     * @return void
     */
    public function updateRecords(
        string $identityAddress,
        array $records
    ) {
        $recordTypes = RecordType::query()->pluck(
            'id', 'key'
        )->toArray();

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
     * @return array
     */
    public function getRecordTypes() {
        return RecordType::query()->get()->map(function(RecordType $recordType) {
            return $recordType->only(['id', 'key', 'name', 'type']);
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
    ) {
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
     * @param mixed $excludeIdentity
     * @return boolean
     */
    public function isRecordExists(
        string $recordTypeKey,
        string $recordValue,
        string $excludeIdentity = null
    ) {
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
            $record->where('identity_address', '!=', $excludeIdentity);
        }

        return $record->count() != 0;
    }


    /**
     * Get identity id by email record
     * @param string $email
     * @return mixed|null
     */
    public function identityAddressByEmail(
        string $email
    ) {
        return identity_repo()->getAddress($email);

        /*$record = Record::query()->where([
            'record_type_id' => $this->getTypeIdByKey('primary_email'),
            'value' => $email,
        ])->first();

        return $record ? $record->identity_address : null;*/
    }

    /**
     * Get identity_address by bsn
     * @param string $bsn
     * @return string|null
     */
    public function identityAddressByBsn(
        string $bsn
    ) {
        $record = Record::query()->where([
            'record_type_id' => $this->getTypeIdByKey('bsn'),
            'value' => $bsn,
        ])->first();

        return $record ? $record->identity_address : null;
    }

    /**
     * Get identity id by email record
     * @param string $identityAddress
     * @return string|null
     */
    public function primaryEmailByAddress(
        string $identityAddress
    ) {
        return identity_repo()->getPrimaryEmail($identityAddress);

        /*$record = Record::query()->where([
            'record_type_id' => $this->getTypeIdByKey('primary_email'),
            'identity_address' => $identityAddress,
        ])->first();

        return $record ? $record->value : null;*/
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
    public function bsnByAddress(
        string $identityAddress
    ) {
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
    public function getTypeIdByKey(
        string $key
    ) {
        $recordType = RecordType::query()->where('key', $key)->first();
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
    ) {
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
    public function categoriesList(
        string $identityAddress
    ) {
        return RecordCategory::query()->where([
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
    public function categoryGet(
        string $identityAddress,
        $recordCategoryId
    ) {
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
     * @param mixed $recordCategoryId
     * @param string|null $name
     * @param int|null $order
     * @return bool
     */
    public function categoryUpdate(
        string $identityAddress,
        $recordCategoryId,
        string $name = null,
        int $order = null
    ) {
        $record =  RecordCategory::query()->where([
            'id' => $recordCategoryId,
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
    public function categoriesSort(
        string $identityAddress,
        array $orders
    ) {
        $self = $this;

        collect($orders)->each(function(
            $categoryId, $order
        ) use ($identityAddress, $self) {
            $self->categoryUpdate($identityAddress, $categoryId, null, $order);
        });
    }

    /**
     * Delete category
     * @param string $identityAddress
     * @param mixed $recordCategoryId
     * @return mixed
     * @throws \Exception
     */
    public function categoryDelete(
        string $identityAddress,
        $recordCategoryId
    ) {
        /** @var RecordCategory $recordCategory */
        $recordCategory =  RecordCategory::query()->where([
            'id' => $recordCategoryId,
            'identity_address' => $identityAddress
        ])->first();

        if (!$recordCategory) {
            return false;
        }

        $recordCategory->records()->update([
            'record_category_id' => null
        ]);


        return !!$recordCategory->delete();
    }


    /**
     * Get identity records
     * @param string $identityAddress
     * @param string|null $type
     * @param integer|null $categoryId
     * @return array
     */
    public function recordsList(
        string $identityAddress,
        $type = null,
        $categoryId = null
    ) {

        // Todo: validation state
        $query = Record::query()->where([
            'identity_address' => $identityAddress
        ])->with([
            'record_type'
        ]);

        if ($type) {
            $recordType = RecordType::query()->where([
                'key' => $type
            ])->first();

            if ($recordType) {
                $query->where('record_type_id', $recordType->id);
            } else {
                return null;
            }
        }

        if ($categoryId) {
            $query->where('record_category_id', $categoryId);
        }

        return $query->orderBy('order')->get()->map(function(
            Record $record
        ) {
            $validations = $record->validations()->where([
                'state' => 'approved'
            ])->select([
                'state', 'identity_address', 'created_at', 'updated_at',
                'organization_id'
            ])->get()->load('organization')->map(function(
                RecordValidation $validation
            ) {
                return $validation->setAttribute(
                    'email',
                    $validation->organization ? null :$this->primaryEmailByAddress(
                        $validation->identity_address
                    )
                );
            });

            return [
                'id' => $record->id,
                'value' => $record->value,
                'order' => $record->order,
                'key' => $record->record_type->key,
                'name' => $record->record_type->name,
                'record_category_id' => $record->record_category_id,
                'validations' => $validations
            ];
        })->toArray();
    }

    /**
     * Get identity record
     * @param string $identityAddress
     * @param mixed $recordId
     * @return array
     */
    public function recordGet(
        string $identityAddress,
        $recordId
    ) {
        /** @var Record $record */
        $record = Record::query()->where([
            'id' => $recordId,
            'identity_address' => $identityAddress,
        ])->first();

        $validations = $record->validations()->where([
            'state' => 'approved'
        ])->select([
            'state', 'identity_address', 'created_at', 'updated_at',
            'organization_id'
        ])->distinct()->orderBy(
            'updated_at', 'DESC'
        )->get()->load('organization')->map(function(
            RecordValidation $validation
        ) {
            return $validation->setAttribute(
                'email',
                $validation->organization ? null :$this->primaryEmailByAddress(
                    $validation->identity_address
                )
            );
        })->toArray();

        // Todo: validation state
        return $record ? [
            'id' => $record->id,
            'value' => $record->value,
            'order' => $record->order,
            'key' => $record->record_type->key,
            'name' => $record->record_type->name,
            'record_category_id' => $record->record_category_id,
            'validations' => $validations
        ] : null;
    }

    /**
     * Add new record to identity
     * @param string $identityAddress
     * @param string $typeKey
     * @param string $value
     * @param mixed|null $recordCategoryId
     * @param integer|null $order
     * @return null|array
     */
    public function recordCreate(
        string $identityAddress,
        string $typeKey,
        string $value,
        $recordCategoryId = null,
        $order = null
    ) {
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
    public function setBsnRecord(
        string $identityAddress,
        string $bsnValue
    ) {
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
    ) {
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
     * Delete record
     * @param string $identityAddress
     * @param mixed $recordId
     * @return bool
     * @throws \Exception
     */
    public function recordDelete(
        string $identityAddress,
        $recordId
    ) {
        if (empty($record = Record::query()->where([
            'id' => $recordId,
            'identity_address' => $identityAddress
        ])->first())) {
            return false;
        }

        if ($record['record_type_id'] ==
            $this->getTypeIdByKey('primary_email')) {
            abort(403,'record.exceptions.cant_delete_primary_email');
        }

        return !!$record->delete();
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
     * @return bool
     */
    public function approveValidationRequest(
        string $identityAddress,
        string $validationUuid,
        int $organization_id = null
    ) {
        /** @var
         * Identity $identity
         */
        $validation = RecordValidation::query()->where(
            'uuid', $validationUuid
        )->first();

        $identity = Identity::query()->where([
            'address' => $identityAddress
        ])->first();

        if (!$identity || $validation->identity_address) {
            return false;
        }

        return !!$validation->update([
            'identity_address' => $identity->address,
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
    ) {
        /** @var
         * Identity $identity
         */
        $validation = RecordValidation::query()->where(
            'uuid', $validationUuid
        )->first();

        $identity = Identity::query()->where([
            'address' => $identityAddress
        ])->first();

        if (!$identity || $validation->identity_address) {
            return false;
        }

        return !!$validation->update([
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