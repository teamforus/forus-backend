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

        if (is_array($data)) {
            $this->responseData->setData(array_merge($data, [
                'next' => $data['next'] ?? $data['Next'] ?? null,
                'last' => $data['last'] ?? $data['Last'] ?? null,
                'first' => $data['first'] ?? $data['First'] ?? null,
                'previous' => $data['previous'] ?? $data['Previous'] ?? null,
            ]));
        }
    }

    /**
     * @return PaginationLink|null
     * @noinspection PhpUnused
     */
    public function getLast(): ?PaginationLink
    {
        return $this->data['last'] ? new PaginationLink(new ResponseData($this->data['last'])) : null;
    }
}