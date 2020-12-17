<?php

namespace App\Http\Requests;

use App\Models\Implementation;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use App\Traits\ThrottleWithMeta;
use Illuminate\Auth\Access\AuthorizationException;

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
    public function rules(): array {
        return [];
    }

    /**
     * @param string $message
     * @throws AuthorizationException
     * @noinspection PhpUnused
     */
    public function deny($message = 'This action is unauthorized.'): void {
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
    protected function failedAuthorization(): void {
        throw new AuthorizationException($this->message);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function auth_address(): ?string {
        return auth_address();
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function client_type(): ?string {
        return client_type();
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function client_version(): ?string {
        return client_version();
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function implementation_key(): ?string {
        return implementation_key();
    }

    /**
     * @return Implementation|null
     * @noinspection PhpUnused
     */
    public function implementation_model(): ?Implementation {
        return Implementation::activeModel();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isAuthorized(): bool {
        return (bool) $this->auth_address();
    }

    /**
     * @return IIdentityRepo
     * @noinspection PhpUnused
     */
    public function identity_repo(): IIdentityRepo {
        return resolve('forus.services.identity');
    }

    /**
     * @return IRecordRepo
     * @noinspection PhpUnused
     */
    public function records_repo(): IRecordRepo {
        return resolve('forus.services.record');
    }

    /**
     * @return NotificationService
     * @noinspection PhpUnused
     */
    public function notification_repo(): NotificationService {
        return resolve('forus.services.notification');
    }
}
