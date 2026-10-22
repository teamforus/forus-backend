<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;

class DeleteRequestBodyExtension extends RequestBodyExtension
{
    public const HTTP_METHODS_WITHOUT_REQUEST_BODY = ['get', 'head'];
}
