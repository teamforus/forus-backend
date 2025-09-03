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
    public function startSession(array $payload = [], array $headers = [])
    {
        return $this->json($headers)->post('/start_session', $payload);
    }

    /**
     * Shortcut to end session
     */
    public function endSession(array $payload = [], array $headers = [])
    {
        return $this->json($headers)->post('/end_session', $payload);
    }

    /**
     * Shortcut to retrieve advice
     */
    public function advice(array $payload = [], array $headers = [])
    {
        return $this->json($headers)->post('/advice', $payload);
    }

    /**
     * Shortcut for stream (SSE)
     */
    public function streamChat(array $query = [], array $headers = [])
    {
        return $this->stream($headers)->get('/chat/stream', $query);
    }

    /**
     * Shortcut for sending answer
     */
    public function sendAnswer(array $payload, array $headers = [])
    {
        return $this->json($headers)->post('/chat/answer', $payload);
    }

    /**
     * Shortcut to retrieve history
     */
    public function history(array $payload = [], array $headers = [])
    {
        return $this->json($headers)->post('/chat/history', $payload);
    }

}
