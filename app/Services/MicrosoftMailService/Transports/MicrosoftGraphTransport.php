<?php

namespace App\Services\MicrosoftMailService\Transports;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class MicrosoftGraphTransport extends AbstractTransport
{
    private const string OPERATION_SEND = 'send';
    private const string OPERATION_TOKEN = 'token';

    /**
     * @param string $tenantId
     * @param string $clientId
     * @param string $clientSecret
     * @param string $fromEmail
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $fromEmail,
    ) {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'microsoft-graph';
    }

    /**
     * @param SentMessage $message
     * @throws ConnectionException
     * @throws RequestException
     * @return void
     */
    protected function doSend(SentMessage $message): void
    {
        /** @var Message $originalMessage */
        $originalMessage = $message->getOriginalMessage();
        $email = MessageConverter::toEmail(new Message(
            $originalMessage->getHeaders(),
            $originalMessage->getBody(),
        ));

        $accessToken = $this->getAccessToken();

        $payload = [
            'message' => [
                'subject' => $email->getSubject(),

                'body' => [
                    'contentType' => $email->getHtmlBody() ? 'HTML' : 'Text',
                    'content' => $email->getHtmlBody() ?: $email->getTextBody() ?: '',
                ],

                'attachments' => $this->attachments($email),
                'toRecipients' => $this->recipients($email->getTo()),
                'ccRecipients' => $this->recipients($email->getCc()),
                'bccRecipients' => $this->recipients($email->getBcc()),
            ],
            'saveToSentItems' => true,
        ];

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://graph.microsoft.com/v1.0/users/$this->fromEmail/sendMail", $payload);

            $response->throw();
        } catch (ConnectionException|RequestException $e) {
            $this->logRequestFailure(self::OPERATION_SEND, $e);

            throw $e;
        }
    }

    /**
     * @param Address[] $addresses
     * @return array
     */
    private function recipients(array $addresses): array
    {
        return array_map(
            fn (Address $address) => [
                'emailAddress' => [
                    'address' => $address->getAddress(),
                    'name' => $address->getName(),
                ],
            ],
            $addresses,
        );
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     * @return string
     */
    private function getAccessToken(): string
    {
        $cacheKey = $this->getTokenCacheKey();

        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = $this->requestAccessToken();

        $token = $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in');

        Cache::put($cacheKey, $token, now()->addSeconds(max($expiresIn - 60, 1)));

        return $token;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     * @return Response
     */
    private function requestAccessToken(): Response
    {
        $url = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->tenantId);

        try {
            $response = Http::asForm()->post($url, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]);

            $response->throw();

            return $response;
        } catch (ConnectionException|RequestException $e) {
            $this->logRequestFailure(self::OPERATION_TOKEN, $e);

            throw $e;
        }
    }

    /**
     * @param string $operation
     * @param ConnectionException|RequestException $exception
     * @return void
     */
    private function logRequestFailure(string $operation, ConnectionException|RequestException $exception): void
    {
        $response = $exception instanceof RequestException ? $exception->response : null;

        Log::channel('microsoft-graph')->error('Microsoft Graph request failed', [
            'operation' => $operation,
            'tenant_id' => $this->tenantId,
            'from_email' => $this->fromEmail,
            'status' => $response?->status(),
            'error_code' => $response?->json($operation === self::OPERATION_TOKEN ? 'error' : 'error.code'),
            'error_message' => $response
                ? $response->json($operation === self::OPERATION_TOKEN ? 'error_description' : 'error.message')
                : $exception->getMessage(),
        ]);
    }

    /**
     * @return string
     */
    private function getTokenCacheKey(): string
    {
        return sprintf('microsoft_graph_token:%s:%s', $this->tenantId, $this->clientId);
    }

    /**
     * @param Email $email
     * @return array
     */
    private function attachments(Email $email): array
    {
        return array_map(
            static function (DataPart $attachment): array {
                $isInline = $attachment->getDisposition() === 'inline';

                return array_filter([
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => $attachment->getFilename(),
                    'contentType' => $attachment->getContentType(),
                    'contentBytes' => base64_encode($attachment->getBody()),
                    'isInline' => $isInline,
                    'contentId' => $isInline ? $attachment->getContentId() : null,
                ], static fn (mixed $value): bool => $value !== null);
            },
            $email->getAttachments(),
        );
    }
}
