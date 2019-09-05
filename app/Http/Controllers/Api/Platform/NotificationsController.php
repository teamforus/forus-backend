<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    private $identityRepo;
    private $notificationPreferencesRepo;
    private $recordRepo;

    public function __construct()
    {
        $this->identityRepo = resolve('forus.services.identity');
        $this->notificationPreferencesRepo = resolve('forus.services.notifications');
        $this->recordRepo = resolve('forus.services.record');
    }

    public function index(string $identity_address): JsonResponse
    {
        $preferences = $this->notificationPreferencesRepo->getNotificationPreferences(
            $identity_address
        );
        $email = $this->recordRepo->primaryEmailByAddress($identity_address);

        return response()->json([
            'email' => $email,
            'preferences' => $preferences
        ]);
    }

    public function unsubscribe(
        string $identity_address,
        string $exchange_token
    ): JsonResponse {

        $proxy = $this->identityRepo->activateEmailPreferencesToken($exchange_token);

        if ($proxy ->identity_address != $identity_address) {
            return response()->json(['success' => false]);
        }

        $this->notificationPreferencesRepo->unsubscribeForIdentity($proxy->identity_address);

        $email = $this->recordRepo->primaryEmailByAddress($proxy->identity_address);

        return response()->json([
            'email' => $email,
            'success' => true
        ]);
    }

    public function update(
        Request $request,
        string $identity_address,
        string $exchange_token
    ): JsonResponse {
        $proxy = $this->identityRepo->activateEmailPreferencesToken($exchange_token);

        if ($proxy ->identity_address != $identity_address) {
            return response()->json(['success' => false]);
        }

        $this->notificationPreferencesRepo->updateForIdentity(
            $proxy->identity_address,
            $request->get('data')
        );

        $email = $this->recordRepo->primaryEmailByAddress($proxy->identity_address);

        return response()->json([
            'email' => $email,
            'success' => true
        ]);
    }
}
