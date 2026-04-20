<?php

namespace App\Service\Finance;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Notifications Twilio : WhatsApp + SMS optionnel.
 * Aucun polling sur l’API : la réponse du POST est traitée tout de suite (pas de délai artificiel côté serveur).
 */
final class TwilioWhatsAppNotifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromWhatsapp,
        /** Numéro Twilio en E.164 pour SMS (ex. +12025551234). Vide = pas de SMS, uniquement WhatsApp. */
        private readonly string $smsFrom,
    ) {
    }

    public function assertConfigured(): void
    {
        if ($this->accountSid === '' || $this->authToken === '') {
            throw new \RuntimeException('Configurez TWILIO_ACCOUNT_SID et TWILIO_AUTH_TOKEN dans .env.local.');
        }
        if ($this->fromWhatsapp === '') {
            throw new \RuntimeException('Configurez TWILIO_WHATSAPP_FROM (ex. whatsapp:+14155238886).');
        }
    }

    /**
     * @return array{
     *   whatsapp_sid: string,
     *   sms_sid: ?string,
     *   whatsapp_status: ?string,
     *   delivery_warning: ?string
     * }
     */
    public function sendWhatsAppAndOptionalSms(string $toRaw, string $body): array
    {
        $this->assertConfigured();

        $wa = $this->postMessage([
            'From' => $this->ensureWhatsappPrefix($this->fromWhatsapp),
            'To' => $this->ensureWhatsappPrefix($toRaw),
            'Body' => $body,
        ]);
        $waStatus = (string) ($wa['status'] ?? '');

        $this->logger->info('Twilio WhatsApp message', [
            'sid' => $wa['sid'],
            'status' => $waStatus,
            'to' => $this->ensureWhatsappPrefix($toRaw),
        ]);

        if (\in_array($waStatus, ['failed', 'canceled'], true)) {
            throw new \RuntimeException(
                'WhatsApp Twilio : envoi refusé (statut '.$waStatus.'). Vérifiez le numéro et la console Twilio.'
            );
        }

        $smsSid = null;
        if ($this->smsFrom !== '') {
            try {
                $sms = $this->postMessage([
                    'From' => $this->normalizeSmsFromE164($this->smsFrom),
                    'To' => $this->toE164($toRaw),
                    'Body' => mb_substr($body, 0, 1500),
                ]);
                $smsSid = $sms['sid'];
                $this->logger->info('Twilio SMS message', ['sid' => $smsSid, 'status' => $sms['status'] ?? null]);
            } catch (\Throwable $e) {
                $this->logger->warning('Twilio SMS optionnel échoué (WhatsApp déjà accepté par Twilio)', [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return [
            'whatsapp_sid' => $wa['sid'],
            'sms_sid' => $smsSid,
            'whatsapp_status' => $waStatus !== '' ? $waStatus : null,
            'delivery_warning' => null,
        ];
    }

    /**
     * @param array<string, string> $formFields
     *
     * @return array{sid: string, status: ?string}
     */
    private function postMessage(array $formFields): array
    {
        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid);
        $response = $this->httpClient->request('POST', $url, [
            'auth_basic' => [$this->accountSid, $this->authToken],
            'body' => $formFields,
            'timeout' => 10,
        ]);
        $code = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($code < 200 || $code >= 300) {
            $msg = $data['message'] ?? $data['error_message'] ?? $response->getContent(false);

            throw new \RuntimeException('Twilio HTTP '.$code.' : '.$msg);
        }

        if (!empty($data['error_code']) || ($data['status'] ?? '') === 'failed') {
            throw new \RuntimeException(
                'Twilio : '.($data['message'] ?? 'échec envoi').' (code '.($data['error_code'] ?? '?').').'
            );
        }

        $sid = (string) ($data['sid'] ?? '');
        if ($sid === '') {
            throw new \RuntimeException('Réponse Twilio invalide (pas de SID). Corps : '.substr($response->getContent(false), 0, 500));
        }

        return [
            'sid' => $sid,
            'status' => isset($data['status']) ? (string) $data['status'] : null,
        ];
    }

    private function ensureWhatsappPrefix(string $n): string
    {
        $t = trim($n);
        if (str_starts_with($t, 'whatsapp:')) {
            return $t;
        }
        if (str_starts_with($t, '+')) {
            return 'whatsapp:'.$t;
        }

        return 'whatsapp:+'.preg_replace('/\D+/', '', $t);
    }

    private function toE164(string $raw): string
    {
        $t = trim($raw);
        if (str_starts_with($t, 'whatsapp:')) {
            $t = substr($t, 9);
        }
        $t = trim($t);
        if (str_starts_with($t, '+')) {
            return $t;
        }

        return '+'.preg_replace('/\D+/', '', $t);
    }

    private function normalizeSmsFromE164(string $from): string
    {
        $t = trim($from);
        if (str_starts_with($t, 'whatsapp:')) {
            $t = substr($t, 9);
        }

        return $this->toE164($t);
    }
}
