<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use Illuminate\Http\Request;

/**
 * @property-read Identity $resource
 */
class ProfileResource extends BaseJsonResource
{
    public static $wrap = null;
    protected ?bool $profile = null;
    protected ?array $records = null;
    protected ?array $bank_accounts = null;

    public const array LOAD = [
        'record_bsn',
        'sessions',
    ];

    public const array LOAD_NESTED = [
        'emails' => IdentityEmailResource::class,
    ];

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
        $sessions = $identity->sessions;
        $bsnRecord = $identity->record_bsn;

        if ($request->auth_address() === $identity->address) {
            return [
                'bsn' => $bsnRecord?->value,
                'bsn_time' => $bsnRecord ? now()->diffInSeconds($bsnRecord->created_at, true) : null,
                'email' => $email,
                'email_verified' => $identity->emails
                    ->where('verified', true)
                    ->where('primary', false)
                    ->pluck('email'),
                'profile' => $this->profile,
                ...$this->profile ? [
                    'records' => $this?->records ?: [],
                    'bank_accounts' => $this?->bank_accounts ?: [],
                ] : [],
                ...static::makeTimestampsStatic([
                    'created_at' => $identity->created_at,
                    'last_login_at' => $sessions->sortByDesc('created_at')->first()?->created_at,
                    'last_activity_at' => $sessions->sortByDesc('last_activity_at')->first()?->last_activity_at,
                ]),
            ];
        }

        return [];
    }
}
