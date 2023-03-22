<?php
namespace App\Services\DigIdService\Repositories\Interfaces;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use Illuminate\Http\Request;

abstract class DigIdRepo
{
    protected array $configs = [];

    /**
     * @param string $redirectUrl
     * @param string $sessionSecret
     * @return DigidAuthRequestData
     */
    abstract public function makeAuthRequest(
        string $redirectUrl,
        string $sessionSecret
    ): DigidAuthRequestData;

    /**
     * @param Request $request
     * @param string $requestId
     * @param string $sessionSecret
     * @return DigidAuthResolveData
     */
    abstract public function resolveResponse(
        Request $request,
        string $requestId,
        string $sessionSecret,
    ): DigidAuthResolveData;

    /**
     * @param Request $request
     * @param string $session_secret
     * @return bool
     */
    abstract public function validateResolveResponse(
        Request $request,
        string $session_secret
    ): bool;

    /**
     * @param string $message
     * @param string|null $digidCode
     * @return DigIdException
     */
    protected function makeException(
        string $message,
        string $digidCode = null
    ): DigIdException {
        $exception = new DigIdException($message);

        if ($digidCode) {
            return $exception->setDigIdCode($digidCode);
        }

        return $exception;
    }
}
