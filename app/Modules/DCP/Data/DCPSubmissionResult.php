<?php

namespace App\Modules\DCP\Data;

use App\Modules\DCP\Enums\DCPResponseError;
use App\Modules\DCP\Enums\DCPSubmissionOutcome;
use InvalidArgumentException;

readonly class DCPSubmissionResult
{
    /**
     * @param DCPSubmissionOutcome $outcome
     * @param DCPResponseData $response
     * @param DCPResponseError|null $error
     */
    public function __construct(
        public DCPSubmissionOutcome $outcome,
        public DCPResponseData $response,
        public ?DCPResponseError $error = null,
    ) {
        if (($outcome === DCPSubmissionOutcome::ACCEPTED) !== ($error === null)) {
            throw new InvalidArgumentException('Only accepted DCP submissions can have no error.');
        }

        if ($outcome === DCPSubmissionOutcome::ACCEPTED &&
            ($response->orderId === null || $response->remoteId === null)) {
            throw new InvalidArgumentException('Accepted DCP submissions require both order references.');
        }
    }
}
