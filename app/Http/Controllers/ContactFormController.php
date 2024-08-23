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
            'name', 'email', 'phone', 'organization_name', 'message',
            'accept_product_update_terms', 'accept_privacy_terms',
        ]);

        if ($email = Config::get('forus.notification_mails.contact_form', false)) {
            resolve('forus.services.notification')->sendSystemMail($email, new ContactFormMail(array_merge($data, [
                'accept_product_update_terms' => $data['accept_product_update_terms'] ? 'Ja' : 'Nee',
                'accept_privacy_terms' => $data['accept_privacy_terms'] ? 'Ja' : 'Nee',
            ])));
        } else {
            Log::error('Contact form submitted but the feedback email is not set: ', $data);
        }

        return new JsonResponse();
    }
}
