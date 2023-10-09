<?php

namespace App\Http\Controllers\Api\Platform;

use App\Helpers\TmpFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FirestoreTokens\StoreFirestoreTokenRequest;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Config;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirestoreTokensController extends Controller
{
    /**
     * @throws AuthException
     * @throws FirebaseException
     */
    public function store(StoreFirestoreTokenRequest $request): array
    {
        $context = SystemConfig::where('key', 'firestore_context')->firstOrFail()?->value;
        $contextFile = new TmpFile($context);

        Config::set('firebase.projects.app.credentials.file', $contextFile->path());

        return [
            'token' => Firebase::auth()->createCustomToken($request->auth_address())->toString()
        ];
    }
}
