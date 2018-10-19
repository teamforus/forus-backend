<?php

namespace App\Services\Forus\Record\Repositories\Interfaces;

interface IRecordRepo {
    /**
     * Create or update records for given identity
     * @param $identityAddress
     * @param array $records
     * @return \Illuminate\Support\Collection
     */
    public function updateRecords(
        string $identityAddress,
        array $records
    );

    /**
    * Get list all available record type keys
    * @return array
    */
    public function getRecordTypes();


    /**
     * Check if record type and value is unique
     * @param string $recordTypeKey
     * @param string $recordValue
     * @param mixed $excludeIdentity
     * @return boolean
     * @throws \Exception
     */
    public function isRecordUnique(
        string $recordTypeKey,
        string $recordValue,
        string $excludeIdentity = null
    );

    /**
     * Check if record type and value is already existing
     * @param string $recordTypeKey
     * @param string $recordValue
     * @param mixed $excludeIdentity
     * @return boolean
     * @throws \Exception
     */
    public function isRecordExists(
        string $recordTypeKey,
        string $recordValue,
        string $excludeIdentity = null
    );


    /**
     * Get identity id by email record
     * @param string $email
     * @return mixed|null
     */
    public function identityIdByEmail(
        string $email
    );


    /**
     * Get identity id by email record
     * @param string $identityAddress
     * @return mixed|null
     */
    public function primaryEmailByAddress(
        string $identityAddress
    );

    /**
     * Get type id by key
     * @param string $key
     * @return int|null
     */
    public function getTypeIdByKey(
        string $key
    );

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
    );

    /**
     * Get identity record categories
     * @param string $identityAddress
     * @return array
     */
    public function categoriesList(
        string $identityAddress
    );

    /**
     * Get identity record category
     * @param string $identityAddress
     * @param mixed $recordCategoryId
     * @return array|null
     */
    public function categoryGet(
        string $identityAddress,
        $recordCategoryId
    );

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
        string $name,
        int $order = null
    );

    /**
     * Sort categories
     * @param string $identityAddress
     * @param array $orders
     * @return void
     */
    public function categoriesSort(
        string $identityAddress,
        array $orders
    );

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
    );

    /**
     * Get identity records
     * @param string $identityAddress
     * @param string|null $type
     * @param integer|null $categoryId
     * @return array
     */
    public function recordsList(
        string $identityAddress,
        $type,
        $categoryId
    );

    /**
     * Get identity record
     * @param string $identityAddress
     * @param mixed $recordId
     * @return array
     */
    public function recordGet(
        string $identityAddress,
        $recordId
    );

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
    );

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
    );

    /**
     * Sort records
     * @param string $identityAddress
     * @param array $orders
     * @return void
     */
    public function recordsSort(
        string $identityAddress,
        array $orders
    );

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
    );

    /**
     * Make record validation qr-code data
     * @param string $identityAddress
     * @param mixed $recordId
     * @return mixed
     */
    public function makeValidationRequest(
        string $identityAddress,
        int $recordId
    );

    /**
     * Approve validation request
     * @param string $identityAddress
     * @param string $validationUuid
     * @return bool
     */
    public function approveValidationRequest(
        string $identityAddress,
        string $validationUuid
    );

    /**
     * Decline validation request
     * @param string $identityAddress
     * @param string $validationUuid
     * @return bool
     */
    public function declineValidationRequest(
        string $identityAddress,
        string $validationUuid
    );
}
