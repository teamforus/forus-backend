<?php

namespace App\Services\MeAppApiService\Documentation\Responses;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Contracts\View\View;

class ViewResponse extends TypeToSchemaExtension
{
    /**
     * @param Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type->isInstanceOf(View::class);
    }

    /**
     * @param Type $type
     * @return Response
     */
    public function toResponse(Type $type): Response
    {
        return Response::make(200)->setContent('text/html', Schema::fromType(new StringType()));
    }
}
