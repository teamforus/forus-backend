<?php

namespace Tests\Unit;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Repositories\BaseDigIdSamlRepo;
use App\Services\DigIdService\Repositories\DigIdSamlRepo;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use PHPUnit\Framework\TestCase;

class DigIdSamlResponseTest extends TestCase
{
    /**
     * @throws DigIdException
     * @return void
     */
    public function testAcceptsSuccessfulResponseForExpectedRequest(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validateResponse($this->makeResponse('_request_1'));
    }

    /**
     * @return void
     */
    public function testRejectsSuccessfulResponseWithMissingInResponseTo(): void
    {
        $this->assertResponseRejected($this->makeResponse(null), 'unknown_error');
    }

    /**
     * @return void
     */
    public function testRejectsSuccessfulResponseWithEmptyInResponseTo(): void
    {
        $this->assertResponseRejected($this->makeResponse(''), 'unknown_error');
    }

    /**
     * @return void
     */
    public function testRejectsSuccessfulResponseForAnotherRequest(): void
    {
        $this->assertResponseRejected($this->makeResponse('_request_2'), 'unknown_error');
    }

    /**
     * @return void
     */
    public function testCancellationForAnotherRequestIsRejectedAsCorrelationError(): void
    {
        $this->assertResponseRejected($this->makeResponse(
            '_request_2',
            false,
            BaseDigIdSamlRepo::DIGID_STATUS_CANCELLED,
        ), 'unknown_error');
    }

    /**
     * @return void
     */
    public function testMapsCorrelatedCancellationToCancelledError(): void
    {
        $this->assertResponseRejected($this->makeResponse(
            '_request_1',
            false,
            BaseDigIdSamlRepo::DIGID_STATUS_CANCELLED,
        ), DigIdRepo::ERROR_CANCELLED);
    }

    /**
     * @return void
     */
    public function testMapsOtherCorrelatedFailureTo403Error(): void
    {
        $this->assertResponseRejected($this->makeResponse('_request_1', false, 'RequestDenied'), '403');
    }

    /**
     * @param string|null $inResponseTo
     * @param bool $success
     * @param string|null $statusSubCode
     * @return SamlArtifactResponse
     */
    protected function makeResponse(
        ?string $inResponseTo,
        bool $success = true,
        ?string $statusSubCode = null,
    ): SamlArtifactResponse {
        $response = $this->createStub(SamlArtifactResponse::class);
        $response->method('getInResponseTo')->willReturn($inResponseTo);
        $response->method('isSuccess')->willReturn($success);
        $response->method('getStatusSubCode')->willReturn($statusSubCode);

        return $response;
    }

    /**
     * @param SamlArtifactResponse $response
     * @param string $expectedCode
     * @return void
     */
    protected function assertResponseRejected(SamlArtifactResponse $response, string $expectedCode): void
    {
        try {
            $this->validateResponse($response);
        } catch (DigIdException $exception) {
            $this->assertSame($expectedCode, $exception->getDigIdCode());

            return;
        }

        $this->fail('An invalid SAML response was accepted.');
    }

    /**
     * @param SamlArtifactResponse $response
     * @throws DigIdException
     * @return void
     */
    protected function validateResponse(SamlArtifactResponse $response): void
    {
        $repository = new class ([]) extends DigIdSamlRepo {
            /**
             * @param SamlArtifactResponse $response
             * @param string $requestId
             * @throws DigIdException
             * @return void
             */
            public function validateSamlResponse(SamlArtifactResponse $response, string $requestId): void
            {
                parent::validateSamlResponse($response, $requestId);
            }
        };

        $repository->validateSamlResponse($response, '_request_1');
    }
}
