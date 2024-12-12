<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Http\Request;
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
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $request = BaseFormRequest::createFrom($request);
        $identity = $this->resource;

        return [
            'address' => $identity->address,
            ...$this->privateFields($request, $identity),
        ];
    }

    /**
     * @param BaseFormRequest $request
     * @param Identity $identity
     * @return array
     */
    protected function privateFields(BaseFormRequest $request, Identity $identity): array
    {
        $email = $request->isMeApp() ? $identity->email ?: 'Geen e-mailadres' : $identity->email;
        $bsnRecord = $identity->record_bsn;

        if ($request->auth_address() === $identity->address) {
            return [
                'bsn' => !empty($bsnRecord),
                'bsn_time' => $bsnRecord ? now()->diffInSeconds($bsnRecord->created_at, true) : null,
                'email' => $email,
                'profile' => $request->implementation()?->organization?->allow_profiles,
            ];
        }

        return [];
    }
}
