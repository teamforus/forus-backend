<?php

namespace App\Support\Microservices\Precheck;

use App\Support\Microservices\BaseMicroClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

class PrecheckMicroClient extends BaseMicroClient
{
    public function __construct()
    {
        // this key must match with config/services.php
        parent::__construct('precheck_micro');
    }

    /**
     * @param array $payload
     * @param array $headers
     * @return Response
     * @throws ConnectionException
     */
    public function createSession(array $payload = [], array $headers = []): Response
    {
        return $this->client($headers)->asJson()->post('/v1/sessions', $payload);
    }

    /**
     * @param string $id
     * @param array $headers
     * @return Response
     * @throws ConnectionException
     */
    public function endSession(string $id, array $headers = []): Response
    {
        return $this->client($headers)->asJson()->delete("/v1/sessions/$id");
    }

    /**
     * @param string $id
     * @param array $headers
     * @return Response
     * @throws ConnectionException
     */
    public function advice(string $id, array $headers = []): Response
    {
        return $this->client($headers)->asJson()->get("/v1/sessions/$id/advice");
    }


    /**
     * @param string $id
     * @param array $payload
     * @param array $headers
     * @return Response
     * @throws ConnectionException
     */
    public function sendAnswer(string $id, array $payload, array $headers = []): Response
    {
        return $this->client($headers)->asJson()->post("/v1/sessions/$id/messages", $payload);
    }

    /**
     * @param string $id
     * @param array $headers
     * @return Response
     * @throws ConnectionException
     */
    public function history(string $id, array $headers = []): Response
    {
        return $this->client($headers)->asJson()->get("/v1/sessions/$id/messages");
    }

}
