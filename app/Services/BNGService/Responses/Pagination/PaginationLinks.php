<?php

namespace App\Services\BNGService\Responses\Pagination;

use App\Services\BNGService\Data\ResponseData;
use App\Services\BNGService\Responses\Value;

class PaginationLinks extends Value
{
    /**
     * @param ResponseData $responseData
     */
    public function __construct(ResponseData $responseData)
    {
        parent::__construct($responseData);
        $data = $this->responseData->getData();

        $this->responseData->setData(is_array($data) ? array_merge($data, [
            'next' => $data['next'] ?? $data['Next'] ?? null,
            'last' => $data['last'] ?? $data['Last'] ?? null,
            'first' => $data['first'] ?? $data['First'] ?? null,
            'previous' => $data['previous'] ?? $data['Previous'] ?? null,
        ]) : [
            'next' => null,
            'last' => null,
            'first' => null,
            'previous' => null,
        ]);
    }

    /**
     * @return PaginationLink|null
     * @noinspection PhpUnused
     */
    public function getFirst(): ?PaginationLink
    {
        return $this->data['first'] ? new PaginationLink(new ResponseData($this->data['first'])) : null;
    }

    /**
     * @return PaginationLink|null
     * @noinspection PhpUnused
     */
    public function getLast(): ?PaginationLink
    {
        return $this->data['last'] ? new PaginationLink(new ResponseData($this->data['last'])) : null;
    }

    /**
     * @return PaginationLink|null
     * @noinspection PhpUnused
     */
    public function getNext(): ?PaginationLink
    {
        return $this->data['next'] ? new PaginationLink(new ResponseData($this->data['next'])) : null;
    }

    /**
     * @return PaginationLink|null
     * @noinspection PhpUnused
     */
    public function getPrevious(): ?PaginationLink
    {
        return $this->data['previous'] ? new PaginationLink(new ResponseData($this->data['previous'])) : null;
    }
}
