<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use Illuminate\Http\Request;

/**
 * @property-read Identity $resource
 * @property-read bool $profile
 * @property-read array $records
 * @property-read array $bank_accounts
 */
class ProfileResource extends BaseJsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
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
                'email_verified' => $identity->emails
                    ->where('verified', true)
                    ->where('primary', false)
                    ->pluck('email'),
                'profile' => $this->profile,
                ...$this->profile ? [
                    'records' => $this?->records?: [],
                    'bank_accounts' => $this?->bank_accounts?: [],
                ] : [],
                ...static::makeTimestampsStatic([
                    'created_at' => $identity->created_at,
                    'last_activity_at' => array_first($identity->sessions)?->last_activity_at,
                ]),
            ];
        }

        return [];
    }
}
