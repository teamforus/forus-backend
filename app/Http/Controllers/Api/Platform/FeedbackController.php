<?php

namespace App\Http\Controllers\Api\Platform;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Feedback\StoreFeedbackRequest;
use App\Mail\ContactForm\ContactFormMail;
use App\Mail\FeedbackForm\FeedbackFormMail;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * @noinspection PhpUnused
 */
class FeedbackController extends Controller
{
    use ThrottleWithMeta;

    public function __construct()
    {
        $this->maxAttempts = Config::get('forus.throttles.feedback_form.attempts');
        $this->decayMinutes = Config::get('forus.throttles.feedback_form.decay');
    }

    /**
     * @param StoreFeedbackRequest $request
     * @return JsonResponse
     * @throws AuthorizationJsonException
     */
    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $this->throttleWithKey('to_many_attempts', $request, 'feedback_form');

        $data = array_merge($request->only('title', 'content'), [
            'customer_email' => $request->input('customer_email') ?: '-',
            'urgency' => $request->input('urgency') ?: '-',
        ]);

        if ($email = Config::get('forus.notification_mails.feedback_form', false)) {
            resolve('forus.services.notification')->sendSystemMail($email, new FeedbackFormMail($data));
        } else {
            Log::error('Feedback form submitted but the feedback email is not set: ', $data);
        }

        return new JsonResponse([]);
    }
}