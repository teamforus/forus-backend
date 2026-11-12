<?php

namespace App\Modules\DCP\Data;

use App\Modules\DCP\Enums\DCPResponseError;
use InvalidArgumentException;

readonly class DCPStatusResult
{
    /**
     * @param DCPResponseData $response
     * @param DCPResponseError|null $error
     */
    public function __construct(public DCPResponseData $response, public ?DCPResponseError $error = null)
    {
        if ($error === null && ($response->orderId === null || $response->remoteId === null ||
            $response->statusId === null)) {
            throw new InvalidArgumentException('DCP status responses require both order references and a status.');
        }
    }
}
