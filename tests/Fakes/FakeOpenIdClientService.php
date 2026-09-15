<?php

namespace Tests\Fakes;

use App\Services\OpenIdService\OpenIdClientService;
use Facile\OpenIDClient\Client\ClientInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class FakeOpenIdClientService extends OpenIdClientService
{
    protected ?array $claims = null;
    protected ?Throwable $authorizationException = null;

    /**
     * @param Throwable $exception
     * @return self
     */
    public function setAuthorizationException(Throwable $exception): self
    {
        $this->authorizationException = $exception;

        return $this;
    }

    /**
     * @param array $claims
     * @return self
     */
    public function setClaims(array $claims): self
    {
        $this->claims = $claims;

        return $this;
    }

    /**
     * @param array $config
     * @param array $authParams
     * @throws Throwable
     * @return array
     */
    public function buildAuthorization(array $config, array $authParams = []): array
    {
        if ($this->authorizationException) {
            throw $this->authorizationException;
        }

        $state = Str::random(32);

        return [
            'redirect_url' => 'https://login.example.test/authorize?' . http_build_query(['state' => $state]),
            'state' => $state,
            'nonce' => Str::random(32),
            'code_verifier' => Str::random(64),
        ];
    }

    /**
     * @param array $config
     * @param array $session
     * @param Request $request
     * @param ClientInterface|null $client
     * @return array
     */
    public function resolveCallback(
        array $config,
        array $session,
        Request $request,
        ?ClientInterface $client = null,
    ): array {
        if ($this->claims === null) {
            throw new LogicException('Configure callback claims before using the fake OpenID client.');
        }

        return ['claims' => $this->claims];
    }
}
