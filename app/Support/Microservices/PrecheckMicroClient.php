<?php

namespace App\Support\Microservices;

class PrecheckMicroClient extends BaseMicroClient
{
    public function __construct()
    {
        // this key must match with config/services.php
        parent::__construct('precheck_micro');
    }

    /**
     * Shortcut for start_session
     */
    public function createSession(array $payload = [], array $headers = [])
    {
        return $this->json($headers)->post('/v1/sessions', $payload);
    }

    /**
     * Shortcut to end session
     */
    public function endSession(string $id, array $headers = [])
    {
        return $this->json($headers)->delete("/v1/sessions/{$id}");
    }

    /**
     * Shortcut to retrieve advice
     */
    public function advice(string $id, array $headers = [])
    {
        return $this->json($headers)->get("/v1/sessions/{$id}/advice");
    }


    /**
     * Shortcut for sending answer
     */
    public function sendAnswer(string $id, array $payload, array $headers = [])
    {
        return $this->json($headers)->post("/v1/sessions/{$id}/messages", $payload);
    }

    /**
     * Shortcut to retrieve history
     */
    public function history(string $id, array $headers = [])
    {
        return $this->json($headers)->get("/v1/sessions/{$id}/messages");
    }

}
