<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\Identity\IdentityDestroyRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailRedirectRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityStoreValidateEmailRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\IdentityResource;
use App\Mail\Forus\IdentityDestroyRequestMail;
use App\Models\Implementation;
use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class IdentityController extends Controller
{
    use ThrottleWithMeta;
}
