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
}