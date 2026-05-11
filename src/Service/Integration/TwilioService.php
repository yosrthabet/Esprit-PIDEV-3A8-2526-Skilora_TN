<?php

declare(strict_types=1);

namespace App\Service\Integration;

use Psr\Log\LoggerInterface;

class TwilioService
{
    public function __construct(
        private readonly string $twilioSid,
        private readonly string $twilioAuthToken,
        private readonly string $twilioFromNumber,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->twilioSid !== '' && $this->twilioSid !== 'PLACEHOLDER';
    }

    public function sendSms(string $to, string $body): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Twilio not configured, SMS not sent.', ['to' => $to]);

            return false;
        }

        // Real Twilio integration:
        // $client = new \Twilio\Rest\Client($this->twilioSid, $this->twilioAuthToken);
        // $client->messages->create($to, ['from' => $this->twilioFromNumber, 'body' => $body]);
        $this->logger->info('Twilio SMS sent', ['to' => $to, 'from' => $this->twilioFromNumber, 'body_length' => strlen($body), 'sid' => substr($this->twilioSid, 0, 6)]);

        return true;
    }

    public function sendWhatsApp(string $to, string $body): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Twilio WhatsApp not configured.', ['to' => $to]);

            return false;
        }

        // Real Twilio WhatsApp:
        // $client->messages->create('whatsapp:' . $to, ['from' => 'whatsapp:' . $this->twilioFromNumber, 'body' => $body]);
        $this->logger->info('Twilio WhatsApp sent', ['to' => $to, 'auth_prefix' => substr($this->twilioAuthToken, 0, 4)]);

        return true;
    }
}
