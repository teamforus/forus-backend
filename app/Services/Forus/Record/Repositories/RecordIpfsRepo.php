<?php

namespace App\Services\Forus\Record\Repositories;

use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;

class RecordIpfsRepo implements IRecordRepo
{
    private $serviceUrl;
    private $apiRequest;

    public function __construct(string $serviceUrl) {
        $this->serviceUrl = $serviceUrl;
        $this->apiRequest = app()->make('api_request');
    }

    /**
     * Create or update records for given identity
     * @param $identityId
     * @param array $records
     * @return void
     */
    public function updateRecords(
        $identityId,
        array $records
    ) {
        $this->apiRequest->post(
            $this->serviceUrl . '/update-records', compact(
                'identityId', 'records'
            )
        );
    }

    /**
     * Get list all available record type keys
     * @return array
     */
    public function getRecordTypes() {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/get-record-types'
        )->getBody();

        return collect($response)->toArray();
    }

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
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/is-record-unique', compact(
                'recordTypeKey', 'recordValue', 'excludeIdentity'
            )
        )->getBody();

        return !!$response;
    }
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
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/is-record-exists', compact(
                'recordTypeKey', 'recordValue', 'excludeIdentity'
            )
        )->getBody();

        return !!$response;
    }


    /**
     * Get identity id by email record
     * @param string $email
     * @return mixed|null
     */
    public function identityIdByEmail(
        string $email
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/identity-id-by-email', compact('email')
        )->getBody();

        return $response ? $response : null;
    }


    /**
     * Get type id by key
     * @param string $key
     * @return int|null
     */
    public function getTypeIdByKey(
        string $key
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/get-type-id-by-key', compact('key')
        )->getBody();

        return $response ? intval($response) : null;
    }

    /**
     * Add new record category to identity
     * @param mixed $identityId
     * @param string $name
     * @param int $order
     * @return array|null
     */
    public function categoryCreate(
        $identityId,
        string $name,
        int $order
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/category-create', compact(
                'identityId', 'name', 'order'
            )
        )->getBody();

        return $this->categoryGet($identityId, $response);
    }

    /**
     * Get identity record categories
     * @param mixed $identityId
     * @return array
     */
    public function categoriesList(
        $identityId
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/category-create', compact('identityId')
        )->getBody();

        return collect($response)->toArray();
    }

    /**
     * Get identity record category
     * @param mixed $identityId
     * @param mixed $recordCategoryId
     * @return array|null
     */
    public function categoryGet(
        $identityId,
        $recordCategoryId
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/category-create', compact(
                'identityId', 'recordCategoryId'
            )
        )->getBody();

        return $response ? collect($response)->toArray() : null;
    }

    /**
     * Update identity record category
     * @param mixed $identityId
     * @param mixed $recordCategoryId
     * @param string|null $name
     * @param int|null $order
     * @return bool
     */
    public function categoryUpdate(
        $identityId,
        $recordCategoryId,
        string $name = null,
        int $order = null
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/category-update', compact(
                'identityId', 'recordCategoryId', 'name', 'order'
            )
        )->getBody();

        return !!$response;
    }

    /**
     * Sort categories
     * @param mixed $identityId
     * @param array $orders
     * @return void
     */
    public function categoriesSort(
        $identityId,
        array $orders
    ) {
        $this->apiRequest->post(
            $this->serviceUrl . '/categories-sort', compact(
                'identityId', 'orders'
            )
        );
    }

    /**
     * Delete category
     * @param mixed $identityId
     * @param mixed $recordCategoryId
     * @return mixed
     * @throws \Exception
     */
    public function categoryDelete(
        $identityId,
        $recordCategoryId
    ) {
        return !!$this->apiRequest->post(
            $this->serviceUrl . '/category-delete', compact(
                'identityId', 'recordCategoryId'
            )
        )->getBody();
    }

    /**
     * Get identity records
     * @param mixed $identityId
     * @param string|null $type
     * @return array
     */
    public function recordsList(
        $identityId,
        $type
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/records-list', compact(
                'identityId', 'type'
            )
        )->getBody();

        return collect($response)->toArray();
    }

    /**
     * Get identity record
     * @param mixed $identityId
     * @param mixed $recordId
     * @return array
     */
    public function recordGet(
        $identityId,
        $recordId
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/record-get', compact(
                'identityId','recordId'
            )
        )->getBody();

        return collect($response)->toArray();
    }

    /**
     * Add new record to identity
     * @param mixed $identityId
     * @param string $typeKey
     * @param string $value
     * @param mixed|null $recordCategoryId
     * @param integer|null $order
     * @return null|array
     * @throws \Exception
     */
    public function recordCreate(
        $identityId,
        string $typeKey,
        string $value,
        $recordCategoryId = null,
        $order = null
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/record-create', compact(
                'identityId','typeKey', 'value', 'recordCategoryId',
                'order'
            )
        )->getBody();

        return $response ? collect($response)->toArray() : null;
    }

    /**
     * Update record
     * @param mixed $identityId
     * @param mixed $recordId
     * @param mixed|null $recordCategoryId
     * @param integer|null $order
     * @return bool
     */
    public function recordUpdate(
        $identityId,
        $recordId,
        $recordCategoryId = null,
        $order = null
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/record-update', compact(
                'identityId','recordId', 'recordCategoryId', 'order'
            )
        )->getBody();

        return !!$response;
    }

    /**
     * Sort records
     * @param mixed $identityId
     * @param array $orders
     * @return void
     */
    public function recordsSort(
        $identityId,
        array $orders
    ) {
        $this->apiRequest->post(
            $this->serviceUrl . '/records-sort', compact(
                'identityId','orders'
            )
        );
    }

    /**
     * Delete record
     * @param mixed $identityId
     * @param mixed $recordId
     * @return bool
     * @throws \Exception
     */
    public function recordDelete(
        $identityId,
        $recordId
    ) {
        $response = $this->apiRequest->post(
            $this->serviceUrl . '/record-delete', compact(
                'identityId','recordId'
            )
        )->getBody();

        return !!$response;
    }
}