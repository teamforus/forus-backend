<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FeedbackProductBoard\StoreFeedbackProductBoardRequest;
use App\Http\Requests\Api\Platform\FeedbackProductBoard\ValidateFeedbackProductBoardRequest;
use Illuminate\Http\JsonResponse;

class FeedbackProductBoardController extends Controller
{
    /**
     * @param ValidateFeedbackProductBoardRequest $request
     * @return void
     */
    public function storeValidate(ValidateFeedbackProductBoardRequest $request): void {}

    /**
     * @param StoreFeedbackProductBoardRequest $request
     * @return JsonResponse
     */
    public function store(StoreFeedbackProductBoardRequest $request): JsonResponse
    {
        $data = $request->only(['title', 'content', 'tags']);
        if ($request->input('use_customer_email')) {
            $data = array_merge($data, [
                'customer_email' => resolve('forus.services.record')->primaryEmailByAddress(auth_address()),
            ]);
        }

        $apiResponse = resolve('productboard_api')->storeNote($data);

        return response()->json($data, $apiResponse['response_code']);
    }
}