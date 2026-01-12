<?php

namespace App\Events\PrevalidationRequests;

use App\Models\PrevalidationRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class BasePrevalidationRequestEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * PrevalidationRequestCreated constructor.
     * @param PrevalidationRequest $prevalidationRequest
     * @param array|null $responseData
     */
    public function __construct(
        protected PrevalidationRequest $prevalidationRequest,
        protected ?array $responseData
    ) {

    }

    /**
     * Get the prevalidation request.
     *
     * @return PrevalidationRequest
     */
    public function getPrevalidationRequest(): PrevalidationRequest
    {
        return $this->prevalidationRequest;
    }

    /**
     * @return array
     */
    public function getResponseArray(): array
    {
        return $this->responseData ? [
            'prevalidation_request_response_code' => Arr::get($this->responseData, 'code'),
            'prevalidation_request_response_body' => Str::limit(json_encode(Arr::get($this->responseData, 'body')), 4096),
        ] : [];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('channel-name');
    }
}
