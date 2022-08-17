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

        return array_merge($identity->only([
            'address',
        ]), $this->privateFields($request, $identity));
    }

    /**
     * @param BaseFormRequest $request
     * @param Identity $identity
     * @return array
     */
    protected function privateFields(BaseFormRequest $request, Identity $identity): array
    {
        $email = $request->isMeApp() ? $identity->email ?: 'Geen e-mailadres' : $identity->email;
        $bsnRecord = $identity->activeBsnRecord();

        if ($request->auth_address() === $identity->address) {
            return [
                'bsn' => !empty($bsnRecord),
                'bsn_time' => $bsnRecord ? now()->diffInSeconds($bsnRecord->created_at) : null,
                'email' => $email,
            ];
        }

        return [];
    }
}
