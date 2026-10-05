<?php

namespace Tests\Unit;

use App\Services\MicrosoftMailService\Transports\MicrosoftGraphTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Tests\TestCase;
use Tests\Traits\FakesMicrosoftGraph;

class MicrosoftGraphTransportTest extends TestCase
{
    use FakesMicrosoftGraph;

    /**
     * @throws TransportExceptionInterface
     * @return void
     */
    public function testSendMailThroughMicrosoftGraph(): void
    {
        $this->fakeMicrosoftGraph([
            'tenant-id' => 'test-access-token',
        ]);

        $transport = $this->makeTransport();

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test subject')
            ->text('Test body');

        $transport->send($email);

        Http::assertSent(function ($request) {
            return $request->url() ===
                'https://graph.microsoft.com/v1.0/users/sender@example.com/sendMail'
                && $request->method() === 'POST'
                && $request->header('Authorization')[0] === 'Bearer test-access-token';
        });
    }

    /**
     * @throws TransportExceptionInterface
     * @return void
     */
    public function testSendMailPreservesInlineImagesAndAttachments(): void
    {
        $this->fakeMicrosoftGraph([
            'tenant-id' => 'test-access-token',
        ]);

        $transport = $this->makeTransport();

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test subject')
            ->text('Test body');

        foreach ([false, true] as $renderFirst) {
            $inlineEmail = (clone $email)
                ->cc('cc@example.com')
                ->bcc('bcc@example.com')
                ->html('<p>Test body</p><img src="cid:qr_code.png">')
                ->embed('qr-code-content', 'qr_code.png', 'image/png')
                ->attach('document-content', 'document.pdf', 'application/pdf');

            if ($renderFirst) {
                $inlineEmail->toString();
            }

            $transport->send($inlineEmail);

            $request = Http::recorded(fn (Request $request) => $request->url() ===
                'https://graph.microsoft.com/v1.0/users/sender@example.com/sendMail')->last()[0];

            $message = $request['message'];
            $attachments = collect($message['attachments'])->keyBy('name');
            $inlineAttachment = $attachments['qr_code.png'];

            $this->assertSame('HTML', $message['body']['contentType']);
            $this->assertTrue($inlineAttachment['isInline']);
            $this->assertNotEmpty($inlineAttachment['contentId']);
            $this->assertStringContainsString('cid:' . $inlineAttachment['contentId'], $message['body']['content']);
            $this->assertSame('image/png', $inlineAttachment['contentType']);
            $this->assertSame(base64_encode('qr-code-content'), $inlineAttachment['contentBytes']);
            $this->assertSame('application/pdf', $attachments['document.pdf']['contentType']);
            $this->assertSame(base64_encode('document-content'), $attachments['document.pdf']['contentBytes']);
            $this->assertFalse($attachments['document.pdf']['isInline']);
            $this->assertSame('recipient@example.com', $message['toRecipients'][0]['emailAddress']['address']);
            $this->assertSame('cc@example.com', $message['ccRecipients'][0]['emailAddress']['address']);
            $this->assertSame('bcc@example.com', $message['bccRecipients'][0]['emailAddress']['address']);
        }
    }

    /**
     * @return void
     */
    public function testMicrosoftMailerUsesExplicitConfiguration(): void
    {
        $mailer = Mail::build([
            'transport' => 'microsoft-graph',
            'tenant_id' => 'tenant-id',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'from_email' => 'sender@example.com',
        ]);

        $this->assertInstanceOf(
            MicrosoftGraphTransport::class,
            $mailer->getSymfonyTransport(),
        );
    }

    /**
     * @return MicrosoftGraphTransport
     */
    private function makeTransport(): MicrosoftGraphTransport
    {
        return new MicrosoftGraphTransport(
            tenantId: 'tenant-id',
            clientId: 'client-id',
            clientSecret: 'client-secret',
            fromEmail: 'sender@example.com',
        );
    }
}
