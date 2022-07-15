<?php
namespace App\Services\AuthService;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class BearerTokenGuard implements Guard
{
    protected Request $request;
    protected UserProvider $identityProvider;
    protected ?Authenticatable $user;

    public function __construct(UserProvider $identityProvider, Request $request)
    {
        $this->user = null;
        $this->request = $request;
        $this->identityProvider = $identityProvider;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * @return int|mixed|string|null
     */
    public function id(): mixed
    {
        return $this->user() ? $this->user()->getAuthIdentifier() : null;
    }

    /**
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        return $this->user = $this->identityProvider->retrieveByCredentials([
            'bearer_token' => $this->getTokenForRequest()
        ]);
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest(): ?string
    {
        return $this->request->bearerToken();
    }

    /**
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->identityProvider->retrieveByCredentials([
            'access_token' => $this->request->bearerToken(),
        ]);

        if (!is_null($user)) {
            $this->setUser($user);
            return true;
        }

        return false;
    }

    /**
     * Set the current user.
     *
     * @param Authenticatable $user
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * @return bool
     */
    public function hasUser(): bool
    {
        return !is_null($this->user);
    }
}