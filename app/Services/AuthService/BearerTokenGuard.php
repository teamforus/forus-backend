<?php
namespace App\Services\AuthService;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class BearerTokenGuard implements Guard
{
    protected UserProvider $identityProvider;
    protected ?Authenticatable $user;
    protected ?string $userBearerToken;

    public function __construct(UserProvider $identityProvider)
    {
        $this->user = null;
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
        $bearerToken = request()->bearerToken();

        if (!is_null($this->user) && $this->userBearerToken == $bearerToken) {
            return $this->user;
        }

        $this->userBearerToken = $bearerToken;

        return $this->user = $this->identityProvider->retrieveByCredentials([
            'bearer_token' => $bearerToken,
        ]);
    }

    /**
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->identityProvider->retrieveByCredentials([
            'access_token' => request()->bearerToken(),
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