<?php
namespace App\Services\AuthService;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class BearerTokenGuard implements Guard
{
    protected $user;
    protected $request;
    protected $identityProvider;

        public function __construct(UserProvider $identityProvider, Request $request)
    {
        $this->user = NULL;
        $this->request = $request;
        $this->identityProvider = $identityProvider;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    public function guest()
    {
        return ! $this->check();
    }

    public function id()
    {
        if ($user = $this->user()) {
            return $this->user()->getAuthIdentifier();
        }
    }

    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        return $this->user = $this->identityProvider->retrieveByCredentials([
            'bearer_token' => $this->getTokenForRequest()
        ]);
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        return $this->request->bearerToken();
    }

    public function validate(array $credentials = [])
    {
        $bearerToken = explode(' ', $this->request->headers->get('Authorization'));
        $accessToken = count($bearerToken) == 2 ? $bearerToken[1] : null;

        $user = $this->identityProvider->retrieveByCredentials([
            'access_token' => $accessToken
        ]);

        if (! is_null($user)) {
            $this->setUser($user);

            return true;
        } else {
            return false;
        }
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

    public static function authenticate() {

    }
}