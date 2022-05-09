<?php

namespace App\Services\Forus\Record\Repositories\Interfaces;

interface IRecordRepo {
    /**
     * Create or update records for given identity
     * @param string $identityAddress
     * @param array $records
     * @return void
     */
    public function updateRecords(string $identityAddress, array $records): void;

    /**
     * Get list all available record type keys
     * @param bool $withSystem
     * @return array
     */
    public function getRecordTypes(bool $withSystem = true): array;

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
    ): bool;

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
    ): bool;

    /**
     * Get identity id by email record
     * @param string $email
     * @return mixed|null
     */
    public function identityAddressByEmail(string $email);

    /**
     * Get identity id by email record
     * @param string $identityAddress
     * @return string|null
     */
    public function primaryEmailByAddress(string $identityAddress): ?string;

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
    ): void;

    /**
     * Get bsn by identity_address
     * @param string $identityAddress
     * @return string|null
     */
    public function bsnByAddress(string $identityAddress): ?string;

    /**
     * Get identity_address by bsn
     * @param string $bsn
     * @return string|null
     */
    public function identityAddressByBsn(string $bsn): ?string;

    /**
     * Search identity_address by bsn
     * @param string $search
     * @return array
     */
    public function identityAddressByBsnSearch(string $search): array;

    /**
     * Get type id by key
     * @param string $key
     * @return int|null
     */
    public function getTypeIdByKey(string $key): ?int;

    /**
     * Add new record category to identity
     * @param string $identityAddress
     * @param string $name
     * @param int $order
     * @return array|null
     */
    public function categoryCreate(string $identityAddress, string $name, int $order = 0): ?array;

    /**
     * Get identity record categories
     * @param string $identityAddress
     * @return array
     */
    public function categoriesList(string $identityAddress): array;

    /**
     * Get identity record category
     * @param string $identityAddress
     * @param mixed $recordCategoryId
     * @return array|null
     */
    public function categoryGet(string $identityAddress, $recordCategoryId): ?array;

    /**
     * Update identity record category
     * @param string $identityAddress
     * @param mixed $categoryId
     * @param string|null $name
     * @param int|null $order
     * @return bool
     */
    public function categoryUpdate(
        string $identityAddress,
        string $categoryId,
        string $name,
        int $order = null
    ): bool;

    /**
     * Sort categories
     * @param string $identityAddress
     * @param array $orders
     * @return void
     */
    public function categoriesSort(string $identityAddress, array $orders): void;

    /**
     * Delete category
     * @param string $identityAddress
     * @param mixed $recordCategoryId
     * @return bool
     * @throws \Exception
     */
    public function categoryDelete(string $identityAddress, $recordCategoryId): bool;

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
    ): ?array;

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
    ): ?array;

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
    ): ?array;

    /**
     * set bsn record
     * @param string $identityAddress
     * @param string $bsnValue
     * @return null|array
     */
    public function setBsnRecord(string $identityAddress, string $bsnValue): ?array;

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
    ): bool;

    /**
     * Sort records
     * @param string $identityAddress
     * @param array $orders
     * @return void
     */
    public function recordsSort(string $identityAddress, array $orders);

    /**
     * Delete record
     * @param string $identityAddress
     * @param mixed $recordId
     * @return bool
     * @throws \Exception
     */
    public function recordDelete(string $identityAddress, $recordId): bool;

    /**
     * Make record validation qr-code data
     * @param string $identityAddress
     * @param mixed $recordId
     * @return mixed
     */
    public function makeValidationRequest(string $identityAddress, int $recordId);

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
    ): bool;

    /**
     * Decline validation request
     * @param string $identityAddress
     * @param string $validationUuid
     * @return bool
     */
    public function declineValidationRequest(string $identityAddress, string $validationUuid): bool;
}
