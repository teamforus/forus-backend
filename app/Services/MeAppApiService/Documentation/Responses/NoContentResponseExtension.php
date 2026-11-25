<?php

namespace App\Services\MeAppApiService\Documentation\Responses;

use App\Http\Responses\NoContentResponse;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Type\Type;

class NoContentResponseExtension extends TypeToSchemaExtension
{
    /**
     * @param Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type->isInstanceOf(NoContentResponse::class);
    }

    /**
     * @param Type $type
     * @return Response
     */
    public function toResponse(Type $type): Response
    {
        return Response::make(204)->setDescription('No content');
    }
}
