<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Feedback\StoreFeedbackRequest;
use App\Mail\FeedbackForm\FeedbackFormMail;
use Illuminate\Http\JsonResponse;

/**
 * @noinspection PhpUnused
 */
class FeedbackController extends Controller
{
    /**
     * @param StoreFeedbackRequest $request
     * @return JsonResponse
     */
    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $data = array_merge($request->only('title', 'content'), [
            'customer_email' => $request->input('customer_email') ?: '-',
            'urgency' => $request->input('urgency') ?: '-',
        ]);

        $notificationService = resolve('forus.services.notification');
        $notificationService->sendSystemMail('feedback@forus.io', new FeedbackFormMail($data));

        return new JsonResponse();
    }
}