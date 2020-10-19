<?php

namespace App\Http\Requests;

use App\Models\Implementation;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Class BaseFormRequest
 * @package App\Http\Requests
 */
class BaseFormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    protected $message;

    /**
     * @return array
     */
    public function rules(): array {
        return [];
    }

    /**
     * @param string $message
     * @throws AuthorizationException
     */
    public function deny($message = 'This action is unauthorized.'): void {
        $this->message = $message;
        $this->failedAuthorization();
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization(): void {
        throw new AuthorizationException($this->message);
    }

    /**
     * @return string|null
     */
    public function auth_address(): ?string {
        return auth_address();
    }

    /**
     * @return string|null
     */
    public function client_type(): ?string {
        return client_type();
    }

    /**
     * @return string|null
     */
    public function client_version(): ?string {
        return client_version();
    }

    /**
     * @return string|null
     */
    public function implementation_key(): ?string {
        return implementation_key();
    }

    /**
     * @return Implementation|null
     */
    public function implementation_model(): ?Implementation {
        return Implementation::activeModel();
    }
}
