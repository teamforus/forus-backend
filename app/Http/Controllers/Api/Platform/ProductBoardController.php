<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FeedbackProductBoard\StoreFeedbackProductBoardRequest;
use Illuminate\Http\JsonResponse;

/**
 * @noinspection PhpUnused
 */
class ProductBoardController extends Controller
{
    /**
     * @param StoreFeedbackProductBoardRequest $request
     * @return JsonResponse
     */
    public function store(StoreFeedbackProductBoardRequest $request): JsonResponse
    {
        $data = array_merge($request->only('title', 'content', 'customer_email'), [
            'tags' => array_filter([$request->input('urgency')]),
        ]);

        $apiResponse = resolve('productboard')->create($data);

        return new JsonResponse([], $apiResponse['response_code'] === 201 ? 201 : 400);
    }
}