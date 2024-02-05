<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\Contact\SendContactFormRequest;
use App\Mail\ContactForm\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ContactFormController extends Controller
{
    /**
     * Get api availability state
     *
     * @param SendContactFormRequest $request
     * @return JsonResponse
     */
    public function send(SendContactFormRequest $request): JsonResponse
    {
        $data = $request->only([
            'name', 'email', 'phone', 'organization', 'message',
        ]);

        if ($email = Config::get('forus.notification_mails.contact_form', false)) {
            resolve('forus.services.notification')->sendSystemMail($email, new ContactFormMail($data));
        } else {
            Log::error('Contact form submitted but the feedback email is not set: ', $data);
        }

        return new JsonResponse();
    }
}
