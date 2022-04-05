<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use App\Traits\ThrottleWithMeta;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

/**
 * Class BaseFormRequest
 * @package App\Http\Requests
 */
class BaseFormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use ThrottleWithMeta;

    protected $message;

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @param string $message
     * @throws AuthorizationException
     * @noinspection PhpUnused
     */
    public function deny($message = 'This action is unauthorized.'): void
    {
        $this->message = $message;
        $this->failedAuthorization();
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException($this->message);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function auth_address(): ?string
    {
        return auth_address();
    }

    /**
     * @param bool $abortOnFail
     * @param int $errorCode
     * @return string|null
     * @noinspection PhpUnused
     */
    public function auth_proxy_id($abortOnFail = false, $errorCode = 403): ?string
    {
        $auth = auth_model($abortOnFail, $errorCode);

        return $auth && method_exists($auth, 'getProxyId') ? $auth->getProxyId() : null;
    }

    /**
     * @param string|null $default
     * @return string|null
     * @noinspection PhpUnused
     */
    public function client_type(?string $default = null): ?string
    {
        return $this->header('Client-Type', $default);
    }

    /**
     * @param int|null $default
     * @return int|null
     * @noinspection PhpUnused
     */
    public function client_version(?int $default = null): ?int
    {
        return $this->header('Client-Version', $default);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function implementation_key(): ?string
    {
        return implementation_key();
    }

    /**
     * @return Implementation|null
     * @noinspection PhpUnused
     */
    public function implementation(): Implementation
    {
        return Implementation::active();
    }

    /**
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return (bool) $this->auth_address();
    }

    /**
     * @return IIdentityRepo
     * @noinspection PhpUnused
     */
    public function identity_repo(): IIdentityRepo
    {
        return resolve('forus.services.identity');
    }

    /**
     * @return IRecordRepo
     * @noinspection PhpUnused
     */
    public function records_repo(): IRecordRepo
    {
        return resolve('forus.services.record');
    }

    /**
     * @return NotificationService
     * @noinspection PhpUnused
     */
    public function notification_repo(): NotificationService
    {
        return resolve('forus.services.notification');
    }

    /**
     * @param int $max
     * @return string
     */
    public function perPageRule($max = 100): string
    {
        return "nullable|numeric|min:0|max:$max";
    }

    /**
     * @param $abilityOrRules
     * @param array $arguments
     * @return bool
     */
    public function gateAllows($abilityOrRules, $arguments = []): bool
    {
        if (is_array($abilityOrRules)) {
            foreach ($abilityOrRules as $ability => $arguments) {
                if (!Gate::allows($ability, $arguments)) {
                    return false;
                }
            }

            return true;
        }

        return Gate::allows($abilityOrRules, $arguments);
    }

    /**
     * @return bool
     */
    public function isMeApp(): bool
    {
        return in_array($this->client_type(), config('forus.clients.mobile'));
    }

    /**
     * @param Organization $organization
     * @return Employee|null
     */
    public function employee(Organization $organization): ?Employee
    {
        return $organization->findEmployee($this->auth_address());
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Log\LogManager|null
     */
    public function logger() {
        return logger();
    }
}
