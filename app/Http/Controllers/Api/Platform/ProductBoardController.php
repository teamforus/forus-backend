<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FeedbackProductBoard\StoreFeedbackProductBoardRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;

/**
 * @noinspection PhpUnused
 */
class ProductBoardController extends Controller
{
    use ThrottleWithMeta;

    public function __construct()
    {
        $this->maxAttempts = env('PRODUCTBOARD_API_ATTEMPTS', 5);
        $this->decayMinutes = env('PRODUCTBOARD_API_DECAY', 5);
    }

    /**
     * @param StoreFeedbackProductBoardRequest $request
     * @return JsonResponse
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function store(StoreFeedbackProductBoardRequest $request): JsonResponse
    {
        $this->throttleWithKey('to_many_calls', $request, 'productboard');

        $data = array_merge($request->only('title', 'content', 'customer_email'), [
            'tags' => array_filter([$request->input('urgency')]),
        ]);

        $apiResponse = resolve('productboard')->create($data);

        return new JsonResponse([], $apiResponse['response_code'] === 201 ? 201 : 400);
    }
}