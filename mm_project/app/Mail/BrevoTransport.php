<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class BrevoTransport extends AbstractTransport
{
    public function __construct(private readonly string $apiKey)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'brevo';
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('Brevo is not configured. Set BREVO_API_KEY in the server environment.');
        }

        $email = $message->getOriginalMessage();
        if (! $email instanceof Email || count($email->getFrom()) !== 1 || $email->getAttachments() !== []) {
            throw new TransportException('Brevo requires an email with one sender and no attachments.');
        }

        $payload = [
            'sender' => $this->address($email->getFrom()[0]),
            'subject' => $email->getSubject(),
        ];
        foreach (['to' => $email->getTo(), 'cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $kind => $addresses) {
            if ($addresses !== []) {
                $payload[$kind] = array_map(fn (Address $address): array => $this->address($address), $addresses);
            }
        }
        if ($email->getReplyTo() !== []) {
            $payload['replyTo'] = $this->address($email->getReplyTo()[0]);
        }
        if ($email->getHtmlBody() !== null) {
            $payload['htmlContent'] = $email->getHtmlBody();
        }
        if ($email->getTextBody() !== null) {
            $payload['textContent'] = $email->getTextBody();
        }
        $idempotencyKey = $email->getHeaders()->get('X-Idempotency-Key')?->getBodyAsString();
        if ($idempotencyKey) {
            $payload['headers'] = ['IdempotencyKey' => $idempotencyKey];
        }

        try {
            $response = Http::acceptJson()->asJson()->withOptions(['allow_redirects' => false])->withHeaders(['api-key' => $this->apiKey])
                ->connectTimeout(5)->timeout(20)
                ->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (ConnectionException) {
            throw new TransportException('Could not connect to Brevo. The queued email can be retried.');
        }

        if ($idempotencyKey && $response->status() === 400
            && $response->json('code') === 'duplicate_parameter'
            && str_contains(strtolower((string) $response->json('message')), 'idempotency key')) {
            return;
        }
        if ($response->status() !== 201 || ! is_string($response->json('messageId')) || $response->json('messageId') === '') {
            throw new TransportException('Brevo did not accept the email (HTTP '.$response->status().'). Check the sender, account activation, API permissions, and sending quota.');
        }

        $message->setMessageId($response->json('messageId'));
    }

    /** @return array{email: string, name?: string} */
    private function address(Address $address): array
    {
        return array_filter(['email' => $address->getAddress(), 'name' => $address->getName()], fn (string $value): bool => $value !== '');
    }
}
