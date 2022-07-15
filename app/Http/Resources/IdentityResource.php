<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;

/**
 * @property-read Identity $resource
 */
class IdentityResource extends BaseJsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $request = BaseFormRequest::createFrom($request);
        $identity = $this->resource;
        $email = $request->isMeApp() ? $identity->email ?: $identity->address : $identity->email;

        return array_merge([
            'address' => $identity->address,
        ], $request->auth_address() === $identity->address ? [
            'bsn' => !empty($identity->bsn),
            'email' => $email,
        ] : []);
    }
}
