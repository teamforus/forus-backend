<?php

declare(strict_types = 1);

namespace App\Http\Responses;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class NoContentResponse extends Response
{
    public function __construct()
    {
        parent::__construct(null, ResponseAlias::HTTP_NO_CONTENT);
    }
}
