<?php

namespace App\Http\Controllers\Api\Platform;

use App\Helpers\Markdown;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FormatRequest;
use Illuminate\Http\JsonResponse;
use League\CommonMark\Exception\CommonMarkException;

class FormatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param FormatRequest $request
     * @return JsonResponse
     * @throws CommonMarkException
     */
    public function format(FormatRequest $request): JsonResponse
    {
        return new JsonResponse([
            'html' => Markdown::convert(e($request->string('markdown'))),
        ]);
    }
}