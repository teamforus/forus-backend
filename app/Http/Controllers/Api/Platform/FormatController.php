<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FormatRequest;
use Illuminate\Http\JsonResponse;

class FormatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param FormatRequest $request
     * @return JsonResponse
     */
    public function format(FormatRequest $request): JsonResponse
    {
        $converter = resolve('markdown.converter');

        return new JsonResponse([
            'html' => $converter->convert($request->string('markdown'))->getContent(),
        ]);
    }
}