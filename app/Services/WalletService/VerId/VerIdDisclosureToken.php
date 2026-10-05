<?php

namespace App\Services\WalletService\VerId;

use App\Services\OpenIdService\OpenIdException;
use Facile\OpenIDClient\Client\ClientInterface;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES384;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Symfony\Component\Clock\NativeClock;
use Throwable;

class VerIdDisclosureToken
{
    /**
     * @param string $token
     * @param ClientInterface $client
     * @param string $challenge
     * @throws OpenIdException
     * @return array{header: array, payload: array}
     */
    public function verify(string $token, ClientInterface $client, string $challenge): array
    {
        try {
            $jws = (new CompactSerializer())->unserialize($token);
            $algorithms = new AlgorithmManager([new ES256(), new ES384(), new RS256()]);
            $headers = new HeaderCheckerManager([
                new AlgorithmChecker($algorithms->list(), true),
            ], [new JWSTokenSupport()]);
            $headers->check($jws, 0, ['alg']);

            $keys = $client->getIssuer()->getJwksProvider();
            $verifier = new JWSVerifier($algorithms);

            if (!$verifier->verifyWithKeySet($jws, JWKSet::createFromKeyData($keys->getJwks()), 0) &&
                !$verifier->verifyWithKeySet($jws, JWKSet::createFromKeyData($keys->reload()->getJwks()), 0)) {
                throw new OpenIdException('Invalid disclosure token signature.');
            }

            $payload = json_decode($jws->getPayload() ?? '', true, flags: JSON_THROW_ON_ERROR);
            $clock = new NativeClock();

            (new ClaimCheckerManager([
                new ExpirationTimeChecker(clock: $clock),
                new IssuedAtChecker(clock: $clock),
                new NotBeforeChecker(clock: $clock),
            ]))->check($payload);

            if ($challenge === '' || ($payload['parameter']['challenge'] ?? null) !== $challenge ||
                ($payload['disclosureUuid'] ?? null) !== $client->getMetadata()->getClientId()) {
                throw new OpenIdException('Disclosure token does not match this request.');
            }

            foreach (['meta', 'mapping', 'credentials'] as $key) {
                if (!is_array($payload[$key] ?? null)) {
                    throw new OpenIdException('Unsupported disclosure token payload.');
                }
            }

            if (!array_is_list($payload['credentials'])) {
                throw new OpenIdException('Invalid disclosure credentials.');
            }

            return ['header' => $jws->getSignature(0)->getProtectedHeader(), 'payload' => $payload];
        } catch (OpenIdException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OpenIdException('Unable to verify disclosure token: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
