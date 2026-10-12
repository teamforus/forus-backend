<?php

namespace App\Services\WalletService\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Models\IdentityEmail;
use App\Rules\IdentityEmailMaxRule;
use App\Rules\IdentityEmailUniqueRule;
use App\Services\WalletService\Models\WalletDisclosure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @property-read WalletDisclosure $resource
 */
class WalletDisclosureResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $request = BaseFormRequest::createFrom($request);
        $identity = $request->identity();
        $email = $this->resource->email;
        $records = $this->resource->records;

        if (isset($records['wallet_bsn'])) {
            $records['wallet_bsn'] = Str::mask($records['wallet_bsn'], '*', 0, -4);
        }

        return [
            ...$this->resource->only(['id', 'fund_id']),
            'records' => $records,
            'email' => $email,
            'can_use_email' => $email !== null && !$identity->email &&
                Gate::forUser($identity)->allows('create', [IdentityEmail::class, $request->identityProxy2FAConfirmed()]) &&
                Validator::make(['email' => $email], [
                    'email' => [
                        'bail', 'required', ...$request->emailRules(),
                        new IdentityEmailUniqueRule(), new IdentityEmailMaxRule($identity->address),
                    ],
                ])->passes(),
            ...$this->makeTimestamps($this->resource->only(['verified_at', 'expires_at'])),
        ];
    }
}
