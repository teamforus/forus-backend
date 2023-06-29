<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Services\Forus\Notification\NotificationService;
use App\Traits\ThrottleWithMeta;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BaseFormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use ThrottleWithMeta;

    protected string $message;
    protected Implementation|null $implementationModel = null;

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
    public function deny(string $message = 'This action is unauthorized.'): void
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
        throw new AuthorizationException($this->message ?? null);
    }

    /**
     * @return string|null
     */
    public function auth_address(): ?string
    {
        return $this->identity()?->address;
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
        return Implementation::activeKey();
    }

    /**
     * @return Implementation|null
     */
    public function implementation(): ?Implementation
    {
        if ($this->implementationModel) {
            return $this->implementationModel;
        }

        return $this->implementationModel = Implementation::active();
    }

    /**
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return (bool) $this->auth_address();
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
     * @return string
     */
    public function qRule(): string
    {
        return 'nullable|string';
    }

    /**
     * @param int $max
     * @return string
     */
    public function perPageRule(int $max = 100): string
    {
        return "nullable|numeric|min:0|max:$max";
    }

    /**
     * @param ...$columns
     * @return string[]
     */
    public function orderByRules(...$columns): array
    {
        return [
            'order_by' => 'nullable|in:' . implode(',', $columns),
            'order_dir' => 'nullable|string|in:asc,desc',
        ];
    }

    /**
     * @param int $perPage
     * @param array $columns
     * @return array
     */
    public function sortableResourceRules(int $perPage = 100, array $columns = []): array
    {
        return array_merge([
            'q' => $this->qRule(),
            'per_page' => $this->perPageRule($perPage),
        ], $this->orderByRules(...$columns));
    }

    /**
     * @param array|string $types
     * @return array
     */
    public function resourceTypeRule(array|string $types = 'default'): array
    {
        return [
            'nullable',
            'string',
            Rule::in($types),
        ];
    }

    /**
     * @param $abilityOrRules
     * @param array $arguments
     * @return bool
     */
    public function gateAllows($abilityOrRules, array $arguments = []): bool
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
     * @return LogManager|null
     */
    public function logger(): ?LogManager
    {
        return logger();
    }

    /**
     * @return Identity|null
     */
    public function identity(): ?Identity
    {
        return $this->user() instanceof Identity ? $this->user() : null;
    }

    /**
     * @return IdentityProxy|Model|null
     */
    public function identityProxy(): IdentityProxy|Model|null
    {
        return $this->identity()?->proxies->where('access_token', $this->bearerToken())->first();
    }

    /**
     * @return bool
     */
    public function identityProxy2FAConfirmed(): bool
    {
        return $this->identityProxy()?->is2FAConfirmed();
    }

    /**
     * @return bool
     */
    public function isWebshop(): bool
    {
        return $this->client_type() == $this->implementation()::FRONTEND_WEBSHOP;
    }

    /**
     * @return bool
     */
    public function isSponsorDashboard(): bool
    {
        return $this->client_type() == $this->implementation()::FRONTEND_SPONSOR_DASHBOARD;
    }

    /**
     * @return bool
     */
    public function isProviderDashboard(): bool
    {
        return $this->client_type() == $this->implementation()::FRONTEND_PROVIDER_DASHBOARD;
    }

    /**
     * @return bool
     */
    public function isValidatorDashboard(): bool
    {
        return $this->client_type() == $this->implementation()::FRONTEND_VALIDATOR_DASHBOARD;
    }

    /**
     * @return bool
     */
    public function isDashboard(): bool
    {
        return $this->isSponsorDashboard() || $this->isProviderDashboard() || $this->isValidatorDashboard();
    }
}
