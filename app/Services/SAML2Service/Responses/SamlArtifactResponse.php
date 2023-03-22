<?php

namespace App\Services\SAML2Service\Responses;

use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Lib\Saml2User;
use App\Services\SAML2Service\Lib\Settings;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\SignedElement;
use SAML2\Response;
use SAML2\XML\saml\SubjectConfirmationData;
use Throwable;

class SamlArtifactResponse
{
    protected Response $response;
    protected Settings $settings;

    public function __construct(Response $response, Settings $settings)
    {
        $this->response = $response;
        $this->settings = $settings;
    }

    /**
     * @throws Throwable
     */
    public function getUser(): Saml2User
    {
        return new Saml2User(last($this->getAssertions()));
    }

    /**
     * @throws Throwable
     */
    public function getAssertions(): array
    {
        return $this->processArtifactResponseBody();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getId(): string
    {
        return $this->response->getId();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getInResponseTo(): string
    {
        return $this->response->getInResponseTo();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isSuccess(): bool
    {
        return $this->response->isSuccess();
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getStatusMessage(): ?string
    {
        return $this->response->getStatus()['Message'] ?? null;
    }

    /**
     * @param bool $truncate
     * @return string|null
     */
    public function getStatusCode(bool $truncate = true): ?string
    {
        return $this->truncateStatus($this->response->getStatus()['Code'] ?? null, $truncate);
    }

    /**
     * @param bool $truncate
     * @return string|null
     */
    public function getStatusSubCode(bool $truncate = true): ?string
    {
        return $this->truncateStatus($this->response->getStatus()['SubCode'] ?? null, $truncate);
    }

    /**
     * @param string|null $status
     * @param bool $truncate
     * @return string|null
     */
    protected function truncateStatus(?string $status, bool $truncate = true): ?string
    {
        if ($status && $truncate && str_starts_with($status, Constants::STATUS_PREFIX)) {
            return substr($status, strlen(Constants::STATUS_PREFIX));
        }

        return $status;
    }

    /**
     * @return Assertion|null
     * @throws Saml2Exception
     * @throws Throwable
     */
    protected function processArtifactResponseBody(): ?array
    {
        if (!$this->isSuccess()) {
            return null;
        }

        // validate Response-element destination
        $destination = $this->response->getDestination();
        $assertions = $this->response->getAssertions();

        if ($destination !== null && $destination !== url()->current()) {
            throw new Saml2Exception(sprintf(implode(' ', [
                'Destination in response does\'t match the current URL. Destination is "%s",',
                'current URL is "%s".',
            ]), $destination, url()->current()));
        }

        if (empty($this->response->getAssertions())) {
            throw new Saml2Exception('No assertions found in response from IdP.');
        }

        if (!self::checkSign($this->settings->getSPXmlSecurityKey(), $this->response)) {
            throw new Saml2Exception('The response was signed.');
        }

        $this->checkAssertion($assertions);

        return $assertions;
    }

    /**
     * Check the signature on a SAML2 message or assertion.
     *
     * @param XMLSecurityKey $key
     * @param SignedElement $element
     * @return bool
     * @throws Saml2Exception
     */
    protected function checkSign(XMLSecurityKey $key, SignedElement $element): bool
    {
        try {
            if ($element->validate($key)) {
                return true;
            }
        } catch (Throwable $e) {
            throw new Saml2Exception(get_class($element) . ' ' . $e->getMessage());
        }

        throw new Saml2Exception(get_class($element) . ' response sign check failed');
    }

    /**
     * Process an assertion in a response.
     *
     * @param Assertion[] $assertions The assertion.
     * @throws Saml2Exception
     */
    protected function checkAssertion(array $assertions): void
    {
        foreach ($assertions as $assertion) {
            $this->checkAssertionTime($assertion);
            $this->checkAssertionAudience($assertion);
            $this->checkAssertionSubjectConfirmation($assertion);
        }
    }

    /**
     * @param Assertion $assertion
     * @return void
     * @throws Saml2Exception
     */
    protected function checkAssertionTime(Assertion $assertion): void
    {
        // check various properties of the assertion
        $allowed_clock_skew = $this->settings->getOptional('assertion.allowed_clock_skew', 180);
        $allowed_clock_skew = filter_var($allowed_clock_skew, FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 180,
                'min_range' => 180,
                'max_range' => 300,
            ],
        ]);

        $notBefore = $assertion->getNotBefore();
        $notOnOrAfter = $assertion->getNotOnOrAfter();
        $sessionNotOnOrAfter = $assertion->getSessionNotOnOrAfter();

        if ($notBefore !== null && $notBefore > time() + $allowed_clock_skew) {
            throw new Saml2Exception(implode(' ', [
                'Received an assertion that is valid in the future.',
                'Check clock synchronization on IdP and SP.',
            ]));
        }

        if ($notOnOrAfter !== null && $notOnOrAfter <= time() - $allowed_clock_skew) {
            throw new Saml2Exception(implode('', [
                'Received an assertion that has expired.',
                'Check clock synchronization on IdP and SP.',
            ]));
        }

        if ($sessionNotOnOrAfter !== null && $sessionNotOnOrAfter <= time() - $allowed_clock_skew) {
            throw new Saml2Exception(implode(' ', [
                'Received an assertion with a session that has expired.',
                'Check clock synchronization on IdP and SP.',
            ]));
        }
    }

    /**
     * @param Assertion $assertion
     * @return void
     * @throws Saml2Exception
     */
    protected function checkAssertionAudience(Assertion $assertion): void
    {
        $audiences = $assertion->getValidAudiences();
        $audienceList = is_array($audiences) ? implode('], [', $audiences) : null;
        $spId = $this->settings->getSPId();

        if ($audiences !== null && !in_array($spId, $audiences, true)) {
            throw new Saml2Exception(implode(' ', [
                "This SP [$spId] is not a valid audience for the assertion.",
                "Candidates were: [$audienceList]."
            ]));
        }
    }

    /**
     * @param Assertion $assertion
     * @return void
     * @throws Saml2Exception
     */
    protected function checkAssertionSubjectConfirmation(Assertion $assertion): void
    {
        $validSCMethods = [Constants::CM_BEARER];

        foreach ($assertion->getSubjectConfirmation() as $sc) {
            $method = $sc->getMethod();
            $subjectConfirmationData = $sc->getSubjectConfirmationData();

            if (!in_array($method, $validSCMethods, true)) {
                throw new Saml2Exception("Invalid Method on SubjectConfirmation " . var_export($method, true) . ".");
            }

            if ($subjectConfirmationData === null) {
                throw new Saml2Exception("No SubjectConfirmationData provided.");
            }

            $this->checkAssertionSubjectConfirmationData($subjectConfirmationData);
        }
    }

    /**
     * @param SubjectConfirmationData $scd
     * @return void
     * @throws Saml2Exception
     */
    protected function checkAssertionSubjectConfirmationData(SubjectConfirmationData $scd): void
    {
        $currentURL = url()->current();
        $notBefore = $scd->getNotBefore();
        $notOnOrAfter = $scd->getNotOnOrAfter();
        $inResponseTo = $scd->getInResponseTo();
        $recipient = $scd->getRecipient();
        $requestInResponseTo = $this->response->getInResponseTo();

        if (is_int($notBefore) && $notBefore > time() + 60) {
            throw new Saml2Exception('NotBefore in SubjectConfirmationData is in the future: $notBefore');
        }

        if (is_int($notOnOrAfter) && $notOnOrAfter <= time() - 60) {
            throw new Saml2Exception("NotOnOrAfter in SubjectConfirmationData is in the past: $notOnOrAfter");
        }

        if ($recipient !== null && $recipient !== $currentURL) {
            throw new Saml2Exception(implode(' ', [
                "Recipient in SubjectConfirmationData does not match the current URL.",
                "Recipient is [$recipient], current URL is [$currentURL]."
            ]));
        }

        if ($inResponseTo !== null && $requestInResponseTo !== null && $inResponseTo !== $requestInResponseTo) {
            throw new Saml2Exception(implode(' ', [
                "InResponseTo in SubjectConfirmationData does not match the Response.",
                "Response has $requestInResponseTo, SubjectConfirmationData has [$inResponseTo].",
            ]));
        }
    }
}
